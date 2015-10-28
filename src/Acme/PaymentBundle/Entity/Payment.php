<?php
namespace Acme\PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Payum\Core\Model\Payment as BasePayment;

/**
 * @ORM\Table(name="payum_payments")
 * @ORM\Entity
 */
class Payment extends BasePayment
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