<?php

namespace SlmMailTest\Service;

use PHPUnit_Framework_TestCase;
use SlmMail\Service\ElasticEmailService;
use SlmMailTest\Util\ServiceManagerFactory;
use Zend\Http\Response as HttpResponse;

class ElasticEmailServiceTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ElasticEmailService
     */
    protected $service;

    public function setUp()
    {
        $this->service = new ElasticEmailService('my-username', 'my-secret-key');
    }

    public function testCreateFromFactory()
    {
        $service = ServiceManagerFactory::getServiceManager()->get('SlmMail\Service\ElasticEmailService');
        $this->assertInstanceOf('SlmMail\Service\ElasticEmailService', $service);
    }
}
