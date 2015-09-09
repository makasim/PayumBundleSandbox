<?php
namespace Acme\PaymentBundle\Payum\Extension;

use Payum\Core\Extension\Context;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\Model\ModelAggregateInterface;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\RenderTemplate;

class AddTemplateParameterExtension implements ExtensionInterface
{
    protected $capture;

    protected $render;

    /**
     * {@inheritDoc}
     */
    public function onPreExecute(Context $context)
    {
        /** @var RenderTemplate $renderTemplate */
        $renderTemplate = $context->getRequest();
        if (false == $renderTemplate instanceof RenderTemplate) {
            return;
        }

        /** @var PaymentInterface $payment */
        $payment = null;
        foreach ($context->getPrevious() as $previous) {
            $request = $previous->getRequest();
            if ($request instanceof ModelAggregateInterface && $request->getModel() instanceof PaymentInterface) {
                $payment = $request->getModel();
            }
        }

        if (false == $payment) {
            return;
        }

        $renderTemplate->addParameter('payment', $payment);
    }

    /**
     * {@inheritDoc}
     */
    public function onExecute(Context $context)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function onPostExecute(Context $context)
    {
    }
}
