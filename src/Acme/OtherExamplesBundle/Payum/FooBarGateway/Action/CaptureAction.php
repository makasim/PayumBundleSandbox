<?php
namespace Acme\OtherExamplesBundle\Payum\FooBarGateway\Action;

use Payum\Action\ActionInterface;
use Payum\Request\CaptureRequest;

class CaptureAction implements ActionInterface
{
    protected $gatewayUsername;

    protected $gatewayPassword;

    public function __construct($gatewayUsername, $gatewayPassword)
    {
        $this->gatewayUsername = $gatewayUsername;
        $this->gatewayPassword = $gatewayPassword;
    }

    public function execute($request)
    {
        $model = $request->getModel();

        if (isset($model['amount']) && isset($model['currency'])) {

            //do purchase call to the payment gateway using username and password.

            $model['status'] = 'success';
        } else {
            $model['status'] = 'error';
        }
    }

    public function supports($request)
    {
        return
            $request instanceof CaptureRequest &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}