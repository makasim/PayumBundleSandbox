<?php
namespace Acme\PaymentBundle\Payum\Extension;

use Acme\PaymentBundle\Entity\NotificationDetails;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\Request\InteractiveRequestInterface;
use Payum\Core\Request\NotifyRequest;
use Payum\Core\Request\SecuredNotifyRequest;
use Symfony\Bridge\Doctrine\RegistryInterface;

class StoreNotificationExtension implements ExtensionInterface
{
    /**
     * @var RegistryInterface
     */
    protected $doctrine;

    /**
     * @var NotifyRequest[]
     */
    protected $processedRequests;

    /**
     * @param RegistryInterface $doctrine
     */
    public function __construct(RegistryInterface $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritDoc}
     */
    public function onPreExecute($request)
    {
        if (false == $request instanceof NotifyRequest) {
            return;
        }
        if (in_array($request, $this->processedRequests)) {
            return;
        }

        /** @var NotifyRequest $request */

        $notification = new NotificationDetails;
        if ($request instanceof SecuredNotifyRequest) {
            $notification->setPaymentName($request->getToken()->getPaymentName());
        } else {
            $notification->setPaymentName('unknown');
        }
        $notification->setDetails($request->getNotification());
        $notification->setCreatedAt(new \DateTime);
        $this->doctrine->getManager()->persist($notification);

        $this->doctrine->getManager()->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function onExecute($request, ActionInterface $action)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function onPostExecute($request, ActionInterface $action)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function onInteractiveRequest(InteractiveRequestInterface $interactiveRequest, $request, ActionInterface $action)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function onException(\Exception $exception, $request, ActionInterface $action = null)
    {
    }
}
