<?php

namespace SlmMailTest\Service;

use Laminas\Http\Client as HttpClient;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mail\Address;
use Laminas\Mail\AddressList;
use Laminas\Mail\Message;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use SlmMail\Mail\Message\SparkPost;
use SlmMail\Service\Exception\RuntimeException;
use SlmMail\Service\SparkPostService;
use SlmMailTest\Util\ServiceManagerFactory;

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

    /** Stub the HTTP response from SparkPost with a custom response */
    private function expectApiResponse(int $statusCode = 200, string $responseBody = '', array $responseHeaders = []): SparkPostService
    {

        $httpClientMock = $this->createPartialMock(HttpClient::class, [
            'send'
        ]);

        $sendMessageResponse = new HttpResponse();
        $sendMessageResponse->setStatusCode($statusCode);
        if ($responseHeaders) {
            $sendMessageResponse->setHeaders($responseHeaders);
        }
        $sendMessageResponse->setContent($responseBody);

        $httpClientMock->expects($this->once())
            ->method('send')
            ->willReturn($sendMessageResponse);

        $sparkPostServiceMock = new SparkPostService('MyApiKey');
        $sparkPostServiceMock->setClient($httpClientMock);

        return $sparkPostServiceMock;
    }

    private function getMessageObject(): Message
    {
        $message = new SparkPost();
        $toAddress = new Address('to-address@sparkpost-test.com');
        $fromAddress = new Address('from-address@sparkpost-test.com');

        $to = new AddressList();
        $to->add($toAddress);

        $from = new AddressList();
        $from->add($fromAddress);

        $message->setFrom($from);
        $message->setTo($to);
        $message->setSubject('Test-email');
        $message->setBody('Content of the test-email.');

        return $message;
    }

    public function testCreateFromFactory()
    {
        $service = ServiceManagerFactory::getServiceManager()->get('SlmMail\Service\SparkPostService');
        $this->assertInstanceOf('SlmMail\Service\SparkPostService', $service);
    }

    public function testResultIsProperlyParsed()
    {
        $payload = ['success' => 123];

        $method = new ReflectionMethod('SlmMail\Service\SparkPostService', 'parseResponse');
        $method->setAccessible(true);

        $response = new HttpResponse();
        $response->setStatusCode(200);
        $response->setContent(json_encode($payload));

        $actual = $method->invoke($this->service, $response);
        $this->assertEquals($payload, $actual);
    }

    public function exceptionDataProvider(): array
    {
        return [
            [400, '{"name":"UnknownError","message":"An error occurred on SparkPost (http code 400), message: Unknown error", "code":4}', 'SlmMail\Service\Exception\RuntimeException'],
            [500, '{"name":"GeneralError","message":"SparkPost server error, please try again", "code":4}', 'SlmMail\Service\Exception\RuntimeException'],
            [204, '', null, []], // An empty 204-response should not throw an exception
        ];
    }

    /**
     * @dataProvider exceptionDataProvider
     */
    public function testExceptionsAreThrownOnErrors($statusCode, $content, $expectedException, $expectedResult = null)
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

        if ($expectedException === null) {
            $this->assertEquals($expectedResult, $actual);
        } else {
            $this->assertNull($actual);
        }
    }

    public function testSend()
    {
        $message = $this->getMessageObject();

        /** @var SparkPostService $sparkPostServiceMock */
        $sparkPostServiceMock = $this->expectApiResponse(
            200,
            '{"results":{"total_rejected_recipients":0,"total_accepted_recipients":1,"id":"11668787484950529"}}'
        );
        $sparkPostServiceMock->send($message);
    }

    public function testRegisterSendingDomain()
    {
        /** @var SparkPostService $sparkPostServiceMock */
        $sparkPostServiceMock = $this->expectApiResponse(
            200,
            '{"results":{"message":"Successfully Created domain.","domain":"sparkpost-sending-domain.com","headers":"from:to:subject:date"}}'
        );
        $this->assertTrue($sparkPostServiceMock->registerSendingDomain('sparkpost-sending-domain.com'));
    }

    public function testRegisterSendingDomainWithDkim(): void
    {
        $dkimConfig = [
            'public' => 'iAmAPublicKey',
            'private' => 'iAmAPrivateKey',
            'selector' => 'iAmASelector',
        ];

        /** @var SparkPostService $sparkPostServiceMock */
        $sparkPostServiceMock = $this->expectApiResponse(
            200,
            '{"results":{"message":"Successfully Created domain.","domain":"sparkpost-sending-domain.com","dkim":{"public":"iAmAPublicKey","selector":"iAmASelector","signing_domain":"sparkpost-sending-domain.com","headers":"from:to:subject:date"}}}'
        );
        $result = $sparkPostServiceMock->registerSendingDomain('sparkpost-sending-domain.com', array('dkim' => $dkimConfig));
        $this->assertTrue($result);
    }

    public function testRegisterSendingDomainWithIncompleteDkimConfig(): void
    {
        $dkimConfig = [
            'public' => 'iAmAPublicKey',
            // missing private key to test validation
            'selector' => 'iAmASelector',
        ];

        $this->expectException(RuntimeException::class);
        $this->service->registerSendingDomain('sparkpost-sending-domain.com', array('dkim' => $dkimConfig));
    }

    public function testRegisterExistingSendingDomain()
    {
        /** @var SparkPostService $sparkPostServiceMock */
        $sparkPostServiceMock = $this->expectApiResponse(409, '{"results":{"message":"resource conflict"}}');
        $this->assertTrue($sparkPostServiceMock->registerSendingDomain('sparkpost-sending-domain.com'));
    }

    public function testRemoveSendingDomain()
    {
        /** @var SparkPostService $sparkPostServiceMock */
        $sparkPostServiceMock = $this->expectApiResponse(204);
        $this->assertNull($sparkPostServiceMock->removeSendingDomain('sparkpost-sending-domain.com'));
    }

    public function testRemoveNonExistingSendingDomain()
    {
        /** @var SparkPostService $sparkPostServiceMock */
        $sparkPostServiceMock = $this->expectApiResponse(404);
        $this->assertNull($sparkPostServiceMock->removeSendingDomain('sparkpost-sending-domain.com'));
    }

    public function testVerifySendingDomain()
    {
        /** @var SparkPostService $sparkPostServiceMock */
        $sparkPostServiceMock = $this->expectApiResponse(
            200,
            '{"results":{"ownership_verified":true,"dkim_status":"unverified","cname_status":"unverified","mx_status":"unverified","compliance_status":"pending","spf_status":"unverified","abuse_at_status":"unverified","postmaster_at_status":"unverified","verification_mailbox_status":"unverified"}}'
        );
        $this->assertTrue($sparkPostServiceMock->verifySendingDomain('sparkpost-sending-domain.com'));
    }

    public function testVerifySendingDomainWithDkimRecord()
    {
        /** @var SparkPostService $sparkPostServiceMock */
        $sparkPostServiceMock = $this->expectApiResponse(
            200,
            '{"results":{"ownership_verified":true,"dns":{"dkim_record":"k=rsa; h=sha256; p=iAmApublicKey"},"dkim_status":"valid","cname_status":"unverified","mx_status":"unverified","compliance_status":"pending","spf_status":"unverified","abuse_at_status":"unverified","postmaster_at_status":"unverified","verification_mailbox_status":"unverified"}}'
        );
        $this->assertTrue($sparkPostServiceMock->verifySendingDomain('sparkpost-sending-domain.com', ['dkim_verify' => true]));
    }

    public function testVerifySendingDomainWithInvalidDkimRecord()
    {
        /** @var SparkPostService $sparkPostServiceMock */
        $sparkPostServiceMock = $this->expectApiResponse(
            200,
            '{"results":{"ownership_verified":true,"dns":{"dkim_record":"k=rsa; h=sha256; p=iAmApublicKey"},"dkim_status":"invalid","cname_status":"unverified","mx_status":"unverified","compliance_status":"pending","spf_status":"unverified","abuse_at_status":"unverified","postmaster_at_status":"unverified","verification_mailbox_status":"unverified"}}'
        );
        $this->assertFalse($sparkPostServiceMock->verifySendingDomain('sparkpost-sending-domain.com', ['dkim_verify' => true]));
    }

    public function testVerifyUnregisteredSendingDomain()
    {
        //** @var SparkPostService $sparkPostServiceMock */
        $sparkPostServiceMock = $this->expectApiResponse(
            404,
            '{"errors":[{"message":"invalid params","description":"Sending domain \'sparkpost-sending-domain.com\' is not a registered sending domain","code":"1200"}]}'
        );
        $this->expectException(RuntimeException::class);
        $sparkPostServiceMock->verifySendingDomain('sparkpost-sending-domain.com');
    }
}
