<?php
namespace Acme\OtherExamplesBundle\Payum\Action;

use Acme\OtherExamplesBundle\Model\Cart;
use Acme\PaymentBundle\Model\PaymentDetails;
use Payum\Core\Action\PaymentAwareAction;
use Payum\Bundle\PayumBundle\Request\ResponseInteractiveRequest;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Request\SecuredCaptureRequest;
use Payum\Core\Security\SensitiveValue;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\EngineInterface;

class CaptureCartWithAuthorizeNetAction extends PaymentAwareAction 
{
    /**
     * @var RegistryInterface
     */
    protected $payum;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container) 
    {
        $this->payum = $container->get('payum');
        $this->formFactory = $container->get('form.factory');
        $this->templating = $container->get('templating');
        $this->request = $container->get('request');
    }

    /**
     * {@inheritdoc}
     */
    public function execute($request)
    {
        /** @var $request SecuredCaptureRequest */
        if (false == $this->supports($request)) {
            throw RequestNotSupportedException::createActionNotSupported($this, $request);
        }

        $form = $this->createPurchaseForm();
        $form->handleRequest($this->request);
        if ($form->isValid()) {
            $data = $form->getData();

            /** @var Cart $cart */
            $cart = $request->getModel();

            $cartStorage = $this->payum->getStorageForClass(
                $cart,
                $request->getToken()->getPaymentName()
            );

            $paymentDetailsStorage = $this->payum->getStorageForClass(
                'Acme\PaymentBundle\Model\PaymentDetails',
                $request->getToken()->getPaymentName()
            );

            /** @var $paymentDetails PaymentDetails */
            $paymentDetails = $paymentDetailsStorage->createModel();
            $paymentDetails['amount'] = $cart->getPrice();
            $paymentDetails['card_num'] = new SensitiveValue($data['card_number']);
            $paymentDetails['exp_date'] = new SensitiveValue($data['card_expiration_date']);
            $paymentDetailsStorage->updateModel($paymentDetails);

            $cart->setDetails($paymentDetails);
            $cartStorage->updateModel($cart);

            $request->setModel($paymentDetails);
            $this->payment->execute($request);

            return;
        }

        throw new ResponseInteractiveRequest(new Response(
            $this->templating->render('AcmeOtherExamplesBundle:CartExamples:_submit_credit_card.html.twig', array(
                'form' => $form->createView()
            ))
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        return
            $request instanceof SecuredCaptureRequest &&
            $request->getModel() instanceof Cart &&
            null === $request->getModel()->getDetails()
        ;
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function createPurchaseForm()
    {
        return $this->formFactory->createBuilder()
            ->add('card_number', null, array('data' => '4007000000027'))
            ->add('card_expiration_date', null, array('data' => '10/16'))

            ->getForm()
        ;
    }
}