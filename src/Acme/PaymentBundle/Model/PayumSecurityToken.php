<?php
namespace Acme\PaymentBundle\Model;

use Payum\Model\Token;

class PayumSecurityToken extends Token
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
}