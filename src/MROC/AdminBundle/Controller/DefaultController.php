<?php

namespace MROC\AdminBundle\Controller;

use Doctrine\Entity;
use Doctrine\ORM\EntityManager;
use Intervention\Image\ImageManagerStatic;
use Keboola\Csv\CsvFile;
use MROC\AdminBundle\Helpers\YaMap;
use MROC\MainBundle\Entity\Comment;
use MROC\MainBundle\Entity\Complaint;
use MROC\MainBundle\Entity\Object;
use MROC\MainBundle\Entity\ObjectType;
use MROC\MainBundle\Entity\SaleType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

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

    public function importCSVAction(Request $request)
    {
        if($request->getMethod() == 'POST'){
            /** @var EntityManager $em */
            $em = $this->getDoctrine()->getManager();
            /** @var UploadedFile $file */
            $file = $request->files->get('csv_file');

            $csv =  new CsvFile($file->getRealPath(),';');
            foreach($csv as $k=>$v){
                $lines[] = $v;
            }

            $this->clearDatabase();
            $objectsTypeRepo = $em->getRepository('MROCMainBundle:ObjectType');
            $saleTypeRepo = $em->getRepository('MROCMainBundle:SaleType');
            $ot = 0; $st = 0; $o = 0;

            foreach($lines as $k=>$v){
                $address = !empty($v[0]) ? $v[0] : null;
                $objectTypeName = !empty($v[1]) ? $v[1] : null;
                $saleTypeName = !empty($v[2]) ? $v[2] : null;
                $owner = !empty($v[3]) ? $v[3] : null;

                $address = trim(preg_replace('/\s+/', ' ', $address));
                $objectTypeName = trim(preg_replace('/\s+/', ' ', $objectTypeName));
                $saleTypeName = trim(preg_replace('/\s+/', ' ', $saleTypeName));
                $owner = trim(preg_replace('/\s+/', ' ', $owner));

                if($objectsTypeRepo->findOneBy(array('name'=>$objectTypeName)) === null){
                    $objectType = new ObjectType();
                    $objectType->setName($objectTypeName);
                    $em->persist($objectType); $ot++;
                    $em->flush();
                }else{
                    $objectType = $objectsTypeRepo->findOneBy(array('name'=>$objectTypeName));
                }

                if($saleTypeRepo->findOneBy(array('name'=>$saleTypeName)) === null){
                    $saleType = new SaleType();
                    $saleType->setName($saleTypeName);
                    $em->persist($saleType); $st++;
                    $em->flush();
                }else{
                    $saleType = $saleTypeRepo->findOneBy(array('name'=>$saleTypeName));
                }

                $helper = new YaMap();
                $coordinates = $helper->getLatLon($address);

                $object = new Object();
                $object->setAddress($address);
                $object->setOwner($owner);
                $object->setObjectType($objectType);
                $object->setSaleType($saleType);
                $object->setCoordinates($coordinates);

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














}
