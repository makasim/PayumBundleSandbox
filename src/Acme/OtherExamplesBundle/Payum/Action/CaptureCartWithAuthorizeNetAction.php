<?php
namespace Acme\OtherExamplesBundle\Payum\Action;

use Acme\OtherExamplesBundle\Model\Cart;
use Acme\PaymentBundle\Model\AuthorizeNetPaymentDetails;
use Payum\Action\ActionInterface;
use Payum\Action\PaymentAwareAction;
use Payum\Bundle\PayumBundle\Request\ResponseInteractiveRequest;
use Payum\Bundle\PayumBundle\Service\TokenizedTokenService;
use Payum\Exception\RequestNotSupportedException;
use Acme\PaypalExpressCheckoutBundle\Model\PaymentDetails;
use Payum\Registry\AbstractRegistry;
use Payum\Request\CaptureRequest;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\EngineInterface;

class CaptureCartWithAuthorizeNetAction extends PaymentAwareAction 
{
    protected $payum;

    protected $formFactory;
    
    protected $templating;
    
    protected $request;

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
        /** @var $request CaptureRequest */
        if (false == $this->supports($request)) {
            throw RequestNotSupportedException::createActionNotSupported($this, $request);
        }

        $form = $this->createPurchaseForm();
        if ($this->request->isMethod('POST')) {
            $form->bindRequest($this->request);
            if ($form->isValid()) {
                $paymentName = 'authorize_net_cart';
                $data = $form->getData();

                /** @var Cart $cart */
                $cart = $request->getModel();
                $cartStorage = $this->payum->getStorageForClass($cart, $paymentName);
                $detailsStorage = $this->payum->getStorageForClass(
                    'Acme\PaymentBundle\Model\AuthorizeNetPaymentDetails',
                    $paymentName
                );

                /** @var $paymentDetails AuthorizeNetPaymentDetails */
                $paymentDetails = $detailsStorage->createModel();
                $paymentDetails->setAmount($cart->getPrice());
                $paymentDetails->setCardNum($data['card_number']);
                $paymentDetails->setExpDate($data['card_expiration_date']);

                $cart->setDetails($paymentDetails);

                $detailsStorage->updateModel($paymentDetails);
                $cartStorage->updateModel($cart);
                
                $request->setModel($paymentDetails);
                $this->payment->execute($request);
            }
        }

        throw new ResponseInteractiveRequest(new Response(
            $this->templating->render('AcmeOtherExamplesBundle:CaptureCartWithAuthorizeNet:submit_credit_card.html.twig', array(
                'form' => $form
            ))
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        return
            $request instanceof CaptureRequest &&
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