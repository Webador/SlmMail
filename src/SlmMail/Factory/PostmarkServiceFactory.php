<?php

namespace SlmMail\Factory;

use SlmMail\Factory\Exception\RuntimeException;
use SlmMail\Service\PostmarkService;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class PostmarkServiceFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');

        if (!isset($config['slm_mail']['postmark'])) {
            throw new RuntimeException(
                'Config for Postmark is not set, did you copy config file into your config/autoload folder ?'
            );
        }

        return new PostmarkService($config['slm_mail']['postmark']['key']);
    }
}
