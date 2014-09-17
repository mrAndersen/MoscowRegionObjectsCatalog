<?php

namespace MROC\MainBundle\Controller;

use Doctrine\ORM\EntityManager;
use Intervention\Image\ImageManagerStatic;
use Keboola\Csv\CsvFile;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Kernel;

class DefaultController extends Controller
{
    public function indexAction()
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        $list = $em->getRepository('MROCMainBundle:Object')->getIdAddressList();
        $objectTypes = $em->getRepository('MROCMainBundle:ObjectType')->findAll();
        $saleTypes = $em->getRepository('MROCMainBundle:SaleType')->findAll();

        return $this->render('MROCMainBundle:Default:index.html.twig',array(
            'list' => json_encode($list),
            'object_type_list' => $objectTypes,
            'sale_type_list' => $saleTypes
        ));
    }

    public function mobileIndexAction()
    {
        return $this->render('MROCMainBundle:Default:index_mobile.html.twig');
    }
}
