<?php
namespace Acme\PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Payum\Paypal\ExpressCheckout\Nvp\Bridge\Doctrine\Entity\PaymentInstruction;

/**
 * @ORM\Entity
 */
class PaypalExpressCheckoutInstruction extends PaymentInstruction
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;
}