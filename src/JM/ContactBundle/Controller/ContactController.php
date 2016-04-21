<?php

namespace JM\ContactBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class ContactController extends Controller
{
    /**
     * @Route("/")
     */
    public function viewAction()
    {
        return $this->render('JMContactBundle:Contact:view.html.twig');
    }
}