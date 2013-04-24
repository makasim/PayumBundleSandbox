<?php
if (!$loader = @include __DIR__.'/../vendor/autoload.php') {
    echo <<<EOM
You must set up the project dependencies by running the following commands:

    curl -s http://getcomposer.org/installer | php
    php composer.phar install

EOM;

    exit(1);
}

$loader->add('Payum\Tests', realpath(__DIR__ .'/../vendor/payum/payum/tests'));
$loader->add('Payum\Examples', realpath(__DIR__ .'/../vendor/payum/payum/examples'));

$loader->add('Payum\Paypal\ExpressCheckout\Nvp\Examples', realpath(__DIR__ .'/../vendor/payum/paypal-express-checkout-nvp/examples'));
$loader->add('Payum\Paypal\ExpressCheckout\Nvp\Tests', realpath(__DIR__ .'/../vendor/payum/paypal-express-checkout-nvp/tests'));

$loader->add('Payum\Paypal\AuthorizeNet\Aim\Tests', realpath(__DIR__ .'/../vendor/payum/authorize-net-aim/tests'));

$loader->add('Payum\Paypal\Be2Bill\Tests', realpath(__DIR__ .'/../vendor/payum/be2bill/tests'));

$loader->add('Payum\Bundle\PayumBundle\Tests', __DIR__);

//TODO remove once composer package is available
$loader->add('Payum\Paypal\Ipn', realpath(__DIR__ .'/../vendor/payum/paypal-ipn/src'));
$loader->add('Payum\Paypal\Ipn\Tests', realpath(__DIR__ .'/../vendor/payum/paypal-ipn/tests'));