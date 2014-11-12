<?php
namespace Acme\RedsysBundle\Controller;

use Payum\Core\Security\GenericTokenFactoryInterface;
use Payum\Core\Registry\RegistryInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class PurchaseExamplesController extends Controller
{
    /**
     * @Extra\Route(
     *   "/prepare",
     *   name="acme_redsys_prepare"
     * )
     * 
     * @Extra\Template("AcmeRedsysBundle:PurchaseExamples:prepare.html.twig")
     */
    public function prepareAction(Request $request)
    {
    }

    /**
     * @return RegistryInterface
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }

    /**
     * @return GenericTokenFactoryInterface
     */
    protected function getTokenFactory()
    {
        return $this->get('payum.security.token_factory');
    }
}