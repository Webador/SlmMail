<?php

namespace SlmMailTest\Service;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use SlmMail\Service\SparkPostService;
use SlmMailTest\Util\ServiceManagerFactory;
use Laminas\Http\Response as HttpResponse;

class SparkPostServiceTest extends TestCase
{
    /**
     * @var SparkPostService
     */
    protected $service;

    protected function setUp(): void
    {
        $this->service = new SparkPostService('my-secret-key');
    }

    public function testCreateFromFactory()
    {
        $service = ServiceManagerFactory::getServiceManager()->get('SlmMail\Service\SparkPostService');
        $this->assertInstanceOf('SlmMail\Service\SparkPostService', $service);
    }

    public function exceptionDataProvider()
    {
        return array(
            array(200, null, null),
            array(400, '{"name":"UnknownError","message":"An error occured on SparkPost (http code 400), message: Unknown error", "code":4}', 'SlmMail\Service\Exception\RuntimeException'),
            array(500, '{"name":"GeneralError","message":"SparkPost server error, please try again", "code":4}', 'SlmMail\Service\Exception\RuntimeException'),
        );
    }

    /**
     * @dataProvider exceptionDataProvider
     */
    public function testExceptionsAreThrownOnErrors($statusCode, $content, $expectedException)
    {
        $method = new ReflectionMethod('SlmMail\Service\SparkPostService', 'parseResponse');
        $method->setAccessible(true);

        $response = new HttpResponse();
        $response->setStatusCode($statusCode);
        $response->setContent($content);

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $actual = $method->invoke($this->service, $response);
        $this->assertNull($actual);
    }
}
