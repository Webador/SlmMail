<?php

namespace SlmMailTest\Service;

use PHPUnit_Framework_TestCase;
use ReflectionMethod;
use SlmMail\Service\PostageService;
use SlmMailTest\Util\ServiceManagerFactory;
use Zend\Http\Response as HttpResponse;

class PostageServiceTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PostageService
     */
    protected $service;

    public function setUp()
    {
        $this->service = new PostageService('my-secret-key');
    }

    public function testCreateFromFactory()
    {
        $service = ServiceManagerFactory::getServiceManager()->get('SlmMail\Service\PostageService');
        $this->assertInstanceOf('SlmMail\Service\PostageService', $service);
    }

    public function exceptionDataProvider()
    {
        return array(
            array(200, null),
            array(401, 'SlmMail\Service\Exception\RuntimeException'),
            array(500, 'SlmMail\Service\Exception\RuntimeException'),
        );
    }

    /**
     * @dataProvider exceptionDataProvider
     */
    public function testExceptionsAreThrownOnErrors($statusCode, $expectedException)
    {
        $method = new ReflectionMethod('SlmMail\Service\PostageService', 'parseResponse');
        $method->setAccessible(true);

        $response = new HttpResponse();
        $response->setStatusCode($statusCode);

        $this->setExpectedException($expectedException);

        $method->invoke($this->service, $response);
    }
}
