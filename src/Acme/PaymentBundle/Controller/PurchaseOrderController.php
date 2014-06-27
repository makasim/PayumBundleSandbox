<?php
namespace Acme\PaymentBundle\Controller;

use Payum\Core\Model\Money;
use Payum\Core\Model\Order;
use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;

class PurchaseOrderController extends Controller
{
    public function prepareAction(Request $request)
    {
        $form = $this->createPurchaseOrderForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage('Payum\Core\Model\Order');

            /** @var Order $order */
            $order = $storage->createModel();
            $order->setTotalPrice(new Money($data['amount'], $data['currency']));
            $storage->updateModel($order);

            $captureToken = $this->getTokenFactory()->createCaptureToken($data['context'], $order, 'acme_payment_details_view');

            return $this->redirect($captureToken->getTargetUrl());
        }
        
        return $this->render('AcmePaymentBundle:PurchaseOrder:prepare.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function createPurchaseOrderForm()
    {
        return $this->createFormBuilder()
            ->add('context', 'payum_context_choice')
            ->add('amount', null, array(
                'data' => 123,
                'constraints' => array(new Range(array('max' => 200)))
            ))
            ->add('currency', null, array(
                'data' => 'USD',
            ))
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