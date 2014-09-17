<?php

namespace MROC\AdminBundle\Controller;

use Doctrine\ORM\EntityManager;
use Keboola\Csv\CsvFile;
use MROC\MainBundle\Entity\Object;
use \MROC\MainBundle\Entity\ObjectType;
use MROC\MainBundle\Entity\SaleType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('MROCAdminBundle:Default:index.html.twig');
    }

    public function clearDatabase()
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        $toClear[] = $em->getRepository('MROCMainBundle:ObjectType')->findAll();
        $toClear[] = $em->getRepository('MROCMainBundle:SaleType')->findAll();
        $toClear[] = $em->getRepository('MROCMainBundle:Object')->findAll();

        foreach($toClear as $k=>$v){
            foreach($v as $k2=>$v2){
                $em->remove($v2);
            }
        }

        $em->flush();
    }

    public function getLatLon($geocode)
    {
        $url = 'http://geocode-maps.yandex.ru/1.x/?format=json&geocode=';
        $url = $url.$geocode;

        try{
            $json = file_get_contents($url);
            $array = json_decode($json,true);

            $coordinates = $array['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']['pos'];
        }catch (\Exception $e){
            $coordinates = null;
        }

        return $coordinates;
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
            $objectsRepo = $em->getRepository('MROCMainBundle:Object');
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

                $coordinates = $this->getLatLon($address);

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
}
