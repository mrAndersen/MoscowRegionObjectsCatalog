<?php

namespace MROC\MainBundle\Controller;

use Doctrine\ORM\EntityManager;
use Intervention\Image\ImageManagerStatic;
use Keboola\Csv\CsvFile;
use MROC\MainBundle\Entity\Complaint;
use MROC\MainBundle\Entity\Object;
use MROC\MainBundle\Form\ComplaintType;
use MROC\MainBundle\Form\QuestionType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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


    public function commentAction(Request $reqeust)
    {

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
