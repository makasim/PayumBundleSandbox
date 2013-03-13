<?php
namespace Acme\PaymentBundle\Model;

class OmnipayInstruction extends \ArrayObject
{
    protected $id;
    
    public function getId()
    {
        return $this->id;
    }
}