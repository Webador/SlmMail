<?php

namespace SlmMail\Factory;

use SlmMail\Factory\Exception\RuntimeException;
use SlmMail\Service\MandrillService;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MandrillServiceFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');

        if (!isset($config['slm_mail']['mandrill'])) {
            throw new RuntimeException(
                'Config for Mandrill is not set, did you copy config file into your config/autoload folder ?'
            );
        }

        return new MandrillService($config['slm_mail']['mandrill']['key']);
    }
}
