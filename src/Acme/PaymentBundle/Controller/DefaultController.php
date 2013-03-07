<?php

namespace Acme\PaymentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('AcmePaymentBundle:Default:index.html.twig');
    }
}