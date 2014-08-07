<?php
namespace Acme\OtherExamplesBundle\Payum\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Model\DetailsAggregateInterface;
use Payum\Core\Request\GetStatusInterface;

class StatusDetailsAggregatedNullModelAction implements ActionInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute($request)
    {
        /** @var $request GetStatusInterface */
        if (false == $this->supports($request)) {
            throw RequestNotSupportedException::createActionNotSupported($this, $request);
        }

        $request->markNew();
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        return 
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof DetailsAggregateInterface && 
            $request->getModel()->getDetails() === null
        ;
    }
}