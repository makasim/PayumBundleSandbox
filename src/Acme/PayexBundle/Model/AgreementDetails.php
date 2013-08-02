<?php
namespace Acme\PayexBundle\Model;

use Payum\Payex\Model\AgreementDetails as BaseAgreementDetails;

class AgreementDetails extends BaseAgreementDetails
{
    protected $id;
    
    public function getId()
    {
        return $this->id;
    }
}