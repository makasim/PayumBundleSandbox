<?php
namespace Acme\PaymentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Payum\Request\BinaryMaskStatusRequest;
use Payum\Registry\AbstractRegistry;
use Payum\Bundle\PayumBundle\Service\TokenizedTokenService;

class DetailsController extends Controller
{
    public function viewAction($paymentName, $token)
    {
        $tokenizedDetails = $this->getTokenizedTokenService()->findTokenizedDetailsByToken($paymentName, $token);
        
        $status = new BinaryMaskStatusRequest($tokenizedDetails);
        $this->getPayum()->getPayment($paymentName)->execute($status);

        return $this->render('AcmePaymentBundle:Details:view.html.twig', array(
            'status' => $status,
            'paymentTitle' => ucwords(str_replace(array('_', '-'), ' ', $paymentName))
        ));
    }

    /**
     * @return AbstractRegistry
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }

    /**
     * @return TokenizedTokenService
     */
    protected function getTokenizedTokenService()
    {
        return $this->get('payum.tokenized_details_service');
    }
}