<?php
namespace Acme\PaymentBundle\Payum\Extension;

use Acme\PaymentBundle\Entity\NotificationDetails;
use Payum\Core\Extension\Context;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\Request\Notify;
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
     */
    public function onPreExecute(Context $context)
    {
        /** @var Notify $request */
        $request = $context->getRequest();
        if (false == $request instanceof Notify) {
            return;
        }
        if (in_array($request, $this->processedRequests)) {
            return;
        }

        $this->processedRequests[] = $request;

        $notification = new NotificationDetails();
        $request->getToken() ?
            $notification->setGatewayName($request->getToken()->getGatewayName()) :
            $notification->setGatewayName('unknown')
        ;

        $notification->setDetails($_REQUEST);
        $notification->setCreatedAt(new \DateTime());
        $this->doctrine->getManager()->persist($notification);

        $this->doctrine->getManager()->flush();
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
