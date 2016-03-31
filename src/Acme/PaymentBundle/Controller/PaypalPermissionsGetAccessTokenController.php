<?php

namespace Acme\PaymentBundle\Controller;

use PayPal\Service\PermissionsService;
use PayPal\Types\Common\RequestEnvelope;
use PayPal\Types\Perm\GetAccessTokenRequest;
use PayPal\Types\Perm\GetAccessTokenResponse;
use PayPal\Types\Perm\RequestPermissionsRequest;
use PayPal\Types\Perm\RequestPermissionsResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Acme\PaymentBundle\Entity\PaymentDetails;
use Payum\Core\Payum;
use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Validator\Constraints\Range;

class PaypalPermissionsGetAccessTokenController extends Controller
{

    const PAYPAL_SANDBOX_APP_ID = 'APP-80W284485P519543T';

    public function prepareAction(Request $request)
    {
        $httpRequest = $request;
        $config = array(
            'mode' => 'sandbox', // todo: make it optional
            'acct1.UserName' => $this->container->getParameter('paypal.express_checkout.username'),
            'acct1.Password' => $this->container->getParameter('paypal.express_checkout.password'),
            'acct1.Signature' => $this->container->getParameter('paypal.express_checkout.signature'),
            'acct1.AppId' => self::PAYPAL_SANDBOX_APP_ID,
        );

        $permissions = new PermissionsService($config);

        if (!$request->query->has('verification_code')) {
            $payPalAction = 'https://www.sandbox.paypal.com/webscr';

            return $this->render('AcmePaymentBundle::permissionsGetAccessToken.html.twig', array(
                'pagetitle' => 'Get Access token',
                'form_action' => $payPalAction,
                'form_method' => 'GET',
            ));
        }

        // validation is also needed..
        $request = new GetAccessTokenRequest();
        $request->token = $httpRequest->query->get('request_token');
        $request->verifier = $httpRequest->query->get('verification_code');

        $response = $permissions->GetAccessToken($request);
        /** @var GetAccessTokenResponse */

        return $this->render('AcmePaymentBundle:Details:token.html.twig', array(
            'status' => $response->responseEnvelope->ack,
            'response' => json_encode($response, JSON_PRETTY_PRINT)
        ));
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function createDetailsForm()
    {
        return $this->createFormBuilder()
            ->add('request_token', null, array(
                'data' => '',
                'property_path' => 'request_token'
            ))
            ->add('cmd', 'hidden', array(
                'data' => '_grant-permission',
                'property_path' => 'cmd',
            ))
            ->getForm()
            ;
    }

    /**
     * @return Payum
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }

}