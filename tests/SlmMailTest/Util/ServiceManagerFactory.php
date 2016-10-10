<?php
/**
 * Copyright (c) 2012-2013 Jurian Sluiman.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the names of the copyright holders nor the names of the
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @author      Jurian Sluiman <jurian@juriansluiman.nl>
 * @copyright   2012-2013 Jurian Sluiman.
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link        http://juriansluiman.nl
 */

namespace SlmMailTest\Util;

use Zend\ServiceManager\ServiceManager;
use Zend\Mvc\Service\ServiceManagerConfig;

/**
 * Utility used to retrieve a freshly bootstrapped application's service manager
 *
 * @license MIT
 * @link    http://www.doctrine-project.org/
 * @author  Marco Pivetta <ocramius@gmail.com>
 */
class ServiceManagerFactory
{
    /**
     * @var array
     */
    protected static $config;

    /**
     * @param array $config
     */
    public static function setConfig(array $config)
    {
        // If this MVC v3 then used Zend\Router module instead Zend\Mvc\Router
        if (class_exists(\Zend\Router\Module::class)) {
            $config['modules'][] = 'Zend\\Router';
        }
        static::$config = $config;
    }

    /**
     * Builds a new service manager
     */
    public static function getServiceManager()
    {
        $serviceConfig = new ServiceManagerConfig(
            isset(static::$config['service_manager']) ? static::$config['service_manager'] : array()
        );
        // If this ServiceManager v3 then __constructor signature pass array instead object
        if (method_exists(new ServiceManager(), 'configure')) {
            $serviceConfig = $serviceConfig->toArray();
        }
        $serviceManager = new ServiceManager($serviceConfig);
        $serviceManager->setService('ApplicationConfig', static::$config);
        //$serviceManager->setFactory('ServiceListener', 'Zend\Mvc\Service\ServiceListenerFactory');
        $serviceManager->setAllowOverride(true);
        /** @var $moduleManager \Zend\ModuleManager\ModuleManager */
        $moduleManager = $serviceManager->get('ModuleManager');
        $moduleManager->loadModules();

        return $serviceManager;
    }
}
