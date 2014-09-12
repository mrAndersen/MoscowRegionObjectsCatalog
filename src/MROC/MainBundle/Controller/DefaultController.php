<?php

namespace MROC\MainBundle\Controller;

use Intervention\Image\ImageManagerStatic;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('MROCMainBundle:Default:index.html.twig');
    }

    public function mobileIndexAction()
    {
        return $this->render('MROCMainBundle:Default:index_mobile.html.twig');
    }
}
