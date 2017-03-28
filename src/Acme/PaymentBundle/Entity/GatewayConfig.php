<?php
namespace Acme\PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Payum\Core\Model\GatewayConfig as BaseGatewayConfig;

/**
 * @ORM\Table(name="payum_gateway_configs")
 * @ORM\Entity
 */
class GatewayConfig extends BaseGatewayConfig
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;
}
