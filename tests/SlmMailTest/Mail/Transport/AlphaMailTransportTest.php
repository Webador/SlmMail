<?php

namespace SlmMail\Mail\Transport;

use PHPUnit_Framework_TestCase;
use SlmMailTest\Util\ServiceManagerFactory;
use Zend\Mail\Message;

class AlphaMailTransportTest extends PHPUnit_Framework_TestCase
{
    public function testCreateFromFactory()
    {
        $transport = ServiceManagerFactory::getServiceManager()->get('SlmMail\Mail\Transport\AlphaMailTransport');

        $this->assertInstanceOf('SlmMail\Mail\Transport\HttpTransport', $transport);

        $property = new \ReflectionProperty('SlmMail\Mail\Transport\HttpTransport', 'service');
        $property->setAccessible(true);

        $this->assertInstanceOf('SlmMail\Service\AlphaMailService', $property->getValue($transport));
    }

    public function testTransportUsesAlphaMailService()
    {
        $service   = $this->getMock('SlmMail\Service\AlphaMailService', array(), array('my-username', 'my-secret-key'));
        $transport = new HttpTransport($service);
        $message   = new Message();

        $service->expects($this->once())
                ->method('send')
                ->with($this->equalTo($message));

        $transport->send($message);
    }
}
