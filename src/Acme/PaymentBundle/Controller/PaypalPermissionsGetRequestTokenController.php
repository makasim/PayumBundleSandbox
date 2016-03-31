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

class PaypalPermissionsGetRequestTokenController extends Controller
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

        $form = $this->createDetailsForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $callbackUrl =$data['return_url']; // todo: url to captureAction here
            $request = new RequestPermissionsRequest('EXPRESS_CHECKOUT', $callbackUrl);
            $request->requestEnvelope = new RequestEnvelope('en_US');

            /** @var RequestPermissionsResponse */
            $response = $permissions->RequestPermissions($request);

            return $this->render('AcmePaymentBundle:Details:token.html.twig', array(
                'pagetitle' => 'Request token',
                'status' => $response->responseEnvelope->ack,
                'response' => json_encode($response, JSON_PRETTY_PRINT),
            ));
        }

        return $this->render('AcmePaymentBundle::permissionsGetRequestToken.html.twig', array(
            'pagetitle' => 'Get Request token',
            'form' => $form->createView()
        ));

    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function createDetailsForm()
    {
        return $this->createFormBuilder()
            ->add('return_url', null, array(
                'data' => $this->generateUrl('acme_payment_paypal_permissions_get_access_token', array(), UrlGeneratorInterface::ABSOLUTE_URL),
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