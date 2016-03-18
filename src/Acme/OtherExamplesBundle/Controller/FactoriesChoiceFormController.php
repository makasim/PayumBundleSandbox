<?php
namespace Acme\OtherExamplesBundle\Controller;

use Acme\OtherExamplesBundle\Model\Cart;
use Payum\Core\Bridge\Symfony\Form\Type\GatewayFactoriesChoiceType;
use Payum\Core\Payum;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class FactoriesChoiceFormController extends Controller
{
    /**
     * @Extra\Route(
     *   "/gateway_factories_choice",
     *   name="acme_other_example_gateway_factories_choice"
     * )
     *
     * @Extra\Template("AcmePaymentBundle::prepare.html.twig")
     */
    public function showAction()
    {
        $form = $this->createGatewayFactoriesForm();

        return ['form' => $form->createView()];
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function createGatewayFactoriesForm()
    {
        return $this->createFormBuilder()
            ->add('gatewayFactory', GatewayFactoriesChoiceType::class)
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
