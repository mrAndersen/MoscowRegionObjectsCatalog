<?php

namespace MROC\AdminBundle\Controller;

use Doctrine\Entity;
use Doctrine\ORM\EntityManager;
use FOS\UserBundle\Doctrine\UserManager;
use Intervention\Image\ImageManagerStatic;
use Keboola\Csv\CsvFile;
use MROC\AdminBundle\Helpers\YaMap;
use MROC\MainBundle\Entity\Comment;
use MROC\MainBundle\Entity\Complaint;
use MROC\MainBundle\Entity\Object;
use MROC\MainBundle\Entity\ObjectType;
use MROC\MainBundle\Entity\SaleType;
use MROC\MainBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function imagesAction(Request $request)
    {
        if($request->getMethod() == 'POST'){
            $images = $request->files->get('images');
            $override = $request->get('override',null);

            $em = $this->getDoctrine()->getManager();
            $helper = new YaMap();
            $i = 0; $j = 0;

            foreach($images as $k=>$v){
                /** @var UploadedFile $v */
                $name = $v->getClientOriginalName();
                $path = $v->getRealPath();
                $id = explode('.',$name);
                $id = $id[0];

                /** @var \MROC\MainBundle\Entity\Object $node */
                $node = $em->getRepository('MROCMainBundle:Object')->findOneBy(array('id'=>$id));

                if($node !== null && $override !== null){
                    $coordinates = $helper->getLatLonFromImage($path);
                    if($coordinates){
                        $node->setCoordinates($coordinates);
                        $i++;
                    }
                }

                $node->setImage($v);
                $node->upload();

                $em->persist($node);
                $j++;
            }

            $em->flush();

            return $this->render('MROCAdminBundle:Default:images.html.twig',array(
                'had_gps' => $i,
                'total' => $j
            ));
        }

        if($request->getMethod() == 'GET'){
            return $this->render('MROCAdminBundle:Default:images.html.twig');
        }
    }

    public function indexAction()
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        $ot = count($em->getRepository('MROCMainBundle:ObjectType')->findAll());
        $o = count($em->getRepository('MROCMainBundle:Object')->findAll());
        $st = count($em->getRepository('MROCMainBundle:SaleType')->findAll());

        return $this->render('MROCAdminBundle:Default:index.html.twig',array(
            'ot' => $ot,
            'o' => $o,
            'st' => $st
        ));
    }

    public function clearDatabase()
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        $repositories[] = $em->getRepository('MROCMainBundle:ObjectType');
        $repositories[] = $em->getRepository('MROCMainBundle:SaleType');
        $repositories[] = $em->getRepository('MROCMainBundle:Comment');
        $repositories[] = $em->getRepository('MROCMainBundle:Object');

        foreach($repositories as $k=>$v){
            $list = $v->findAll();
            foreach($list as $k2=>$v2){
                $em->remove($v2);
            }
        }
        $em->flush();

        foreach($repositories as $k=>$v){
            $table = $em->getClassMetadata($v->getClassName())->getTableName();
            $test[] = $em->getConnection()->exec("ALTER TABLE ".$table." AUTO_INCREMENT = 1");
        }
    }

    public function exportCSVAction(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        if($user->hasRole('ROLE_MUNICIPAL')){
            $objects = $em->createQueryBuilder()
                ->select('n')->from('MROCMainBundle:Object','n')
                ->where('n.municipal_id = :mid')
                ->setParameter(':mid',$user->getMunicipalId())
                ->getQuery()
                ->getResult();
        }else{
            $objects = $em->getRepository('MROCMainBundle:Object')->findAll();
        }

        $webDir = __DIR__.'/../../../../web/';
        $fileName = $webDir.'out.csv';

        $file = new CsvFile($fileName,';');

        foreach($objects as $k=>$v){
            $data = array();

            /** @var \MROC\MainBundle\Entity\Object $v */
            $data[] = $v->getAddress();
            $data[] = $v->getObjectType()->getName();
            $data[] = $v->getSaleType()->getName();
            $data[] = $v->getOwner();
            $data[] = $v->getTimes();
            $data[] = (string)$v->getRegisteredLand();
            $data[] = $v->getMunicipalId();
            $data[] = $v->getRating();

            $file->writeRow($data);
        }

        $file->__destruct();

        $response = new Response();
        $response->setContent(file_get_contents($file));

        unlink($fileName);

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="output.csv"');

        return $response;
    }

    public function filterStringVariable($var)
    {
        $var = preg_replace('/( )+/', ' ', $var);
        return $var;
    }

    public function writeUniqueSaleTypes($lines)
    {
        $i = 0;
        $em = $this->getDoctrine()->getManager();

        foreach($lines as $k=>$v){
            $key = $v[2] !='' ? $v[2] : null;
            $result[] = $this->filterStringVariable($key);
        }
        $result = array_unique($result);
        foreach($result as $k=>$v){
            $s = new SaleType();
            $s->setName($v);
            $em->persist($s);

            $i++;
        }
        $em->flush();
        return $i;
    }

    public function writeUniqueObjectTypes($lines)
    {
        $i = 0;
        $em = $this->getDoctrine()->getManager();

        foreach($lines as $k=>$v){
            $key = $v[1] !='' ? $v[1] : null;
            $result[] = $this->filterStringVariable($key);
        }
        $result = array_unique($result);
        foreach($result as $k=>$v){
            $t = new ObjectType();
            $t->setName($v);
            $em->persist($t);

            $i++;
        }
        $em->flush();
        return $i;
    }

    public function importCSVAction(Request $request)
    {
        if($request->getMethod() == 'POST'){
            /** @var EntityManager $em */
            $em = $this->getDoctrine()->getManager();
            /** @var UploadedFile $file */
            $file = $request->files->get('csv_file');

            $lines = array();
            $csv =  new CsvFile($file->getRealPath(),';');
            foreach($csv as $k=>$v){
                $lines[] = $v;
            }
            $ot = 0; $st = 0; $o = 0;


            $this->clearDatabase();
            $ot = $this->writeUniqueObjectTypes($lines);
            $st = $this->writeUniqueSaleTypes($lines);

            foreach($lines as $k=>$v){
                $address = $v[0] !='' ? $v[0] : null;
                $objectTypeName = $v[1] !='' ? $v[1] : null;
                $saleTypeName = $v[2] !='' ? $v[2] : null;
                $owner = $v[3] !='' ? $v[3] : null;
                $times = $v[4] !='' ? $v[4] : null;
                $land = $v[5] !='' ? filter_var($v[5],FILTER_VALIDATE_BOOLEAN) : null;
                $municipal = $v[6] !='' ? $v[6] : null;
                $rating = $v[7] !='' ? $v[7] : null;

                $address = $this->filterStringVariable($address);
                $objectTypeName = $this->filterStringVariable($objectTypeName);
                $saleTypeName =$this->filterStringVariable($saleTypeName);
                $owner = $this->filterStringVariable($owner);


                $objectType = $em->getRepository('MROCMainBundle:ObjectType')->findOneBy(array('name'=>$objectTypeName));
                $saleType = $em->getRepository('MROCMainBundle:SaleType')->findOneBy(array('name'=>$saleTypeName));

                $helper = new YaMap();
                $coordinates = $helper->getLatLon($address);

                $object = new Object();
                $object->setAddress($address);
                $object->setOwner($owner);
                $object->setObjectType($objectType);
                $object->setSaleType($saleType);
                $object->setCoordinates($coordinates);
                $object->setRegisteredLand($land);
                $object->setMunicipalId($municipal);
                $object->setRating($rating);
                $object->setTimes($times);

                $o++;

                $em->persist($object);
            }

            $em->flush();

            return $this->render('MROCAdminBundle:Default:csv.html.twig',array(
                'results' => array(
                    'ot' => $ot,
                    'st' => $st,
                    'o' => $o
                )
            ));
        }


        return $this->render('MROCAdminBundle:Default:csv.html.twig');
    }

    public function complaintListAction($page, Request $request)
    {
        $pageSize = 20;
        $start = $page * $pageSize;

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        /** @var Complaint $repo */
        $repo = $em->getRepository('MROCMainBundle:Complaint');

        $count = $repo->getElementsCount();
        $pages = ceil($count / $pageSize);


        $qb = $em->createQueryBuilder()
            ->select('n')->from('MROCMainBundle:Complaint','n')
            ->setMaxResults($pageSize)
            ->setFirstResult($start);

        $entities = $qb->getQuery()->getResult();

        return $this->render('MROCAdminBundle:Default:complaints.html.twig',array(
            'entities' => $entities,
            'pages' => $pages,
            'current' => $page,
            'count' => $count
        ));
    }

    public function commentListAction($page, Request $request)
    {
        $pageSize = 20;
        $start = $page * $pageSize;

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        /** @var Complaint $repo */
        $repo = $em->getRepository('MROCMainBundle:Comment');

        $count = $repo->getElementsCount();
        $pages = ceil($count / $pageSize);


        $qb = $em->createQueryBuilder()
            ->select('n')->from('MROCMainBundle:Comment','n')
            ->setMaxResults($pageSize)
            ->setFirstResult($start);

        $entities = $qb->getQuery()->getResult();

        return $this->render('MROCAdminBundle:Default:comments.html.twig',array(
            'entities' => $entities,
            'pages' => $pages,
            'current' => $page,
            'count' => $count
        ));
    }

    public function objectComplaintListAction($page, Request $request)
    {
        $pageSize = 20;
        $start = $page * $pageSize;

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        /** @var Complaint $repo */
        $repo = $em->getRepository('MROCMainBundle:ObjectComplaint');

        $count = $repo->getElementsCount();
        $pages = ceil($count / $pageSize);


        $qb = $em->createQueryBuilder()
            ->select('n')->from('MROCMainBundle:ObjectComplaint','n')
            ->setMaxResults($pageSize)
            ->setFirstResult($start);

        $entities = $qb->getQuery()->getResult();

        return $this->render('MROCAdminBundle:Default:object_complaints.html.twig',array(
            'entities' => $entities,
            'pages' => $pages,
            'current' => $page,
            'count' => $count
        ));
    }

    public function objectSuggestionListAction($page, Request $request)
    {
        $pageSize = 20;
        $start = $page * $pageSize;

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        /** @var Complaint $repo */
        $repo = $em->getRepository('MROCMainBundle:ObjectSuggestion');

        $count = $repo->getElementsCount();
        $pages = ceil($count / $pageSize);


        $qb = $em->createQueryBuilder()
            ->select('n')->from('MROCMainBundle:ObjectSuggestion','n')
            ->setMaxResults($pageSize)
            ->setFirstResult($start);

        $entities = $qb->getQuery()->getResult();

        return $this->render('MROCAdminBundle:Default:object_suggestion.html.twig',array(
            'entities' => $entities,
            'pages' => $pages,
            'current' => $page,
            'count' => $count
        ));
    }


    public function commentApproveAction($id, Request $request)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var Comment $node */
        $node = $em->getRepository('MROCMainBundle:Comment')->findOneBy(array('id' => $id));
        $node->setModerated(true);

        $em->persist($node);
        $em->flush();

        return $this->redirect($this->generateUrl('mroc_admin_comments'));
    }

    public function usersAction($page)
    {
        $pageSize = 20;
        $start = $page * $pageSize;

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        /** @var Complaint $repo */
        $repo = $em->getRepository('MROCMainBundle:User');

        $count = $repo->getElementsCount();
        $pages = ceil($count / $pageSize);


        $qb = $em->createQueryBuilder()
            ->select('n')->from('MROCMainBundle:User','n')
            ->setMaxResults($pageSize)
            ->setFirstResult($start);

        $entities = $qb->getQuery()->getResult();

        return $this->render('MROCAdminBundle:Default:users.html.twig',array(
            'entities' => $entities,
            'pages' => $pages,
            'current' => $page,
            'count' => $count
        ));
    }

    public function registerOwnerAction(Request $request)
    {
        if($request->getMethod() == 'GET'){
            $objects = $this->getDoctrine()->getManager()->getRepository('MROCMainBundle:Object')->findAll();

            return $this->render('MROCAdminBundle:Default:register_owner.html.twig',array(
                'objects' => $objects
            ));
        }
        if($request->getMethod() == 'POST'){
            $form = $request->request->all();

            $em = $this->getDoctrine()->getManager();

            /** @var UserManager $manager */
            $manager = $this->container->get('fos_user.user_manager');

            $user = $manager->createUser();
            $user->setRoles(array('ROLE_OWNER'));
            $user->setPlainPassword($form['password']);
            $user->setEmail($form['email']);
            $user->setUsername($form['username']);
            $user->setEnabled(true);

            $manager->updateUser($user);
            $id = $user->getId();

            foreach($form['objects'] as $v){
                /** @var \MROC\MainBundle\Entity\Object $object */
                $object = $em->getRepository('MROCMainBundle:Object')->findOneBy(array('id' => $v));

                $object->setUser($user);
                $em->persist($object);
            }

            $em->flush();
            return $this->redirect($this->generateUrl('mroc_admin_user_list'));
        }
    }

    public function registerMunicipalAction(Request $request)
    {
        if($request->getMethod() == 'GET'){
            return $this->render('MROCAdminBundle:Default:register_municipal.html.twig');
        }

        if($request->getMethod() == 'POST'){
            $form = $request->request->all();
            $em = $this->getDoctrine()->getManager();

            /** @var UserManager $manager */
            $manager = $this->container->get('fos_user.user_manager');

            /** @var User $user */
            $user = $manager->createUser();

            $user->setRoles(array('ROLE_MUNICIPAL'));
            $user->setPlainPassword($form['password']);
            $user->setEmail($form['email']);
            $user->setUsername($form['username']);
            $user->setEnabled(true);
            $user->setMunicipalId($form['number']);

            $manager->updateUser($user);

            return $this->redirect($this->generateUrl('mroc_admin_user_list'));
        }
    }














}
