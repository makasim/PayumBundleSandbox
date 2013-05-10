<?php
namespace Acme\PaymentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

class DemoController extends Controller
{
    /**
     * @Extra\Route(
     *   "/payment/demos",
     *   name="acme_payment_demos"
     * )
     *
     * @Extra\Template
     */
    public function listAction()
    {
        var_dump($this->get('router')->match('http://payum-bundle-sandbox.dev/app_dev.php'));
        die;
        
        return array();
    }
}