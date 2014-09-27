<?php
namespace Acme\OtherExamplesBundle\Payum\FooBarGateway\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Request\Capture;

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