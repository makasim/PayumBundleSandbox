<?php
namespace Acme\PaymentBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as Mongo;
use Payum\Core\Model\ArrayObject as BaseArrayObject;

/**
 * @Mongo\Document
 */
class PaymentDetails extends BaseArrayObject
{
    /**
     * @Mongo\Id
     */
    protected $id;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
}