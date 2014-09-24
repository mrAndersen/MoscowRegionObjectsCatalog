<?php

namespace MROC\AdminBundle\Controller;

use Doctrine\ORM\EntityManager;
use MROC\AdminBundle\Form\OwnerEditMessageType;
use MROC\MainBundle\Entity\Object;
use MROC\MainBundle\Entity\ObjectRepository;
use MROC\MainBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use MROC\MainBundle\Entity\ObjectType;
use MROC\AdminBundle\Form\ObjectTypeType;

/**
 * ObjectType controller.
 *
 */
class OwnerController extends Controller
{

    public function editMessageAction($id, Request $request)
    {
        /** @var \MROC\MainBundle\Entity\Object $entity */
        $entity = $this->getDoctrine()->getManager()->getRepository('MROCMainBundle:Object')->findOneBy(array('id' => $id));

        if($request->getMethod() == 'GET'){
            $form = $this->createForm(new OwnerEditMessageType(),null,array(
                'action' => $this->generateUrl('owner_edit_message', array('id' => $entity->getId())),
                'method' => 'POST',
            ));

            $form->get('header')->setData($entity->getOwnerMessageHeader());
            $form->get('text')->setData($entity->getOwnerMessage());

            return $this->render('MROCAdminBundle:Owner:edit_message.html.twig',array(
                'form' => $form->createView()
            ));
        }

        if($request->getMethod() == 'POST'){
            $em = $this->getDoctrine()->getManager();

            $form = $this->createForm(new OwnerEditMessageType());
            $form->handleRequest($request);

            if($form->isValid()){
                $data = $form->getData();

                /** @var \MROC\MainBundle\Entity\Object $entity */
                $entity = $em->getRepository('MROCMainBundle:Object')->findOneBy(array('id' => $id));

                $entity->setOwnerMessage($data['text']);
                $entity->setOwnerMessageHeader($data['header']);

                $em->persist($entity);
                $em->flush();

                return $this->redirect($this->generateUrl('owner_list_objects'));
            }
        }
    }

    public function listAction($page)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var User $user */
        $user = $this->getUser();

        $pageSize = 10;
        $start = $page * $pageSize;

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        $qb = $em->createQueryBuilder()
            ->select('n')->from('MROCMainBundle:Object','n')
            ->where('n.user = :user')
            ->setParameter(':user',$user)
            ->setMaxResults($pageSize)
            ->setFirstResult($start);

        $entities = $qb->getQuery()->getResult();

        $count = count($entities);
        $pages = ceil($count / $pageSize);

        return $this->render('MROCAdminBundle:Owner:list.html.twig',array(
            'entities' => $entities,
            'pages' => $pages,
            'current' => $page,
            'count' => $count
        ));
    }

}