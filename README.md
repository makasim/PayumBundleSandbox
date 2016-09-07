Payum Bundle Sandbox
====================

Welcome to the Payum Bundle Sandbox - a fully-functional Symfony2 application that you can use as the skeleton for your new applications.

This document contains information on how to download, install, and start using Payum Bundle. 

For a more detailed explanation, see:
 
 * [Readme][2] of the Symfony Standard Edition.
 * [Installation][1] chapter of the Symfony Documentation.
 * [Index document][3] of the PayumBundle.

[1]:  https://github.com/symfony/symfony-standard
[2]:  http://symfony.com/doc/2.8/setup.html
[3]:  https://github.com/Payum/PayumBundle/blob/master/Resources/doc/index.md
[4]:  http://getcomposer.org

1) Installation
---------------

First of all clone this repository on your local machine:

    git clone git@github.com:makasim/PayumBundleSandbox.git folder-name

As Symfony and PayumBundle uses [Composer][4] to manage its dependencies, now it's time to install them.

If you don't have Composer yet, download it following the instructions on
http://getcomposer.org/ or just run the following command:

    curl -s http://getcomposer.org/installer | php

Then go to the folder where you downloaded the repository and symply run

    composer install

2) Checking your System Configuration
-------------------------------------

Before starting coding, make sure that your local system is properly
configured for Symfony.

Execute the `check.php` script from the command line:

    php app/check.php

**Warning:**

> Don't forget to configure a payment gateway options in `app/config/parameters.yml`.

3) Configure the application
----------------------------

To make the images, css files and other files needed to display the web pages, you need to install them.

Run:

    app/console assets:install

In the folder `web/bundles` will be copied all the required files that are used to render the web pages.

Then create the database and its schema

    app/console doctrine:database:create && app/console doctrine:schema:update --force

4) Run the web server
---------------------

To use the app, you have to run the php built-in webserver using the Symfony's command

    app/console server:start

5) Browsing the Demo Application
--------------------------------
        
Congratulations! You're now using Symfony! :)

Go to `http://127.0.0.1` and you will see the Symfony's Welcome Page that tells you 

    Your application is now ready. You can start working on it at

Now you can start navigating the app.

Go to `http://127.0.0.1:8000/demo/` and you will se a list of available demos created by Symfony itself.

To see the list of demos of payment gateways, instead, go to `http://127.0.0.1:8000/payment`.