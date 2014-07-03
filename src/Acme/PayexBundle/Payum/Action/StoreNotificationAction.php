<?php
namespace Acme\PayexBundle\Payum\Action;

use Acme\PaymentBundle\Payum\Action\StoreNotificationAction as BaseStoreNotificationAction;
use Payum\Core\Bridge\Symfony\Request\ResponseInteractiveRequest;
use Payum\Core\Request\NotifyRequest;
use Symfony\Component\HttpFoundation\Response;

class StoreNotificationAction extends BaseStoreNotificationAction
{
    /**
     * {@inheritDoc}
     */
    public function execute($request)
    {
        parent::execute($request);

        throw new ResponseInteractiveRequest(new Response('OK'));
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return $request instanceof NotifyRequest;
    }
}