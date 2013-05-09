<?php

namespace SlmMail\Mail\Transport;

use PHPUnit_Framework_TestCase;
use SlmMailTest\Util\ServiceManagerFactory;
use Zend\Mail\Message;

class SesTransportTest extends PHPUnit_Framework_TestCase
{
    public function testCreateFromFactory()
    {
        $serviceManager = ServiceManagerFactory::getServiceManager();
        $serviceManager->setAllowOverride(true);

        $serviceManager->setFactory('Aws', function() {
            $aws = $this->getMock('Guzzle\Service\Builder\ServiceBuilderInterface');
            $aws->expects($this->once())
                ->method('get')
                ->with($this->equalTo('Ses'))
                ->will($this->returnValue($this->getMock('Aws\Ses\SesClient', array(), array(), '', false)));

            return $aws;
        });

        $transport = $serviceManager->get('SlmMail\Mail\Transport\SesTransport');

        $this->assertInstanceOf('SlmMail\Mail\Transport\HttpTransport', $transport);

        $property = new \ReflectionProperty('SlmMail\Mail\Transport\HttpTransport', 'service');
        $property->setAccessible(true);

        $this->assertInstanceOf('SlmMail\Service\SesService', $property->getValue($transport));
    }

    public function testTransportUsesSesService()
    {
        $client    = $this->getMock('Aws\Ses\SesClient', array(), array(), '', false);
        $service   = $this->getMock('SlmMail\Service\SesService', array(), array($client));
        $transport = new HttpTransport($service);
        $message   = new Message();

        $service->expects($this->once())
                ->method('send')
                ->with($this->equalTo($message));

        $transport->send($message);
    }
}
