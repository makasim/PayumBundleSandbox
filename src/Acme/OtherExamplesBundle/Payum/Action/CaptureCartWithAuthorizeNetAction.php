<?php
namespace Acme\OtherExamplesBundle\Payum\Action;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\EngineInterface;

use Payum\Action\PaymentAwareAction;
use Payum\Registry\AbstractRegistry;
use Payum\Exception\RequestNotSupportedException;
use Payum\Bundle\PayumBundle\Request\ResponseInteractiveRequest;
use Payum\Bundle\PayumBundle\Request\CaptureTokenizedDetailsRequest;

use Acme\OtherExamplesBundle\Model\Cart;
use Acme\PaymentBundle\Model\AuthorizeNetPaymentDetails;

class CaptureCartWithAuthorizeNetAction extends PaymentAwareAction 
{
    /**
     * @var AbstractRegistry
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
        /** @var $request CaptureTokenizedDetailsRequest */
        if (false == $this->supports($request)) {
            throw RequestNotSupportedException::createActionNotSupported($this, $request);
        }

        $form = $this->createPurchaseForm();
        if ($this->request->isMethod('POST')) {
            $form->bind($this->request);
            if ($form->isValid()) {
                $data = $form->getData();

                /** @var Cart $cart */
                $cart = $request->getModel();
                
                $cartStorage = $this->payum->getStorageForClass(
                    $cart, 
                    $request->getTokenizedDetails()->getPaymentName()
                );
                
                $paymentDetailsStorage = $this->payum->getStorageForClass(
                    'Acme\PaymentBundle\Model\AuthorizeNetPaymentDetails',
                    $request->getTokenizedDetails()->getPaymentName()
                );

                /** @var $paymentDetails AuthorizeNetPaymentDetails */
                $paymentDetails = $paymentDetailsStorage->createModel();
                $paymentDetails->setAmount($cart->getPrice());
                $paymentDetails->setCardNum($data['card_number']);
                $paymentDetails->setExpDate($data['card_expiration_date']);
                $paymentDetailsStorage->updateModel($paymentDetails);
                
                $cart->setDetails($paymentDetails);
                $cartStorage->updateModel($cart);
                
                $request->setModel($paymentDetails);
                $this->payment->execute($request);
                
                return;
            }
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
            $request instanceof CaptureTokenizedDetailsRequest &&
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