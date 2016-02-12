<?php
namespace Acme\OtherExamplesBundle\Payum\FooBarGateway\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Request\Capture;

class CaptureAction implements ActionInterface
{
    public function execute($request)
    {
        $model = $request->getModel();

        if (isset($model['amount']) && isset($model['currency'])) {

            //do purchase call to the payment gateway

            $model['status'] = 'captured';
        } else {
            $model['status'] = 'error';
        }
    }

    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}