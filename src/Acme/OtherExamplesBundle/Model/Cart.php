<?php
namespace Acme\OtherExamplesBundle\Model;

use Payum\Model\DetailsAggregateInterface;
use Payum\Model\DetailsAwareInterface;

class Cart implements DetailsAwareInterface, DetailsAggregateInterface
{
    protected $id;
    
    protected $price;
    
    protected $currency;
    
    protected $details;

    public function getId()
    {
        return $this->id;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setPrice($price)
    {
        $this->price = $price;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * {@inheritdoc}
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * {@inheritdoc}
     */
    public function setDetails($details)
    {
        $this->details = $details;   
    }
}