<?php

namespace SlmMailTest\Service;

use Laminas\Http\Client as HttpClient;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mail\Address;
use Laminas\Mail\AddressList;
use Laminas\Mail\Message;
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

        $httpClientMock->expects($this->atLeastOnce())
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
        $sparkPostServiceMock->removeSendingDomain('sparkpost-sending-domain.com');
        $this->doesNotPerformAssertions();
    }

    public function testRemoveNonExistingSendingDomain()
    {
        /** @var SparkPostService $sparkPostServiceMock */
        $sparkPostServiceMock = $this->expectApiResponse(404);
        $sparkPostServiceMock->removeSendingDomain('sparkpost-sending-domain.com');
        $this->doesNotPerformAssertions();
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
        /** @var SparkPostService $sparkPostServiceMock */
        $sparkPostServiceMock = $this->expectApiResponse(
            404,
            '{"errors":[{"message":"invalid params","description":"Sending domain \'sparkpost-sending-domain.com\' is not a registered sending domain","code":"1200"}]}'
        );
        $this->expectException(RuntimeException::class);
        $sparkPostServiceMock->verifySendingDomain('sparkpost-sending-domain.com');
    }

    public function testAddToSuppressionList()
    {
        /** @var SparkPostService $sparkPostServiceMock */
        $sparkPostServiceMock = $this->expectApiResponse(200, '{"results":{"message":"Suppression List successfully updated"}}');
        $sparkPostServiceMock->addToSuppressionList('sender@sending-domain.com', 'Permanent block after hard bounce');
        $this->doesNotPerformAssertions();
    }

    public function testAddToTransactionalSuppressionList()
    {
        /** @var SparkPostService $sparkPostServiceMock */
        $sparkPostServiceMock = $this->expectApiResponse(200, '{"results":{"message":"Suppression List successfully updated"}}');
        $sparkPostServiceMock->addToSuppressionList('sender@sending-domain.com', 'Permanent block after hard bounce', [SparkPostService::SUPPRESSION_LIST_TRANSACTIONAL]);
        $this->doesNotPerformAssertions();
    }

    public function testRemoveFromSuppressionList()
    {
        /** @var SparkPostService $sparkPostServiceMock */
        $sparkPostServiceMock = $this->expectApiResponse(204);
        $sparkPostServiceMock->removeFromSuppressionList('sender@sending-domain.com');
        $this->doesNotPerformAssertions();
    }

    public function testRemoveFromTransactionalSuppressionList()
    {
        /** @var SparkPostService $sparkPostServiceMock */
        $sparkPostServiceMock = $this->expectApiResponse(204);
        $sparkPostServiceMock->removeFromSuppressionList('sender@sending-domain.com', [SparkPostService::SUPPRESSION_LIST_TRANSACTIONAL]);
        $this->doesNotPerformAssertions();
    }

    public function testRemoveNonExistingAddressFromSuppressionList()
    {
        /** @var SparkPostService $sparkPostServiceMock */
        $sparkPostServiceMock = $this->expectApiResponse(404);
        $sparkPostServiceMock->removeFromSuppressionList('sender@sending-domain.com');
        $this->doesNotPerformAssertions();
    }

    public function testSendBulkMail()
    {
        $message = $this->getMessageObject();
        $message->addTo('second@slmmail.com');
        $message->addTo('third@slmmail.com');

        /** @var SparkPostService $sparkPostServiceMock */
        $sparkPostServiceMock = $this->expectApiResponse(200);
        $sparkPostServiceMock->send($message);
        $this->doesNotPerformAssertions();
    }

    public function testSendAttachment()
    {
        $base64image = '/9j/4AAQSkZJRgABAQEAAAAAAAD/4QBORXhpZgAATU0AKgAAAAgAAYdpAAQAAAABAAAAGgAAAAAA
A6ABAAMAAAABAAEAAKACAAQAAAABAAAAKKADAAQAAAABAAAAKAAAAAAAAP/bAEMAAQEBAQEBAgEB
AgMCAgIDBAMDAwMEBQQEBAQEBQYFBQUFBQUGBgYGBgYGBgcHBwcHBwgICAgICQkJCQkJCQkJCf/b
AEMBAQEBAgICBAICBAkGBQYJCQkJCQkJCQkJCQkJCQkJCQkJCQkJCQkJCQkJCQkJCQkJCQkJCQkJ
CQkJCQkJCQkJCf/AABEIACgAKAMBIgACEQEDEQH/xAAfAAABBQEBAQEBAQAAAAAAAAAAAQIDBAUG
BwgJCgv/xAC1EAACAQMDAgQDBQUEBAAAAX0BAgMABBEFEiExQQYTUWEHInEUMoGRoQgjQrHBFVLR
8CQzYnKCCQoWFxgZGiUmJygpKjQ1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5
eoOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4eLj
5OXm5+jp6vHy8/T19vf4+fr/xAAfAQADAQEBAQEBAQEBAAAAAAAAAQIDBAUGBwgJCgv/xAC1EQAC
AQIEBAMEBwUEBAABAncAAQIDEQQFITEGEkFRB2FxEyIygQgUQpGhscEJIzNS8BVictEKFiQ04SXx
FxgZGiYnKCkqNTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqCg4SFhoeIiYqS
k5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2dri4+Tl5ufo6ery8/T1
9vf4+fr/3QAEAAP/2gAMAwEAAhEDEQA/AP68f+Ck3/BSb9n3/gmD+z7J8cvjlJPfXV9ONP0DQNPA
fUdZ1FwSltbIegHWWU/LGv8AeZkR/wAOPBv7FP8AwWw/4K82Ufxd/b6+Luqfsv8Awz1iPzNO+G3g
BntdbazlOQNVvmIeOV49paObz+chra3OY6T9inwbZf8ABXn/AILYfF39vr4uxx6x8M/2X9UbwB8N
dOkzLZtrVq5a91UA/u3ljcedGxXP7+2YHNvGa/rPoA/mA/4hKf8AgmVt+3/8JP8AEz+2v+gz/wAJ
Iv27/vv7J5fXn/V9a8w8ZfsU/wDBbD/gkNZSfFz9gX4u6p+1B8M9Hj8zUfht4/Z7rW1s4jkjSr5S
XklSPcVjh8jnAW2uDiOv6xL6+sdLsZtT1OZLe2t0aWWWVgiRogLMzMxAVVAySTgCvyA/4JRf8FNf
G3/BT3xH8Z/iR4d8IwaT8G/CXiSPw/4F8QiSX7Tr4tkk+33MkTgKsYPkPCVAwspjbMkb4APoP/gm
z/wUm/Z9/wCCnv7Psfxy+Bsk9jdWM50/X9A1ABNR0bUUGXtrlB1B6xSj5ZF9GDon6DV/Jj+2t4Ns
v+CQ3/BbD4Rft9fCOOPR/hn+1Bqi+APiTp0eYrNdbunD2WqkD92ksjnzpGC5/cXLE5uJDX9Gf/Da
H7KP/Q/6L/4FJQB//9D9eP8Ag0p2/wDDsnxN9vx/bX/CzPEn9s/9f2203+/+r8vrzX69/tJ/8FOv
2Sv2Q/2nvh5+yn+0Rq914Y1n4nwTS6Hqt5bGPRXmhkWIWs1+xEcU8jMAqkbRld7IZIw/4bfsU+Mr
L/gkN/wWw+Lv7Avxdkj0f4Z/tQao3j/4a6jJmKzXWrpyl7pQJ/dpLI58mNS2f3FsoGbiMV/Qh+3F
+wv+zf8A8FD/ANn/AFT9nH9p3Q01fQ9QHmW86YS9067VSIryynIYw3EeThsFWUskivGzowB+M/8A
wcIftTfFLxXpPgb/AII7fsf3Ofi9+0rcf2deyxE50fwqCw1G8n2/Msc8aSxscHNvHdYw6pn9xP2P
/wBlj4WfsTfsz+DP2WPgxbfZ/D3gzTo7CBmAElxIMvPdTY4M1zMzzSkcF3bAA4r8cf8Agj//AMEQ
fGf/AAT6+Pnjv9pn9p34kSfGLxve2Vt4V8Jaxeee0+m+F7FEWKFxcF/LuJVjijdY2dI0iARz5sgr
+iCgD+YD/g7W2/8ADsrwz9g/5DX/AAszw3/Y3/X9tu9nv/q/M6c1/Al/xm//AJ31/c9+2r4ysv8A
grz/AMFsPhF+wL8I5I9Y+Gf7L+qL4/8AiTqMeZbNtbtXCWOlEj928sbjyZFDZ/f3KkZt5BX9Gf8A
wxf+yj/0IGi/+AqUAf/R/rx/4KTf8E2f2ff+Cn37PsnwN+OUc9jdWM41DQNf08hNR0bUUBCXNs56
g9JYj8si/wB1lR0/Djwb+2t/wWw/4JDWUfwi/b6+EWqftQfDPR4/L074k+AFe61tbOI4B1WxYF5J
Uj2hpJvI5yWubg5kr+s6vmT9s/8A5NS+IH/YFuv/AECgD8G/+Itb/gmVt+wf8Ix8TP7a/wCgN/wj
a/bv++Ptfl9eP9Z1rzDxl+2t/wAFsP8AgrzZSfCL9gX4Rap+y/8ADPWI/L1H4k+P1e11trOU4J0q
xUB45Xj3BZIfP5wVubc4kr+GH/m9/wDz/fr/AF0f2MP+TUvh/wD9gW1/9AoA8U/4Js/8E2f2ff8A
gmF+z7H8DfgbHPfXV9OdQ1/X9QIfUdZ1Fxh7m5cdAOkUQ+WNf7zM7v8AoNRRQB//2Q==';

        /** @var SparkPost $message */
        $message = $this->getMessageObject();
        $message->addAttachment('file.jpg', 'image/jpg;base64', $base64image);

        /** @var SparkPostService $sparkPostServiceMock */
        $sparkPostServiceMock = $this->expectApiResponse(200);
        $sparkPostServiceMock->send($message);
        $this->doesNotPerformAssertions();
    }

    public function testCampaignId()
    {
        /** @var SparkPost $message */
        $message = $this->getMessageObject();

        // default value is null
        $this->assertNull($message->getCampaignId());

        // accepts null-value as a way to unset the Campaign ID
        $message->setCampaignId('non-null-value');
        $message->setCampaignId(null);
        $this->assertNull($message->getCampaignId());

        // nullify empty string
        $message->setCampaignId('');
        $this->assertNull($message->getCampaignId());

        // regular use
        $message->setCampaignId('sample-campaign');
        $this->assertEquals('sample-campaign', $message->getCampaignId());

        // truncation
        $message->setCampaignId('abcdefghijklmnopqrstuvwxyz0123456789abcdefghijklmnopqrstuvwxyz0123456789');
        $this->assertEquals('abcdefghijklmnopqrstuvwxyz0123456789abcdefghijklmnopqrstuvwxyz01', $message->getCampaignId());

        // successful transmission injection
        $sparkPostServiceMock = $this->expectApiResponse(200);
        $sparkPostServiceMock->send($message);
    }
}
