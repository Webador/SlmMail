<?php

namespace SlmMail\Mail\Transport;

use PHPUnit_Framework_TestCase;
use SlmMailTest\Util\ServiceManagerFactory;
use Zend\Mail\Message;

class PostmarkTransportTest extends PHPUnit_Framework_TestCase
{
    public function testCreateFromFactory()
    {
        $transport = ServiceManagerFactory::getServiceManager()->get('SlmMail\Mail\Transport\PostmarkTransport');

        $this->assertInstanceOf('SlmMail\Mail\Transport\HttpTransport', $transport);

        $property = new \ReflectionProperty('SlmMail\Mail\Transport\HttpTransport', 'service');
        $property->setAccessible(true);

        $this->assertInstanceOf('SlmMail\Service\PostmarkService', $property->getValue($transport));
    }

    public function testTransportUsesPostmarkService()
    {
        $service   = $this->getMock('SlmMail\Service\PostmarkService', array(), array('my-secret-key'));
        $transport = new HttpTransport($service);
        $message   = new Message();

        $service->expects($this->once())
                ->method('send')
                ->with($this->equalTo($message));

        $transport->send($message);
    }
}
