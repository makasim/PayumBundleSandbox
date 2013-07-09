<?php
namespace Acme\PaymentBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Doctrine\ORM\EntityRepository;

class NotificationsController extends Controller
{
    public function listAction()
    {
        $qb = $this->getNotificationDetailsRepository()->createQueryBuilder('n');

        $qb
            ->setMaxResults(20)
            ->addOrderBy('n.createdAt', 'DESC')
        ;

        return $this->render('AcmePaymentBundle:Notifications:list.html.twig', array(
            'notifications' => $qb->getQuery()->getResult(),
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