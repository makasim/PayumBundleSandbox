<?php
namespace Acme\PaypalExpressCheckoutBundle\Controller;

use Acme\PaymentBundle\Model\PaymentDetails;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Payum\Paypal\ExpressCheckout\Nvp\Api;
use Payum\Core\Registry\RegistryInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;

class PurchaseExamplesController extends Controller
{
    /**
     * @Extra\Route(
     *   "/prepare_simple_purchase_doctrine_orm",
     *   name="acme_paypal_express_checkout_prepare_simple_purchase_doctrine_orm"
     * )
     *
     * @Extra\Template("AcmePaypalExpressCheckoutBundle:PurchaseExamples:prepare.html.twig")
     */
    public function prepareSimplePurchaseAndDoctrineOrmAction(Request $request)
    {
        $paymentName = 'paypal_express_checkout_and_doctrine_orm';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Entity\PaymentDetails');

            /** @var $paymentDetails PaymentDetails */
            $paymentDetails = $storage->create();
            $paymentDetails['PAYMENTREQUEST_0_CURRENCYCODE'] = $data['currency'];
            $paymentDetails['PAYMENTREQUEST_0_AMT'] = $data['amount'];
            $storage->update($paymentDetails);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $paymentName,
                $paymentDetails,
                'acme_payment_details_view'
            );

            $paymentDetails['RETURNURL'] = $captureToken->getTargetUrl();
            $paymentDetails['CANCELURL'] = $captureToken->getTargetUrl();
            $paymentDetails['INVNUM'] = $paymentDetails->getId();
            $storage->update($paymentDetails);

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'form' => $form->createView(),
            'paymentName' => $paymentName
        );
    }

    /**
     * @Extra\Route(
     *   "/prepare_simple_purchase_doctrine_mongo_odm",
     *   name="acme_paypal_express_checkout_prepare_simple_purchase_doctrine_mongo_odm"
     * )
     *
     * @Extra\Template("AcmePaypalExpressCheckoutBundle:PurchaseExamples:prepare.html.twig")
     */
    public function prepareSimplePurchaseAndDoctrineMongoOdmAction(Request $request)
    {
        $paymentName = 'paypal_express_checkout_and_doctrine_mongo_odm';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Document\PaymentDetails');

            /** @var $paymentDetails PaymentDetails */
            $paymentDetails = $storage->create();
            $paymentDetails['PAYMENTREQUEST_0_CURRENCYCODE'] = $data['currency'];
            $paymentDetails['PAYMENTREQUEST_0_AMT'] = $data['amount'];
            $storage->update($paymentDetails);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $paymentName,
                $paymentDetails,
                'acme_payment_details_view'
            );

            $paymentDetails['RETURNURL'] = $captureToken->getTargetUrl();
            $paymentDetails['CANCELURL'] = $captureToken->getTargetUrl();
            $paymentDetails['INVNUM'] = $paymentDetails->getId();
            $storage->update($paymentDetails);

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'form' => $form->createView(),
            'paymentName' => $paymentName
        );
    }

    /**
     * @Extra\Route(
     *   "/digital_goods_purchase",
     *   name="acme_paypal_express_checkout_prepare_digital_goods_purchase"
     * )
     *
     * @Extra\Template
     */
    public function prepareDigitalGoodsAction(Request $request)
    {
        $paymentName = 'paypal_express_checkout_and_doctrine_orm';

        $eBook = array(
            'author' => 'Jules Verne',
            'name' => 'The Mysterious Island',
            'description' => 'The Mysterious Island is a novel by Jules Verne, published in 1874.',
            'price' => 2.64,
            'currency_symbol' => '$',
            'currency' => 'USD',
            'quantity' => 2
        );

        if ('POST' === $request->getMethod()) {
            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Entity\PaymentDetails');

            /** @var $paymentDetails PaymentDetails */
            $paymentDetails = $storage->create();
            $paymentDetails['PAYMENTREQUEST_0_CURRENCYCODE'] = $eBook['currency'];
            $paymentDetails['PAYMENTREQUEST_0_AMT'] = $eBook['price'] * $eBook['quantity'];
            $paymentDetails['NOSHIPPING'] = Api::NOSHIPPING_NOT_DISPLAY_ADDRESS;
            $paymentDetails['REQCONFIRMSHIPPING'] = Api::REQCONFIRMSHIPPING_NOT_REQUIRED;
            $paymentDetails['L_PAYMENTREQUEST_0_ITEMCATEGORY0'] = Api::PAYMENTREQUEST_ITERMCATEGORY_DIGITAL;
            $paymentDetails['L_PAYMENTREQUEST_0_AMT0'] = $eBook['price'];
            $paymentDetails['L_PAYMENTREQUEST_0_QTY0'] = $eBook['quantity'];
            $paymentDetails['L_PAYMENTREQUEST_0_NAME0'] = $eBook['author'].'. '.$eBook['name'];
            $paymentDetails['L_PAYMENTREQUEST_0_DESC0'] = $eBook['description'];
            $storage->update($paymentDetails);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $paymentName,
                $paymentDetails,
                'acme_payment_details_view'
            );

            $paymentDetails['RETURNURL'] = $captureToken->getTargetUrl();
            $paymentDetails['CANCELURL'] = $captureToken->getTargetUrl();
            $paymentDetails['INVNUM'] = $paymentDetails->getId();
            $storage->update($paymentDetails);

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'book' => $eBook,
            'paymentName' => $paymentName
        );
    }

    /**
     * @Extra\Route(
     *   "/prepare_simple_purchase_with_custom_api",
     *   name="acme_paypal_express_checkout_prepare_simple_purchase_with_custom_api"
     * )
     *
     * @Extra\Template
     */
    public function prepareWithCustomApiAction(Request $request)
    {
        $paymentName = 'paypal_express_checkout_and_custom_api';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Entity\PaymentDetails');

            /** @var $paymentDetails PaymentDetails */
            $paymentDetails = $storage->create();
            $paymentDetails['PAYMENTREQUEST_0_CURRENCYCODE'] = $data['currency'];
            $paymentDetails['PAYMENTREQUEST_0_AMT'] = $data['amount'];
            $storage->update($paymentDetails);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $paymentName,
                $paymentDetails,
                'acme_payment_details_view'
            );

            $paymentDetails['RETURNURL'] = $captureToken->getTargetUrl();
            $paymentDetails['CANCELURL'] = $captureToken->getTargetUrl();
            $paymentDetails['INVNUM'] = $paymentDetails->getId();
            $storage->update($paymentDetails);

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'form' => $form->createView(),
            'paymentName' => $paymentName
        );
    }

    /**
     * @Extra\Route(
     *   "/prepare_purchase_with_ipn_enabled",
     *   name="acme_paypal_express_checkout_prepare_purchase_with_ipn_enabled"
     * )
     *
     * @Extra\Template("AcmePaypalExpressCheckoutBundle:PurchaseExamples:prepare.html.twig")
     */
    public function prepareWithIpnEnabledAction(Request $request)
    {
        $paymentName = 'paypal_express_checkout_with_ipn_enabled';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Entity\PaymentDetails');

            /** @var $paymentDetails PaymentDetails */
            $paymentDetails = $storage->create();
            $paymentDetails['PAYMENTREQUEST_0_CURRENCYCODE'] = $data['currency'];
            $paymentDetails['PAYMENTREQUEST_0_AMT'] = $data['amount'];
            $storage->update($paymentDetails);

            $notifyToken = $this->getTokenFactory()->createNotifyToken($paymentName, $paymentDetails);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $paymentName,
                $paymentDetails,
                'acme_payment_details_view'
            );

            $paymentDetails['RETURNURL'] = $captureToken->getTargetUrl();
            $paymentDetails['CANCELURL'] = $captureToken->getTargetUrl();
            $paymentDetails['PAYMENTREQUEST_0_NOTIFYURL'] = $notifyToken->getTargetUrl();
            $paymentDetails['INVNUM'] = $paymentDetails->getId();
            $storage->update($paymentDetails);

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'form' => $form->createView(),
            'paymentName' => $paymentName
        );
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function createPurchaseForm()
    {
        return $this->createFormBuilder()
            ->add('amount', null, array(
                'data' => 1,
                'constraints' => array(new Range(array('max' => 2)))
            ))
            ->add('currency', null, array('data' => 'USD'))
            ->getForm()
        ;
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
