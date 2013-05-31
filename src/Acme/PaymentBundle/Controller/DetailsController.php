<?php
namespace Acme\PaymentBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Payum\Request\BinaryMaskStatusRequest;
use Payum\Registry\RegistryInterface;
use Payum\Bundle\PayumBundle\Service\TokenManager;

class DetailsController extends Controller
{
    public function viewAction(Request $request)
    {
        $token = $this->getTokenManager()->getTokenFromRequest($request);
        
        $payment = $this->getPayum()->getPayment($token->getPaymentName()); 
        
        $status = new BinaryMaskStatusRequest($token);
        $payment->execute($status);

        return $this->render('AcmePaymentBundle:Details:view.html.twig', array(
            'status' => $status,
            'paymentTitle' => ucwords(str_replace(array('_', '-'), ' ', $token->getPaymentName()))
        ));
    }

    /**
     * @return RegistryInterface
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