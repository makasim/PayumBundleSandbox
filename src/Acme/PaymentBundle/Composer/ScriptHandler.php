<?php
namespace Acme\PaymentBundle\Composer;

use Composer\Script\Event;

class ScriptHandler 
{
    public static function createParametersYaml(Event $event)
    {
        $rootDir = realpath(__DIR__.'/../../../../');
        if (false == ($rootDir && is_dir($rootDir.'/app'))) {
            $event->getIO()->write('Cannot create paramaters yaml. Root dir was not guess correctly');
            
            return;
        }

        if (file_exists($rootDir.'/app/config/parameters.yml')) {
            return;
        }
        
        if (false == file_exists($rootDir.'/app/config/parameters.yml.dist')) {
            return;
        }
        
        copy(
            $rootDir.'/app/config/parameters.yml.dist',
            $rootDir.'/app/config/parameters.yml'
        );
    }
}