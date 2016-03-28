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
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Validator\Constraints\Range;

class PaypalPermissionsGetAccessTokenController extends Controller
{

    public function prepareAction(Request $request)
    {
        $httpRequest = $request;
        $config = array(
            'mode' => 'sandbox', // todo: make it optional
            'acct1.UserName' => $this->container->getParameter('paypal.express_checkout.username'),
            'acct1.Password' => $this->container->getParameter('paypal.express_checkout.password'),
            'acct1.Signature' => $this->container->getParameter('paypal.express_checkout.signature'),
            'acct1.AppId' => 'APP-80W284485P519543T', // sandbox App Id
        );

        $permissions = new PermissionsService($config);

        if ($request->isMethod('POST')) {
            $callbackUrl = $request->getUri(); // todo: url to captureAction here
            $request = new RequestPermissionsRequest('EXPRESS_CHECKOUT', $callbackUrl);
            $request->requestEnvelope = new RequestEnvelope('en_US');

            $response = $permissions->RequestPermissions($request);
            /** @var RequestPermissionsResponse */

            if(strtoupper($response->responseEnvelope->ack) != 'SUCCESS') {
                throw new BadCredentialsException('No token received! Response object: ' . json_encode((array) $response));
            }
            // no URI builder in SDK..
            $payPalURL = 'https://www.sandbox.paypal.com/webscr&cmd='.'_grant-permission&request_token='.$response->token;
            return $this->redirect($payPalURL);
        }

        if (!$request->query->has('verification_code')) {
            return $this->render('AcmePaymentBundle::permissionsGetToken.html.twig', array());
        }

        // validation is also needed..
        $request = new GetAccessTokenRequest();
        $request->token = $httpRequest->query->get('request_token');
        $request->verifier = $httpRequest->query->get('verification_code');

        $response = $permissions->GetAccessToken($request);
        /** @var GetAccessTokenResponse */

        return $this->render('AcmePaymentBundle:Details:accessToken.html.twig', array(
            'status' => $response->responseEnvelope->ack,
            'response' => json_encode($response, JSON_PRETTY_PRINT)
        ));

    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function createPurchaseForm()
    {
        return $this->createFormBuilder()
            ->add('Return URL', null, array(
                'data' => 1.1,
                'constraints' => array(new Range(array('max' => 2)))
            ))
//            ->add('currency', null, array('data' => 'USD'))

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