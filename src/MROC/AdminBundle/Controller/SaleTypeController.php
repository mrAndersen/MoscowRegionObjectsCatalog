<?php

namespace MROC\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use MROC\MainBundle\Entity\SaleType;
use MROC\AdminBundle\Form\SaleTypeType;

/**
 * SaleType controller.
 *
 */
class SaleTypeController extends Controller
{

    /**
     * Lists all SaleType entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('MROCMainBundle:SaleType')->findAll();

        return $this->render('MROCAdminBundle:SaleType:index.html.twig', array(
            'entities' => $entities,
        ));
    }
    /**
     * Creates a new SaleType entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new SaleType();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('sale_type'));
        }

        return $this->render('MROCAdminBundle:SaleType:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a SaleType entity.
     *
     * @param SaleType $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(SaleType $entity)
    {
        $form = $this->createForm(new SaleTypeType(), $entity, array(
            'action' => $this->generateUrl('sale_type_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new SaleType entity.
     *
     */
    public function newAction()
    {
        $entity = new SaleType();
        $form   = $this->createCreateForm($entity);

        return $this->render('MROCAdminBundle:SaleType:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing SaleType entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('MROCMainBundle:SaleType')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SaleType entity.');
        }

        $editForm = $this->createEditForm($entity);

        return $this->render('MROCAdminBundle:SaleType:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a SaleType entity.
    *
    * @param SaleType $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(SaleType $entity)
    {
        $form = $this->createForm(new SaleTypeType(), $entity, array(
            'action' => $this->generateUrl('sale_type_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing SaleType entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('MROCMainBundle:SaleType')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SaleType entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('sale_type'));
        }

        return $this->render('MROCAdminBundle:SaleType:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        ));
    }
    /**
     * Deletes a SaleType entity.
     *
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('MROCMainBundle:SaleType')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SaleType entity.');
        }

        $em->remove($entity);
        $em->flush();


        return $this->redirect($this->generateUrl('sale_type'));
    }

}
