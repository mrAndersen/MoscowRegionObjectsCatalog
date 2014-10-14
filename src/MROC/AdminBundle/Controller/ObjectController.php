<?php

namespace MROC\AdminBundle\Controller;

use Doctrine\ORM\EntityManager;
use Intervention\Image\Image;
use Intervention\Image\ImageManagerStatic;
use MROC\AdminBundle\Helpers\YaMap;
use MROC\MainBundle\Entity\ObjectRepository;
use MROC\MainBundle\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use MROC\MainBundle\Entity\Object;
use MROC\AdminBundle\Form\ObjectType;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
    public function indexAction($page, Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();

        $pageSize = 30;
        $start = $page * $pageSize;

        $sort = $request->query->get('sort','id');
        $order = $request->query->get('order','asc');

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        /** @var ObjectRepository $repo */
        $repo = $em->getRepository('MROCMainBundle:Object');

        $qcb = $em->createQueryBuilder()->select('count(n.id)')->from('MROCMainBundle:Object','n');
        if($user->hasRole('ROLE_MUNICIPAL')){
            $qcb->where('n.municipal_id = :id')->setParameter(':id',$user->getMunicipalId());
        }
        $count = $qcb->getQuery()->getOneOrNullResult();
        $count = $count[1];

        $pages = ceil($count / $pageSize);

        $qb = $em->createQueryBuilder()
            ->select('n')->from('MROCMainBundle:Object','n')
            ->orderBy('n.'.$sort,$order);
        if($user->hasRole('ROLE_MUNICIPAL')){
            $qb->where('n.municipal_id = :id')->setParameter(':id',$user->getMunicipalId());
        }
        $qb->setMaxResults($pageSize)->setFirstResult($start);

        $entities = $qb->getQuery()->getResult();

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

        if($form->isValid()){
            $entity->upload();

            $raw = $request->request->get('mroc_mainbundle_object');
            $gen_type = $raw['generation_type'];

            $em = $this->getDoctrine()->getManager();
            $helper = new YaMap();

            $entity->setCoordinateType($gen_type);

            if($gen_type === 'I'){
                $entity->setCoordinates($helper->getLatLonFromImage($entity->getImage()));
                if($entity->getCoordinates() == null){
                    $entity->setCoordinates($helper->getLatLon($entity->getAddress()));
                    $entity->setCoordinateType($entity::FROM_ADDRESS);
                }
            }

            if($gen_type === 'C'){
                $entity->setCoordinates($form->get('coordinates')->getData());
            }

            if($gen_type === 'A'){
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
        $user = $this->getUser();
        $form = $this->createForm(new ObjectType(), $entity, array(
            'action' => $this->generateUrl('object_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        if($user->hasRole('ROLE_MUNICIPAL')){
            $form->get('municipal_id')->setData($user->getMunicipalId());
        }

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
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        /** @var Object $entity */
        $entity = $em->getRepository('MROCMainBundle:Object')->find($id);

        if($user->hasRole('ROLE_MUNICIPAL') && $entity->getMunicipalId() !== $user->getMunicipalId()){
            throw new AccessDeniedException;
        }

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
        /** @var Object $entity */
        $entity = $em->getRepository('MROCMainBundle:Object')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Object entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if($editForm->isValid()){
            $entity->upload();
            $helper = new YaMap();

            $raw = $request->request->get('mroc_mainbundle_object');
            $gen_type = $raw['generation_type'];

            $entity->setCoordinateType($gen_type);

            if($gen_type === 'I'){
                $entity->setCoordinates($helper->getLatLonFromImage($entity->getImage()));
                if($entity->getCoordinates() == null){
                    $entity->setCoordinates($helper->getLatLon($entity->getAddress()));
                    $entity->setCoordinateType($entity::FROM_ADDRESS);
                }
            }

            if($gen_type === 'C'){
                $entity->setCoordinates($editForm->get('coordinates')->getData());
            }

            if($gen_type === 'A'){
                $entity->setCoordinates($helper->getLatLon($entity->getAddress()));
            }


            $em->flush();
            return $this->redirect($this->generateUrl('object'));
        }

        return $this->render('MROCAdminBundle:Object:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        ));
    }

    /**
     * Deletes a Object entity.
     *
     */
    public function deleteAction($id)
    {
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        /** @var Object $entity */
        $entity = $em->getRepository('MROCMainBundle:Object')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Object entity.');
        }

        if($user->hasRole('ROLE_MUNICIPAL') && $entity->getMunicipalId() !== $user->getMunicipalId()){
            throw new AccessDeniedException;
        }

        $em->remove($entity);
        $em->flush();


        return $this->redirect($this->generateUrl('object'));
    }
}
