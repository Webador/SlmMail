<?php

namespace SlmMail\Mail\Transport;

use PHPUnit_Framework_TestCase;
use SlmMailTest\Util\ServiceManagerFactory;
use Zend\Mail\Message;

class PostageTransportTest extends PHPUnit_Framework_TestCase
{
    public function testCreateFromFactory()
    {
        $transport = ServiceManagerFactory::getServiceManager()->get('SlmMail\Mail\Transport\PostageTransport');

        $this->assertInstanceOf('SlmMail\Mail\Transport\HttpTransport', $transport);

        $property = new \ReflectionProperty('SlmMail\Mail\Transport\HttpTransport', 'service');
        $property->setAccessible(true);

        $this->assertInstanceOf('SlmMail\Service\PostageService', $property->getValue($transport));
    }

    public function testTransportUsesPostageService()
    {
        $service   = $this->getMock('SlmMail\Service\PostageService', array(), array('my-secret-key'));
        $transport = new HttpTransport($service);
        $message   = new Message();

        $service->expects($this->once())
                ->method('send')
                ->with($this->equalTo($message));

        $transport->send($message);
    }
}
