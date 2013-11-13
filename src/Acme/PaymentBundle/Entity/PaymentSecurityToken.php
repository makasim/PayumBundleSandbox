<?php
namespace Acme\PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Payum\Model\Token;

/**
 * @ORM\Table(name="payment_security_token")
 * @ORM\Entity
 */
class PaymentSecurityToken extends Token
{
}