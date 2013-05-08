<?php

namespace SlmMailTest\Service;

use PHPUnit_Framework_TestCase;
use SlmMail\Service\AlphaMailService;
use SlmMailTest\Util\ServiceManagerFactory;
use Zend\Http\Response as HttpResponse;

class AlphaMailServiceTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var AlphaMailService
     */
    protected $service;

    public function setUp()
    {
        $this->service = new AlphaMailService('my-username', 'my-secret-key');
    }

    public function testCreateFromFactory()
    {
        $service = ServiceManagerFactory::getServiceManager()->get('SlmMail\Service\AlphaMailService');
        $this->assertInstanceOf('SlmMail\Service\AlphaMailService', $service);
    }
}
