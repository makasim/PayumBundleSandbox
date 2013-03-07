Payum Bundle Sandbox
====================

Welcome to the Payum Bundle Sandbox - a fully-functional Symfony2 application that you can use as the skeleton for your new applications.

This document contains information on how to download, install, and start using Payum Bundle. 

For a more detailed explanation, see:
 
 * [Readme][2] of the Symfony Standart Edition.
 * [Installation][1] chapter of the Symfony Documentation.
 * [Index document][3] of the PayumBundle.

[1]:  https://github.com/symfony/symfony-standard
[2]:  http://symfony.com/doc/2.1/book/installation.html
[3]:  https://github.com/Payum/PayumBundle/blob/master/Resources/doc/index.md
[4]:  http://getcomposer.org

1) Installation
---------------

As Symfony and PayumBundle uses [Composer][4] to manage its dependencies, the recommended way
to create a new project is to use it.

If you don't have Composer yet, download it following the instructions on
http://getcomposer.org/ or just run the following command:

    curl -s http://getcomposer.org/installer | php

Then, use the `create-project` command to generate a new Symfony application:

    php composer.phar create-project payum/payum-bundle-sandbox path/to/install --stability=dev

Composer will install Symfony and all its dependencies under the
`path/to/install` directory.

2) Some additional steps to make it work
----------------------------------------

To start play with payum bundle you have to do some additional preparations:

* Create `config_dev_local.yml` from `config_dev_local.yml.example` and edit parameters in it.
* Create dir `payments` in `app/Resources`. It will be used for storing your payments details.
 
3) Browsing the Demo Application
--------------------------------
        
Congratulations! You're now ready to use the application.

To see a list of payment demos access the following page:

    /app_dev.php/payment
