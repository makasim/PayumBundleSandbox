<?php
namespace Acme\OtherExamplesBundle\Payum\Action;

use Acme\OtherExamplesBundle\Model\Cart;
use Acme\PaymentBundle\Model\PaymentDetails;
use Payum\Core\Action\PaymentAwareAction;
use Payum\Core\Bridge\Symfony\Reply\HttpResponse;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Capture;
use Payum\Core\Security\SensitiveValue;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureCartWithAuthorizeNetAction extends PaymentAwareAction 
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container) 
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     *
     * @param Capture $request
     */
    public function execute($request)
    {
        if (false == $this->supports($request)) {
            throw RequestNotSupportedException::createActionNotSupported($this, $request);
        }

        $form = $this->createPurchaseForm();
        $form->handleRequest($this->container->get('request'));
        if ($form->isValid()) {
            $data = $form->getData();

            /** @var Cart $cart */
            $cart = $request->getModel();

            $cartStorage = $this->container->get('payum')->getStorage($cart);

            $paymentDetailsStorage = $this->container->get('payum')->getStorage('Acme\PaymentBundle\Model\PaymentDetails');

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

        throw new HttpResponse(new Response(
            $this->container->get('templating')->render('AcmeOtherExamplesBundle:CartExamples:_submit_credit_card.html.twig', array(
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
            $request instanceof Capture &&
            $request->getToken() &&
            $request->getModel() instanceof Cart &&
            null === $request->getModel()->getDetails()
        ;
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function createPurchaseForm()
    {
        return $this->container->get('form.factory')->createBuilder()
            ->add('card_number', null, array('data' => '4007000000027'))
            ->add('card_expiration_date', null, array('data' => '10/16'))

            ->getForm()
        ;
    }
}