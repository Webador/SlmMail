<?php
/**
 * @author niki
 */

namespace SlmMailTest\Service;

use PHPUnit_Framework_TestCase;
use ReflectionMethod;
use SlmMail\Service\SparkPostService;
use SlmMailTest\Util\ServiceManagerFactory;
use Zend\Http\Response as HttpResponse;

class SparkPostServiceTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var SparkPostService
     */
    protected $service;

    public function setUp()
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
            array(401, '{"name":"InvalidKey","message":"Invalid credentials", "code":4}', 'SlmMail\Service\Exception\InvalidCredentialsException'),
            array(400, '{"name":"ValidationError","message":"Validation failed", "code":4}', 'SlmMail\Service\Exception\ValidationErrorException'),
            array(420, '{"name":"ValidationError","message":"Validation failed", "code":4}', 'SlmMail\Service\Exception\ValidationErrorException'),
            array(500, '{"name":"GeneralError","message":"Failed", "code":4}', 'SlmMail\Service\Exception\RuntimeException'),
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

        $this->setExpectedException($expectedException);

        $method->invoke($this->service, $response);
    }
}
