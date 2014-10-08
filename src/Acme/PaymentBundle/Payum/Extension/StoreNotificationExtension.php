<?php
namespace Acme\PaymentBundle\Payum\Extension;

use Acme\PaymentBundle\Entity\NotificationDetails;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\Reply\ReplyInterface;
use Payum\Core\Request\Notify;
use Payum\Core\Request\SecuredNotify;
use Symfony\Bridge\Doctrine\RegistryInterface;

class StoreNotificationExtension implements ExtensionInterface
{
    /**
     * @var RegistryInterface
     */
    protected $doctrine;

    /**
     * @var Notify[]
     */
    protected $processedRequests;

    /**
     * @param RegistryInterface $doctrine
     */
    public function __construct(RegistryInterface $doctrine)
    {
        $this->doctrine = $doctrine;
        $this->processedRequests = array();
    }

    /**
     * {@inheritDoc}
     *
     * @param Notify $request
     */
    public function onPreExecute($request)
    {
        if (false == $request instanceof Notify) {
            return;
        }
        if (in_array($request, $this->processedRequests)) {
            return;
        }

        $this->processedRequests[] = $request;

        $notification = new NotificationDetails;
        $request->getToken() ?
            $notification->setPaymentName($request->getToken()->getPaymentName()) :
            $notification->setPaymentName('unknown')
        ;

        $notification->setDetails($_REQUEST);
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
    public function onReply(ReplyInterface $reply, $request, ActionInterface $action)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function onException(\Exception $exception, $request, ActionInterface $action = null)
    {
    }
}
