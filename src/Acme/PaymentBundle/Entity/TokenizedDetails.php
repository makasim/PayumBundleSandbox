<?php
namespace Acme\PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Payum\Bridge\Doctrine\Entity\TokenizedDetails as BaseTokenizedDetails;

/**
 * @ORM\Table(name="payum_tokenized_details")
 * @ORM\Entity
 */
class TokenizedDetails extends BaseTokenizedDetails
{
}