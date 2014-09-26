<?php

namespace MROC\MainBundle\Controller;

use Doctrine\ORM\EntityManager;
use Intervention\Image\ImageManagerStatic;
use Keboola\Csv\CsvFile;
use MROC\MainBundle\Entity\Comment;
use MROC\MainBundle\Entity\Complaint;
use MROC\MainBundle\Entity\Object;
use MROC\MainBundle\Entity\ObjectComplaint;
use MROC\MainBundle\Form\CommentType;
use MROC\MainBundle\Form\ComplaintType;
use MROC\MainBundle\Form\ObjectComplaintType;
use MROC\MainBundle\Form\QuestionType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Kernel;

class DefaultController extends Controller
{

    public function questionAction(Request $request)
    {
        if($request->getMethod() == 'GET'){
            $form = $this->createForm(new QuestionType(),null,array(
                'action' => $this->generateUrl('_xhr_mroc_main_question'),
                'method' => 'POST'
            ));

            return $this->render('MROCMainBundle:Forms:question.html.twig', array(
                'form'   => $form->createView(),
            ));
        }

        if($request->getMethod() == 'POST'){
            $form = $this->createForm(new QuestionType());
            $form->handleRequest($request);

            if($form->isValid()){
                $body = "Данные отправителя: "."\r\n";
                $body .= "Имя: ".$form->get('name')->getData()."\r\n";
                $body .= "Электронная почта: ".$form->get('email')->getData()."\r\n";
                $body .= 'Вопрос: '."\r\n"."\r\n";
                $body .= $form->get('question')->getData();

                $email = \Swift_Message::newInstance()
                    ->setSubject('Пользователь сайта ГУП МосОблКачество задал вопрос')
                    ->setFrom('mosoblkach@yandex.ru')
                    ->setTo($this->container->getParameter('complaint_email'))
                    ->setBody($body);

                $this->get('mailer')->send($email);

                $message = 'Ваше сообщение успешно отправлено!';
                $errors = array();
            }else{
                $message = 'Произошли ошибки:';

                $errors = array();
                foreach($form->getErrors(true) as $k=>$v){
                    $errors[] = $v->getMessage();
                }
            }

            return new JsonResponse(array('message'=>$message,'errors'=>$errors));
        }
    }

    public function commentAction(Request $request)
    {
        if($request->getMethod() == 'GET'){

            $em = $this->getDoctrine()->getManager();
            $id = $request->query->get('id');

            $form = $this->createForm(new CommentType(),null,array(
                'action' => $this->generateUrl('_xhr_mroc_main_comment'),
                'method' => 'POST'
            ));

            return $this->render('MROCMainBundle:Forms:comment.html.twig', array(
                'form'   => $form->createView(),
                'node' => $em->getRepository('MROCMainBundle:Object')->findOneBy(array('id' => $id))
            ));
        }


        if($request->getMethod() == 'POST'){
            $em = $this->getDoctrine()->getManager();

            $form = $this->createForm(new CommentType());
            $form->handleRequest($request);

            if($form->isValid()){
                $data = $form->getData();
                $id = $request->request->get('mroc_mainbundle_comment');
                $id = $id['id'];


                $comment = new Comment();
                $comment->setModerated(false);
                $comment->setObject($em->getRepository('MROCMainBundle:Object')->findOneBy(array('id' => $id)));
                $comment->setPosted(new \DateTime());
                $comment->setText($data['comment']);
                $comment->setEmail($data['email']);
                $comment->setAuthor($data['name']);

                $em->persist($comment);
                $em->flush();

                $message = 'Ваше сообщение успешно отправлено!';
                $errors = array();
            }else{
                $message = 'Произошли ошибки:';

                $errors = array();
                foreach($form->getErrors(true) as $k=>$v){
                    $errors[] = $v->getMessage();
                }
            }

            return new JsonResponse(array('message'=>$message,'errors'=>$errors));
        }
    }

    public function objectComplaintAction(Request $request)
    {
        if($request->getMethod() == 'GET'){
            $entity = new ObjectComplaint();
            $id = $request->query->get('id');

            $form = $this->createForm(new ObjectComplaintType(),$entity,array(
                'action' => $this->generateUrl('_xhr_mroc_main_object_complaint'),
                'method' => 'POST'
            ));

            return $this->render('MROCMainBundle:Forms:object_complaint.html.twig', array(
                'id' => $id,
                'form'   => $form->createView(),
            ));
        }

        if($request->getMethod() == 'POST'){
            /** @var EntityManager $em */
            $em = $this->getDoctrine()->getManager();

            $entity = new ObjectComplaint();
            $form = $this->createForm(new ObjectComplaintType(),$entity);
            $form->handleRequest($request);

            if($form->isValid()){
                /** @var Session $session */
                $session = $this->get('session');
                $session->getFlashBag()->set('success','Ваше жалоба успешно отправлена');

                $entity->upload();

                $em->persist($entity);
                $em->flush();

                return $this->redirect($this->generateUrl('mroc_main_homepage'));
            }else{
                /** @var Session $session */
                $session = $this->get('session');

                $session->getFlashBag()->set('failed','Произошли ошибки:');

                $errors = array();
                foreach($form->getErrors(true) as $k=>$v){
                    $errors[] = $v->getMessage();
                }

                $session->getFlashBag()->set('errors',json_encode($errors));
                return $this->redirect($this->generateUrl('mroc_main_homepage'));
            }
        }
    }

    public function globalComplaintAction(Request $request)
    {
        if($request->getMethod() == 'GET'){
            return $this->render('MROCMainBundle:Forms:global_complaint.html.twig');
        }

        if($request->getMethod() == 'POST'){
            $form = $request->request->all();
            $errors = array(); $valid = true;

            if(!isset($form['where']) || !isset($form['what'])){
                $valid = false;
                $errors[] = 'Укажите хотя бы одну инстанцию и хотя бы одну причину обращения.';
            }

            if(empty($form['name']) || empty($form['tel']) || empty($form['email'])){
                $valid = false;
                $errors[] = 'Заполните все свои данные.';
            }

            if(!filter_var($form['email'],FILTER_VALIDATE_EMAIL)){
                $valid = false;
                $errors[] = 'Email указан не верно.';
            }

            if($valid){
                $body = "С сайта ГУП МосОблКачество была отправлена жалоба в контролирующие органы"."\r\n\r\n";
                $body .= "Данные отправителя: "."\r\n";
                $body .= "Имя: ".$form['name']."\r\n";
                $body .= "Телефон: ".$form['tel']."\r\n";
                $body .= "Электронная почта: ".$form['email']."\r\n"."\r\n";

                $replace = array(
                    'minpotreb' => 'МинПотребРУ',
                    'ufms' => 'УФМС',
                    'rospotrebnadzor' => 'Роспотребнадзор',
                    'rosadmnadzor' => 'Росадмнадзор',
                    'omsu' => 'ОМСУ',
                    'no-schema' => 'Отсутсвие объектов на схеме',
                    'foreign' => 'Мигрантов',
                    'garbage' => 'Мусор',
                    'bad-service' => 'Плохой сервис'
                );

                foreach($form['where'] as $k=>$v){
                    $where[] = $replace[$k];
                }

                foreach($form['what'] as $k=>$v){
                    $what[] = $replace[$k];
                }

                $body .= "Посетитель жалуется на:"."\r\n".implode("\r\n",$what)."\r\n\r\n";
                $body .= "В инстанции:"."\r\n".implode("\r\n",$where)."\r\n";

                $email = \Swift_Message::newInstance()
                    ->setSubject('Жалоба в контролирующие органы, с сайта ГУП МосОблКачество')
                    ->setFrom('mosoblkach@yandex.ru')
                    ->setTo($this->container->getParameter('complaint_email'))
                    ->setBody($body);

                $this->get('mailer')->send($email);

                $message = 'Ваше сообщение успешно отправлено!';
            }else{
                $message = 'Произошли ошибки:';
            }

            return new JsonResponse(array('message'=>$message,'errors'=>$errors));
        }


    }

    public function complaintAction(Request $request)
    {
        if($request->getMethod() == 'GET'){
            $entity = new Complaint();

            $form = $this->createForm(new ComplaintType(),$entity,array(
                'action' => $this->generateUrl('_xhr_mroc_main_complaint'),
                'method' => 'POST'
            ));

            return $this->render('MROCMainBundle:Forms:complaint.html.twig', array(
                'entity' => $entity,
                'form'   => $form->createView(),
            ));
        }

        if($request->getMethod() == 'POST'){
            /** @var EntityManager $em */
            $em = $this->getDoctrine()->getManager();

            $entity = new Complaint();
            $form = $this->createForm(new ComplaintType(), $entity);
            $form->handleRequest($request);

            if($form->isValid()){
                $entity->setCreatedNow();

                $em->persist($entity);
                $em->flush();

                $message = 'Ваше сообщение успешно отправлено!';
                $errors = array();
            }else{
                $message = 'Произошли ошибки:';

                $errors = array();
                foreach($form->getErrors(true) as $k=>$v){
                    $errors[] = $v->getMessage();
                }
            }

            return new JsonResponse(array('message'=>$message,'errors'=>$errors));
        }
    }









    public function indexAction()
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        $land_missing_color = $this->container->getParameter('color.land_missing');
        $default_color = $this->container->getParameter('color.default');

        $list = $em->getRepository('MROCMainBundle:Object')->getIdAddressList($land_missing_color,$default_color);
        $top =$em->getRepository('MROCMainBundle:Object')->getTop(5);

        $objectTypes = $em->getRepository('MROCMainBundle:ObjectType')->findAll();
        $saleTypes = $em->getRepository('MROCMainBundle:SaleType')->findAll();

        $useragent = $_SERVER['HTTP_USER_AGENT'];

        if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))){
            $mobile = true;
        }else{
            $mobile = false;
        }

        if($mobile){
            return $this->render('MROCMainBundle:Default:index_mobile.html.twig',array(
                'list' => json_encode($list),
                'object_type_list' => $objectTypes,
                'sale_type_list' => $saleTypes,
                'top' => $top,
                'scope' => 'mobile'
            ));
        }else{
            return $this->render('MROCMainBundle:Default:index.html.twig',array(
                'list' => json_encode($list),
                'object_type_list' => $objectTypes,
                'sale_type_list' => $saleTypes,
                'top' => $top,
                'scope' => 'normal'
            ));
        }
    }

    public function modifyRatingAction(Request $request)
    {
        $id = $request->request->get('id');
        $modify = $request->request->get('rating');

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var Object $node */
        $node = $em->getRepository('MROCMainBundle:Object')->findOneBy(array('id'=>$id));
        $votes = $node->getVotes() === null ? 0 : $node->getVotes();
        $current = $node->getRating() === null ? 0 : $node->getRating();

        $newVotes = $votes + 1;
        $newRating = ($current + $modify) / $newVotes;

        $node->setVotes($newVotes);
        $node->setRating($newRating);

        $em->persist($node);
        $em->flush();

        return new JsonResponse();
    }

    public function getExtendedAction(Request $request)
    {
        $id = $request->get('id');
        $mobile = $request->query->get('mobile',false);

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        $node = $em->getRepository('MROCMainBundle:Object')->findOneBy(array('id' => $id));
        $rank = $em->getRepository('MROCMainBundle:Object')->getRank($node);

        $comments = $em->createQueryBuilder('n')
            ->select('n')
            ->from('MROCMainBundle:Comment','n')
            ->where('n.object = :object')
            ->andWhere('n.moderated = true')
            ->orderBy('n.posted','desc')
            ->setParameter(':object',$node)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        if($mobile){
            $view = $this->renderView('MROCMainBundle:Default:extended_info_mobile.html.twig',array(
                'node' => $node,
                'rank' => $rank,
                'comments' => $comments
            ));
        }else{
            $view = $this->renderView('MROCMainBundle:Default:extended_info.html.twig',array(
                'node' => $node,
                'rank' => $rank,
                'comments' => $comments
            ));
        }



        return new JsonResponse(array('view' => $view));
    }
}
