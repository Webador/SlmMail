<?php

namespace SlmMail\Factory;

use SlmMail\Factory\Exception\RuntimeException;
use SlmMail\Service\AlphaMailService;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class AlphaMailServiceFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');

        if (!isset($config['slm_mail']['alpha_mail'])) {
            throw new RuntimeException(
                'Config for AlphaMail is not set, did you copy config file into your config/autoload folder ?'
            );
        }

        $config  = $config['slm_mail']['alpha_mail'];
        $service = new AlphaMailService($config['username'], $config['key']);

        $client  = $serviceLocator->get('SlmMail\Http\Client');
        $service->setClient($client);

        return $service;
    }
}
