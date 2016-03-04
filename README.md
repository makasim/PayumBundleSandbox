Payum Bundle Sandbox
====================

Welcome to the Payum Bundle Sandbox - a fully-functional Symfony2 application that you can use as the skeleton for your new applications.

This document contains information on how to download, install, and start using Payum Bundle. 

For a more detailed explanation, see:
 
 * [Readme][2] of the Symfony Standard Edition.
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

2) Checking your System Configuration
-------------------------------------

Before starting coding, make sure that your local system is properly
configured for Symfony.

Execute the `check.php` script from the command line:

    php app/check.php

Access the `config.php` script from a browser:

    http://localhost/path/to/symfony/app/web/config.php

If you get any warnings or recommendations, fix them before moving on.

**Warning:**

> Don't forget to configure a payment gateway options in `app/config/parameters.yml`.


3) Setting up the web server
----------------------------

If you run a web server make sure you set the server variables `SYMFONY_ENV` and `SYMFONY_DEBUG`.

If you want to run the build-it web server you have to make sure that `variables_order` is set to `EGPCS`.
Then run:

    SYMFONY_ENV=dev SYMFONY_DEBUG=1 php app/console server:run

4) Browsing the Demo Application
--------------------------------
        
Congratulations! You're now ready to use Symfony.

From the `config.php` page, click the "Bypass configuration and go to the
Welcome page" link to load up your first Symfony page.

You can also use a web-based configurator by clicking on the "Configure your
Symfony Application online" link of the `config.php` page.

To see a list of payment demos access the following page:

    /index.php
