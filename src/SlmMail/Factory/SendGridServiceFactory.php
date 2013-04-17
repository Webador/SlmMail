<?php

namespace SlmMail\Factory;

use SlmMail\Factory\Exception\RuntimeException;
use SlmMail\Service\SendGridService;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SendGridServiceFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');

        if (!isset($config['slm_mail']['send_grid'])) {
            throw new RuntimeException(
                'Config for SendGrid is not set, did you copy config file into your config/autoload folder ?'
            );
        }

        $config = $config['slm_mail']['send_grid'];

        return new SendGridService($config['username'], $config['key']);
    }
}
