<?php


namespace SlmMail\Factory;


use Aws\Sdk;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use SlmMail\Factory\Exception\RuntimeException;

class AwsSdkFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');

        if (!isset($config['slm_mail']['ses'])) {
            throw new RuntimeException(
                'Config for SES is not set, did you copy config file into your config/autoload folder ?'
            );
        }

        return new Sdk($config['slm_mail']['ses']);
    }
}
