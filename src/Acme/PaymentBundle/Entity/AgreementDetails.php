<?php
namespace Acme\PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Acme\PaymentBundle\Model\AgreementDetails as BaseAgreementDetails;

/**
 * @ORM\Table(name="payum_agreement_details")
 * @ORM\Entity
 */
class AgreementDetails extends BaseAgreementDetails
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;
}