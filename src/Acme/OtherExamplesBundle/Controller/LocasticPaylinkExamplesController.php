<?php
namespace Acme\OtherExamplesBundle\Controller;

use Acme\PaymentBundle\Model\PaymentDetails;
use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class LocasticPaylinkExamplesController extends Controller
{
    /**
     * @Extra\Route(
     *   "/locastic-paylink/prepare",
     *   name="acme_other_locastic_paylink_prepare"
     * )
     *
     * @Extra\Template
     */
    public function prepareAction(Request $request)
    {
        $paymentName = 'paylink';

        $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Model\PaymentDetails');

        /** @var PaymentDetails */
        $details = $storage->create();
        $details['NAME.GIVEN'] = 'John';
        $details['NAME.FAMILY'] = 'Doe';
        $details['CONTACT.EMAIL'] = 'foo@example.com';
        $details['ADDRESS.STREET'] = 'aStreet';
        $details['ADDRESS.CITY'] = 'aCity';
        $details['PRESENTATION.AMOUNT'] = 1;
        $details['PRESENTATION.CURRENCY'] = 'USD';
        $details['PRESENTATION.USAGE'] = 'aDescription';
        $storage->update($details);

        $captureToken = $this->getTokenFactory()->createCaptureToken(
            $paymentName,
            $details,
            'acme_payment_details_view'
        );

        return $this->redirect($captureToken->getTargetUrl());
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
