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

namespace SlmMailTest\Service;

use Laminas\Http\Client as HttpClient;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use SlmMail\Service\SendGridService;
use SlmMailTest\Util\ServiceManagerFactory;
use Laminas\Http\Response as HttpResponse;

class SendGridServiceTest extends TestCase
{
    /**
     * @var SendGridService
     */
    protected $service;

    protected function setUp(): void
    {
        $this->service = new SendGridService('my-username', 'my-secret-key');
    }

    public function testCreateFromFactory()
    {
        $service = ServiceManagerFactory::getServiceManager()->get('SlmMail\Service\SendGridService');
        $this->assertInstanceOf('SlmMail\Service\SendGridService', $service);
    }

    public function testResultIsProperlyParsed()
    {
        $payload = ['success' => 123];

        $method = new ReflectionMethod('SlmMail\Service\SendGridService', 'parseResponse');
        $method->setAccessible(true);

        $response = new HttpResponse();
        $response->setStatusCode(200);
        $response->setContent(json_encode($payload));

        $actual = $method->invoke($this->service, $response);
        $this->assertEquals($payload, $actual);
    }


    /** @dataProvider dataProviderTestPrepareHttpClientWithUsername */
    public function testPrepareHttpClientWithUsername(
        string $username,
        bool $expectHeaderAuthentication
    ): void {
        $apiKey = 'api_key';

        $httpClient = $this->createMock(HttpClient::class);
        $httpClient->method('resetParameters')
                   ->willReturn($httpClient);
        $httpClient->method('setMethod')
                   ->willReturn($httpClient);
        $httpClient->method('setUri')
                   ->willReturn($httpClient);
        $httpClient->method('setParameterGet')
                   ->willReturnCallback(function (array $query) use (
                        $username,
                        $apiKey,
                        $expectHeaderAuthentication,
                        $httpClient
                    ) {
                        if ($expectHeaderAuthentication) {
                            self::assertFalse(isset($query['api_user']));
                            self::assertFalse(isset($query['api_key']));
                        } else {
                            self::assertEquals($username, $query['api_user']);
                            self::assertNotEmpty($apiKey, $query['api_key']);
                        }

                        return $httpClient;
                    });

        if ($expectHeaderAuthentication) {
            $httpClient->expects(self::once())
                       ->method('setHeaders')
                       ->with([
                           'Authorization' => sprintf('Bearer %s', $apiKey),
                       ]);
        } else {
            $httpClient->expects(self::never())->method('setHeaders');
        }

        $service = new SendGridService($username, $apiKey);
        $service->setClient($httpClient);
        $method = new ReflectionMethod($service, 'prepareHttpClient');
        $method->setAccessible(true);

        $method->invoke($service, '/mail.send.json', []);
    }

    public static function dataProviderTestPrepareHttpClientWithUsername(): array
    {
        return [
            'with username'    => ['my-user', false],
            'without username' => ['', true],
        ];
    }

    /**
     * @dataProvider exceptionDataProvider
     */
    public function testExceptionsAreThrownOnErrors($statusCode, $payload, $expectedException, $expectedExceptionMessage)
    {
        $method = new ReflectionMethod(SendGridService::class, 'parseResponse');
        $method->setAccessible(true);

        $response = new HttpResponse();
        $response->setContent($payload);
        $response->setStatusCode($statusCode);

        if ($expectedException !== null) {
            $this->expectException($expectedException);
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        $actual = $method->invoke($this->service, $response);
        $this->assertNull($actual);
    }

    public function exceptionDataProvider()
    {
        return [
            [
                400,
                'some jiberish, non-JSON',
                'SlmMail\Service\Exception\RuntimeException',
                'An error occured on SendGrid (http code 400), could not interpret result as JSON. Body: some jiberish, non-JSON'
            ],
            [
                400,
                json_encode(['errors' => 'some error message']),
                'SlmMail\Service\Exception\RuntimeException',
                'An error occured on SendGrid (http code 400), message: Unknown error'
            ],
            [
                401,
                json_encode(['errors' => 'some error message']),
                'SlmMail\Service\Exception\RuntimeException',
                'An error occured on SendGrid (http code 401), message: Unknown error'
            ],
            [
                402,
                json_encode(['errors' => 'some error message']),
                'SlmMail\Service\Exception\RuntimeException',
                'An error occured on SendGrid (http code 402), message: Unknown error'
            ],
            [
                500,
                json_encode(['errors' => 'some error message']),
                'SlmMail\Service\Exception\RuntimeException',
                'SendGrid server error, please try again'
            ],
        ];
    }
}
