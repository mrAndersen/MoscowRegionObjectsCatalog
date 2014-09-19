<?php

namespace MROC\MainBundle\Controller;

use Doctrine\ORM\EntityManager;
use Intervention\Image\ImageManagerStatic;
use Keboola\Csv\CsvFile;
use MROC\MainBundle\Entity\Object;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
