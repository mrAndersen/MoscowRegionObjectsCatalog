<?php

namespace MROC\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('MROCAdminBundle:Default:index.html.twig');
    }
}
