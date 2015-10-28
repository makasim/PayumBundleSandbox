<?php
namespace Acme\PayexBundle\Controller;

use Acme\PaymentBundle\Entity\AgreementDetails;
use Acme\PaymentBundle\Entity\PaymentDetails;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Request\Sync;
use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Model\Identificator;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Payum\Payex\Api\AgreementApi;
use Payum\Payex\Api\OrderApi;
use Payum\Payex\Request\Api\CreateAgreement;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints\Range;

class OneClickExamplesController extends Controller
{
    /**
     * @Extra\Route(
     *   "/one_click/confirm_agreement",
     *   name="acme_payex_one_click_confirm_agreement"
     * )
     *
     * @Extra\Template()
     */
    public function confirmAgreementAction(Request $request)
    {
        $gatewayName = 'payex_agreement';

        $agreementStorage = $this->getPayum()->getStorage('Acme\PaymentBundle\Entity\AgreementDetails');

        if ($request->get('confirm')) {
            $syncAgreement = new Sync(new Identificator(
                $request->get('agreementId'),
                'Acme\PaymentBundle\Entity\AgreementDetails'
            ));

            $this->getPayum()->getGateway($gatewayName)->execute($syncAgreement);

            /** @var AgreementDetails $agreement */
            $agreement = $syncAgreement->getModel();

            $agreementStatus = new GetHumanStatus($agreement);
            $this->getPayum()->getGateway($gatewayName)->execute($agreementStatus);

            if ($agreementStatus->isCaptured()) {
                return $this->redirect($this->generateUrl('acme_payex_one_click_purchase', array(
                    'agreementId' => $agreement->getId()
                )));
            } elseif ($agreementStatus->isNew()) {
                $paymentStorage = $this->getPayum()->getStorage('Acme\PaymentBundle\Entity\PaymentDetails');

                /** @var $payment PaymentDetails */
                $payment = $paymentStorage->create();
                $payment['price'] = 1000;
                $payment['priceArgList'] = '';
                $payment['vat'] = 0;
                $payment['currency'] = 'NOK';
                $payment['orderId'] = 123;
                $payment['productNumber'] = 123;
                $payment['purchaseOperation'] = OrderApi::PURCHASEOPERATION_SALE;
                $payment['view'] = OrderApi::VIEW_CREDITCARD;
                $payment['description'] = 'a desc';
                $payment['clientIPAddress'] = $request->getClientIp();
                $payment['clientIdentifier'] = '';
                $payment['additionalValues'] = '';
                $payment['agreementRef'] = $agreement['agreementRef'];
                $payment['clientLanguage'] = 'en-US';

                $paymentStorage->update($payment);

                $captureToken = $this->getTokenFactory()->createCaptureToken(
                    $gatewayName,
                    $payment,
                    'acme_payex_one_click_confirm_agreement',
                    array('agreementId' => $agreement->getId(), 'confirm' => 1)
                );

                $payment['returnUrl'] = $captureToken->getTargetUrl();
                $payment['cancelUrl'] = $captureToken->getTargetUrl();
                $paymentStorage->update($payment);

                return $this->redirect($captureToken->getTargetUrl());
            }
        } else {
            /** @var AgreementDetails $agreement */
            $agreement = $agreementStorage->create();
            $agreement['maxAmount'] = 10000;
            $agreement['purchaseOperation'] = AgreementApi::PURCHASEOPERATION_AUTHORIZATION;
            $agreement['merchantRef'] = 'aRef';
            $agreement['description'] = 'aDesc';
            $agreement['startDate'] = '';
            $agreement['stopDate'] = '';

            $this->getPayum()->getGateway($gatewayName)->execute(new CreateAgreement($agreement));
            $this->getPayum()->getGateway($gatewayName)->execute(new Sync($agreement));

            $agreementStatus = new GetHumanStatus($agreement);
            $this->getPayum()->getGateway($gatewayName)->execute($agreementStatus);
        }

        return array(
            'agreementStatus' => $agreementStatus
        );
    }

    /**
     * @Extra\Route(
     *   "/one_click/purchase",
     *   name="acme_payex_one_click_purchase"
     * )
     *
     * @Extra\Template()
     */
    public function purchaseAction(Request $request)
    {
        $gatewayName = 'payex_agreement';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {

            $agreementStatus = new GetHumanStatus(new Identificator(
                $request->get('agreementId'),
                'Acme\PaymentBundle\Entity\AgreementDetails'
            ));
            $this->getPayum()->getGateway($gatewayName)->execute($agreementStatus);
            if (false == $agreementStatus->isCaptured()) {
                throw new HttpException(400, sprintf(
                    'Agreement has to have confirmed status, but it is %s',
                    $agreementStatus->getStatus()
                ));
            }

            /** @var AgreementDetails $agreement */
            $agreement = $agreementStatus->getModel();

            $paymentStorage = $this->getPayum()->getStorage('Acme\PaymentBundle\Entity\PaymentDetails');

            /** @var $payment PaymentDetails */
            $payment = $paymentStorage->create();
            $payment['price'] = 1000;
            $payment['currency'] = 'NOK';
            $payment['orderId'] = 123;
            $payment['productNumber'] = 123;
            $payment['purchaseOperation'] = OrderApi::PURCHASEOPERATION_SALE;
            $payment['description'] = 'a desc';
            $payment['agreementRef'] = $agreement['agreementRef'];
            $payment['autoPay'] = true;

            $paymentStorage->update($payment);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_details_view'
            );

            $payment['Returnurl'] = $captureToken->getTargetUrl();
            $payment['Cancelurl'] = $captureToken->getTargetUrl();
            $paymentStorage->update($payment);

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'form' => $form->createView(),
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
