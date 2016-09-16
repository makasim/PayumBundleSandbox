<?php

use Doctrine\ODM\MongoDB\Types\Type;
use Payum\Core\Bridge\Doctrine\Types\ObjectType;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function boot()
    {
        /**
         * DEFINE THE CUSTOM OBJECT TYPE AS DESCRIBED IN THE PAYUMBUNDLE DOCUMENTATION:
         *
         * @see https://github.com/Payum/PayumBundle/blob/master/Resources/doc/storages.md
         */
        if (false == Type::hasType('object')) {
            Type::addType('object', ObjectType::class);
        }

        parent::boot();
    }

    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),

            /**
             * HERE ALL THE BUNDLES REQUIRED TO MAKE PAYUMBUNDLE WORK
             */
            new Payum\Bundle\PayumBundle\PayumBundle(),

            new AppBundle\AppBundle(),
            new Acme\DemoBundle\AcmeDemoBundle(),
            new Acme\PaymentBundle\AcmePaymentBundle(),
            new Acme\PaypalExpressCheckoutBundle\AcmePaypalExpressCheckoutBundle(),
            new Acme\StripeBundle\AcmeStripeBundle(),
            new Acme\RedsysBundle\AcmeRedsysBundle(),
            new Acme\PayexBundle\AcmePayexBundle(),
            new Acme\KlarnaBundle\AcmeKlarnaBundle(),
            new Acme\OtherExamplesBundle\AcmeOtherExamplesBundle(),

            new Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle(),
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            new Sonata\AdminBundle\SonataAdminBundle(),
            new Sonata\BlockBundle\SonataBlockBundle(),
            new Sonata\CoreBundle\SonataCoreBundle(),
            new Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'), true)) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');
    }
}
