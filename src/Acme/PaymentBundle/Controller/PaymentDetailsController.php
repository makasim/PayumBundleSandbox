<?php
namespace Acme\PaymentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Payum\Bundle\PayumBundle\Context\ContextRegistry;
use Payum\Request\BinaryMaskStatusRequest;

class PaymentDetailsController extends Controller
{
    /**
     * @Extra\Route(
     *   "/payment/{contextName}/{model}/details",
     *   name="acme_payment_payment_details_view"
     * )
     *
     * @Extra\Template
     */
    public function viewAction($model, $contextName)
    {
        $context = $this->getPayum()->getContext($contextName);

        $status = new BinaryMaskStatusRequest($model);
        $context->getPayment()->execute($status);
        
        return array(
            'status' => $status
        );
    }

    /**
     * @return ContextRegistry
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }
}