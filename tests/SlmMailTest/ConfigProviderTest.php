<?php
namespace SlmMail;

use SlmMail\Module;
use PHPUnit_Framework_TestCase;

class ConfigProviderTest extends PHPUnit_Framework_TestCase
{
    public function testConfigProviderGetConfig()
    {
        $config = (new \SlmMail\ConfigProvider())();

        $this->assertNotEmpty($config);
    }

    public function testConfigEqualsToModuleConfig()
    {
        $moduleConfig = (new Module())->getConfig();
        $config       = (new \SlmMail\ConfigProvider())();

        $this->assertEquals($moduleConfig['service_manager'], $config['dependencies']);
        $this->assertEquals($moduleConfig['slm_mail'], $config['slm_mail']);
    }
}