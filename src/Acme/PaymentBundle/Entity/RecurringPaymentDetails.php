<?php
namespace Acme\PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Payum\Core\Model\ArrayObject;

/**
 * @ORM\Table(name="payum_recurring_payment_details")
 * @ORM\Entity
 */
class RecurringPaymentDetails extends ArrayObject
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}