<?php
namespace Acme\PaymentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Payum\Bundle\PayumBundle\Context\ContextRegistry;
use Payum\Request\CaptureRequest;
use Payum\Exception\InvalidArgumentException;

use Acme\PaymentBundle\Util\Random;

class CaptureController extends Controller
{
    /**
     * @var array
     */
    protected $metaInfos = array();
    
    /**
     * @Extra\Route(
     *   "/payment/capture/{token}",
     *   name="acme_payment_capture",
     *   defaults={"_controller"="acme_payment.controller.capture" }
     * )
     */
    public function doAction($token)
    {
        if (false == $metaInfo = $this->getMetaInfo($token)) {
            throw $this->createNotFoundException('MetaInfo Not Found');
        }
        
        $context = $this->getPayum()->getContext($metaInfo['contextName']);

        $captureRequest = new CaptureRequest($metaInfo['model']);
        $context->getPayment()->execute($captureRequest);
        
        $this->removeMetaInfo($token);

        return $this->redirect($metaInfo['redirectUrl']);
    }

    /**
     * @param object $model
     * @param string $contextName
     * @param string $redirectUrl
     * 
     * @throws \Payum\Exception\InvalidArgumentException
     * 
     * @return string
     */
    public function createMetaInfo($model, $contextName, $redirectUrl)
    {
        if (false == (is_object($model) && method_exists($model, 'getId'))) {
            throw new InvalidArgumentException(sprintf(
                'Invalid model given it must be object with getId method. Given %s',
                is_object($model) ? get_class($model) : gettype($model)
            ));
        }
        
        if (false == $this->getPayum()->hasContext($contextName)) {
            throw new InvalidArgumentException(sprintf('Invalid payum contextName given: %s.', $contextName));
        }
        
        $token = Random::generateToken();

        $this->metaInfos[$token] = array(
            'model' => $model,
            'contextName' => $contextName,
            'redirectUrl' => $redirectUrl
        );
        
        $this->getRequest()->getSession()->set('_payum_capture_meta_info_'.$token, array(
            'model' => $model->getId(),
            'contextName' => $contextName,
            'redirectUrl' => $redirectUrl
        ));
        
        return $token;
    }

    /**
     * @param string $token
     *
     * @return array|null
     */
    protected function getMetaInfo($token)
    {
        if (array_key_exists($token, $this->metaInfos)) {
            return $this->metaInfos[$token];
        }
        
        return $this->getRequest()->getSession()->get('_payum_capture_meta_info_'.$token);
    }

    /**
     * @param string $token
     */
    protected function removeMetaInfo($token)
    {
        unset($this->metaInfos[$token]);
        
        $this->getRequest()->getSession()->remove('_payum_capture_meta_info_'.$token);
    }

    /**
     * @return ContextRegistry
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }
}