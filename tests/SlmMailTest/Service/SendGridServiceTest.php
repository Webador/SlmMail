<?php

namespace SlmMailTest\Service;

use PHPUnit_Framework_TestCase;
use ReflectionMethod;
use SlmMail\Service\SendGridService;
use SlmMailTest\Util\ServiceManagerFactory;
use Zend\Http\Response as HttpResponse;

class SendGridServiceTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var MailgunService
     */
    protected $service;

    public function setUp()
    {
        $this->service = new SendGridService('my-username', 'my-secret-key');
    }

    public function testCreateFromFactory()
    {
        $service = ServiceManagerFactory::getServiceManager()->get('SlmMail\Service\SendGridService');
        $this->assertInstanceOf('SlmMail\Service\SendGridService', $service);
    }

    public function exceptionDataProvider()
    {
        return array(
            array(200, null),
            array(400, 'SlmMail\Service\Exception\ValidationErrorException'),
            array(401, 'SlmMail\Service\Exception\InvalidCredentialsException'),
            array(402, 'SlmMail\Service\Exception\RuntimeException'),
            array(500, 'SlmMail\Service\Exception\RuntimeException'),
        );
    }

    /**
     * @dataProvider exceptionDataProvider
     */
    public function testExceptionsAreThrownOnErrors($statusCode, $expectedException)
    {
        /*$method = new ReflectionMethod('SlmMail\Service\MailgunService', 'parseResponse');
        $method->setAccessible(true);

        $response = new HttpResponse();
        $response->setStatusCode($statusCode);

        $this->setExpectedException($expectedException);

        $method->invoke($this->service, $response);*/
    }
}
