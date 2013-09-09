<?php
namespace Acme\PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Payum\Bridge\Doctrine\Entity\Token;

/**
 * @ORM\Table(name="payum_security_token")
 * @ORM\Entity
 */
class PayumSecurityToken extends Token
{
}