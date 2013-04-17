<?php

namespace SlmMail\Factory;

use SlmMail\Factory\Exception\RuntimeException;
use SlmMail\Service\ElasticEmailService;
use SlmMail\Service\MandrillService;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ElasticEmailServiceFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');

        if (!isset($config['slm_mail']['elastic_email'])) {
            throw new RuntimeException(
                'Config for ElasticEmail is not set, did you copy config file into your config/autoload folder ?'
            );
        }

        $config = $config['slm_mail']['elastic_email'];

        return new ElasticEmailService($config['username'], $config['key']);
    }
}
