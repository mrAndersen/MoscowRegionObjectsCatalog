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
                return $this->render('MROCMainBundle:Forms:failed_complaint.html.twig',array(
                    'errors' => $form->getErrors()
                ));
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

        $list = $em->getRepository('MROCMainBundle:Object')->getIdAddressList();
        $top =$em->getRepository('MROCMainBundle:Object')->getTop(5);

        $objectTypes = $em->getRepository('MROCMainBundle:ObjectType')->findAll();
        $saleTypes = $em->getRepository('MROCMainBundle:SaleType')->findAll();

        return $this->render('MROCMainBundle:Default:index.html.twig',array(
            'list' => json_encode($list),
            'object_type_list' => $objectTypes,
            'sale_type_list' => $saleTypes,
            'top' => $top
        ));
    }

    public function mobileIndexAction()
    {
        return $this->render('MROCMainBundle:Default:index_mobile.html.twig');
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

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        $node = $em->getRepository('MROCMainBundle:Object')->findOneBy(array('id' => $id));
        $rank = $em->getRepository('MROCMainBundle:Object')->getRank($node);


        $view = $this->renderView('MROCMainBundle:Default:extended_info.html.twig',array(
            'node' => $node,
            'rank' => $rank
        ));

        return new JsonResponse(array('view' => $view));
    }
}
