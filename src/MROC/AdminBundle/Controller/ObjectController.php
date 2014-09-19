<?php

namespace MROC\AdminBundle\Controller;

use Doctrine\ORM\EntityManager;
use Intervention\Image\Image;
use Intervention\Image\ImageManagerStatic;
use MROC\AdminBundle\Helpers\YaMap;
use MROC\MainBundle\Entity\ObjectRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use MROC\MainBundle\Entity\Object;
use MROC\AdminBundle\Form\ObjectType;

/**
 * Object controller.
 *
 */
class ObjectController extends Controller
{

    /**
     * Lists all Object entities.
     *
     */
    public function indexAction($page)
    {
        $pageSize = 10;
        $start = $page * $pageSize;

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        /** @var ObjectRepository $repo */
        $repo = $em->getRepository('MROCMainBundle:Object');

        $count = $repo->getElementsCount();
        $pages = ceil($count / $pageSize);

        $dql = 'select q from MROCMainBundle:Object q';
        $query = $em->createQuery($dql)->setMaxResults($pageSize)->setFirstResult($start);
        $entities = $query->getResult();

        return $this->render('MROCAdminBundle:Object:index.html.twig',array(
            'entities' => $entities,
            'pages' => $pages,
            'current' => $page,
            'count' => $count
        ));
    }

    /**
     * Creates a new Object entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Object();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()){
            $entity->upload();

            $em = $this->getDoctrine()->getManager();
            $helper = new YaMap();

            if($entity->getOverride() == true){
                $entity->setCoordinates($helper->getLatLonFromImage($entity->getImage()));
                if($entity->getCoordinates() == null){
                    $entity->setCoordinates($helper->getLatLon($entity->getAddress()));
                }
            }else{
                $entity->setCoordinates($helper->getLatLon($entity->getAddress()));
            }

            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('object'));
        }

        return $this->render('MROCAdminBundle:Object:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Object entity.
     *
     * @param Object $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Object $entity)
    {
        $form = $this->createForm(new ObjectType(), $entity, array(
            'action' => $this->generateUrl('object_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Object entity.
     *
     */
    public function newAction()
    {
        $entity = new Object();
        $form   = $this->createCreateForm($entity);

        return $this->render('MROCAdminBundle:Object:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Object entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('MROCMainBundle:Object')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Object entity.');
        }

        $editForm = $this->createEditForm($entity);

        return $this->render('MROCAdminBundle:Object:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a Object entity.
    *
    * @param Object $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Object $entity)
    {
        $form = $this->createForm(new ObjectType(), $entity, array(
            'action' => $this->generateUrl('object_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }

    /**
     * Edits an existing Object entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('MROCMainBundle:Object')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Object entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()){
            $entity->upload();

            $em->flush();
            return $this->redirect($this->generateUrl('object'));
        }

        return $this->render('MROCAdminBundle:Object:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        ));
    }

    public function removeImageFromDisk($id)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        /** @var Object $node */
        $node = $em->getRepository('MROCMainBundle:Object')->findOneBy(array('id'=>$id));

        $path = $this->get('kernel')->getRootDir().'/../web';

        $truePath = $path.$node->getImage();
        $trueTPath = $path.$node->getImageT();

        unlink($truePath);
        unlink($trueTPath);
    }

    /**
     * Deletes a Object entity.
     *
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('MROCMainBundle:Object')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Object entity.');
        }

        $em->remove($entity);
        $em->flush();


        return $this->redirect($this->generateUrl('object'));
    }
}
