<?php
namespace Acme\PayexBundle\Payum\Action;

use Acme\PaymentBundle\Payum\Action\StoreNotificationAction as BaseStoreNotificationAction;
use Payum\Core\Bridge\Symfony\Reply\HttpResponse;
use Payum\Core\Request\Notify;
use Symfony\Component\HttpFoundation\Response;

class StoreNotificationAction extends BaseStoreNotificationAction
{
    /**
     * {@inheritDoc}
     */
    public function execute($request)
    {
        parent::execute($request);

        throw new HttpResponse(new Response('OK'));
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return $request instanceof Notify;
    }
}