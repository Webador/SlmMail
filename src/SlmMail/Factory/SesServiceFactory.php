<?php

namespace SlmMail\Factory;

use SlmMail\Service\SesService;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SesServiceFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new SesService($serviceLocator->get('Aws')->get('Ses'));
    }
}
