<?php

namespace SlmMail\Factory;

use SlmMail\Factory\Exception\RuntimeException;
use SlmMail\Service\PostageService;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class PostageServiceFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        if (!isset($config['slm_mail']['postage'])) {
            throw new RuntimeException(
                'Config for Postage is not set, did you copy config file into your config/autoload folder ?'
            );
        }

        return new PostageService($config['slm_mail']['postage']['key']);
    }
}
