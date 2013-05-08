<?php

namespace SlmMail\Factory;

use Zend\Http\Client as HttpClient;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class HttpClientFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');

        if (!isset($config['slm_mail']['http_adapter'])
         || !isset($config['slm_mail']['http_options'])
        ) {
            return new HttpClient();
        }

        $client = new HttpClient();
        $client->setAdapter($config['slm_mail']['http_adapter']);
        $client->getAdapter()->setOptions($config['slm_mail']['http_options']);

        return $client;
    }
}
