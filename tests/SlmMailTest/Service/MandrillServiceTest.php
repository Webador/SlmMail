<?php

namespace SlmMailTest\Service;

use PHPUnit_Framework_TestCase;
use ReflectionMethod;
use SlmMail\Service\MandrillService;
use SlmMailTest\Util\ServiceManagerFactory;
use Zend\Http\Response as HttpResponse;

class MandrillServiceTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var MandrillService
     */
    protected $service;

    public function setUp()
    {
        $this->service = new MandrillService('my-secret-key');
    }

    public function testCreateFromFactory()
    {
        $service = ServiceManagerFactory::getServiceManager()->get('SlmMail\Service\MandrillService');
        $this->assertInstanceOf('SlmMail\Service\MandrillService', $service);
    }

    public function exceptionDataProvider()
    {
        return array(
            array(200, null, null),
            array(401, '{"name":"InvalidKey","message":"Invalid credentials", "code":4}', 'SlmMail\Service\Exception\InvalidCredentialsException'),
            array(400, '{"name":"ValidationError","message":"Validation failed", "code":4}', 'SlmMail\Service\Exception\ValidationErrorException'),
            array(500, '{"name":"GeneralError","message":"Failed", "code":4}', 'SlmMail\Service\Exception\RuntimeException'),
        );
    }

    /**
     * @dataProvider exceptionDataProvider
     */
    public function testExceptionsAreThrownOnErrors($statusCode, $content, $expectedException)
    {
        $method = new ReflectionMethod('SlmMail\Service\MandrillService', 'parseResponse');
        $method->setAccessible(true);

        $response = new HttpResponse();
        $response->setStatusCode($statusCode);
        $response->setContent($content);

        $this->setExpectedException($expectedException);

        $method->invoke($this->service, $response);
    }
}
