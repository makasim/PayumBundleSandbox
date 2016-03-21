<?php
namespace Acme\PaymentBundle\Controller;

use Acme\PaymentBundle\Entity\NotificationDetails;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Doctrine\ORM\EntityRepository;

class NotificationsController extends Controller
{
    public function listAction()
    {
        $query = $this->getNotificationDetailsRepository()->createQueryBuilder('n')
            ->setMaxResults(20)
            ->addOrderBy('n.createdAt', 'DESC')

            ->getQuery()
        ;

        $notifications = array();
        foreach ($query->getResult() as $notification) {
            /** @var NotificationDetails $notification */
            $notifications[] = array(
                'id' => $notification->getId(),
                'gatewayName' => $notification->getGatewayName(),
                'details' => var_export($notification->getDetails(), true),
                'createdAt' => $notification->getCreatedAt(),
            );
        }

        return $this->render('AcmePaymentBundle:Notifications:list.html.twig', array(
            'notifications' => $notifications
        ));
    }

    /**
     * @return EntityRepository
     */
    protected function getNotificationDetailsRepository()
    {
        return $this->getDoctrine()->getRepository('Acme\PaymentBundle\Entity\NotificationDetails');
    }
}
