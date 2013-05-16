<?php
namespace Acme\PaymentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Payum\Request\BinaryMaskStatusRequest;
use Payum\Registry\AbstractRegistry;
use Payum\Bundle\PayumBundle\Service\TokenManager;

class DetailsController extends Controller
{
    public function viewAction($paymentName, $token)
    {
        $tokenizedDetails = $this->getTokenManager()->findByToken($paymentName, $token);
        
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
     * @return TokenManager
     */
    protected function getTokenManager()
    {
        return $this->get('payum.token_manager');
    }
}