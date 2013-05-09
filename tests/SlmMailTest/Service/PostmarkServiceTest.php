<?php

namespace SlmMailTest\Service;

use PHPUnit_Framework_TestCase;
use ReflectionMethod;
use SlmMail\Service\PostmarkService;
use SlmMailTest\Util\ServiceManagerFactory;
use Zend\Http\Response as HttpResponse;

class PostmarkServiceTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PostmarkService
     */
    protected $service;

    public function setUp()
    {
        $this->service = new PostmarkService('my-secret-key');
    }

    public function testCreateFromFactory()
    {
        $service = ServiceManagerFactory::getServiceManager()->get('SlmMail\Service\PostmarkService');
        $this->assertInstanceOf('SlmMail\Service\PostmarkService', $service);
    }

    public function exceptionDataProvider()
    {
        return array(
            array(200, null),
            array(401, 'SlmMail\Service\Exception\InvalidCredentialsException'),
            array(422, 'SlmMail\Service\Exception\ValidationErrorException'),
            array(500, 'SlmMail\Service\Exception\RuntimeException'),
            array(404, 'SlmMail\Service\Exception\RuntimeException')
        );
    }

    /**
     * @dataProvider exceptionDataProvider
     */
    public function testExceptionsAreThrownOnErrors($statusCode, $expectedException)
    {
        $method = new ReflectionMethod('SlmMail\Service\PostmarkService', 'parseResponse');
        $method->setAccessible(true);

        $response = new HttpResponse();
        $response->setStatusCode($statusCode);

        $this->setExpectedException($expectedException);

        $method->invoke($this->service, $response);
    }
}
