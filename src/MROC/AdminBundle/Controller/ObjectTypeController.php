<?php

namespace MROC\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use MROC\MainBundle\Entity\ObjectType;
use MROC\AdminBundle\Form\ObjectTypeType;

/**
 * ObjectType controller.
 *
 */
class ObjectTypeController extends Controller
{

    /**
     * Lists all ObjectType entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('MROCMainBundle:ObjectType')->findAll();

        return $this->render('MROCAdminBundle:ObjectType:index.html.twig', array(
            'entities' => $entities,
        ));
    }
    /**
     * Creates a new ObjectType entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new ObjectType();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('object_type_show', array('id' => $entity->getId())));
        }

        return $this->render('MROCAdminBundle:ObjectType:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a ObjectType entity.
     *
     * @param ObjectType $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(ObjectType $entity)
    {
        $form = $this->createForm(new ObjectTypeType(), $entity, array(
            'action' => $this->generateUrl('object_type_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new ObjectType entity.
     *
     */
    public function newAction()
    {
        $entity = new ObjectType();
        $form   = $this->createCreateForm($entity);

        return $this->render('MROCAdminBundle:ObjectType:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a ObjectType entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('MROCMainBundle:ObjectType')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ObjectType entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('MROCAdminBundle:ObjectType:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing ObjectType entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('MROCMainBundle:ObjectType')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ObjectType entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('MROCAdminBundle:ObjectType:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a ObjectType entity.
    *
    * @param ObjectType $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(ObjectType $entity)
    {
        $form = $this->createForm(new ObjectTypeType(), $entity, array(
            'action' => $this->generateUrl('object_type_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing ObjectType entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('MROCMainBundle:ObjectType')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ObjectType entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('object_type_edit', array('id' => $id)));
        }

        return $this->render('MROCAdminBundle:ObjectType:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a ObjectType entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('MROCMainBundle:ObjectType')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find ObjectType entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('object_type'));
    }

    /**
     * Creates a form to delete a ObjectType entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('object_type_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }
}
