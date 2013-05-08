<?php

namespace SlmMail\Service;

use SlmMail\Mail\Message\Provider\Mailgun as MailgunMessage;
use SlmMail\Service\AbstractMailService;
use Zend\Http\Request as HttpRequest;
use Zend\Http\Response as HttpResponse;
use Zend\Mail\Address;
use Zend\Mail\Message;

class MailgunService extends AbstractMailService
{
    /**
     * API endpoint
     */
    const API_ENDPOINT = 'https://api.mailgun.net/v2';

    /**
     * Mailgun domain to use
     *
     * @var string
     */
    protected $domain;

    /**
     * Mailgun API key
     *
     * @var string
     */
    protected $apiKey;

    /**
     * @var array
     */
    protected $validOptions = array(
        'dkim'            => 'o:dkim',
        'delivery_time'   => 'o:deliverytime',
        'test_mode'       => 'o:testmode',
        'tracking'        => 'o:tracking',
        'tracking_clicks' => 'o:tracking-clicks',
        'tracking_opens'  => 'o:tracking-opens'
    );

    /**
     * @param string $domain
     * @param string $apiKey
     */
    public function __construct($domain, $apiKey)
    {
        $this->domain = (string) $domain;
        $this->apiKey = (string) $apiKey;
    }

    /**
     * ------------------------------------------------------------------------------------------
     * MESSAGES
     * ------------------------------------------------------------------------------------------
     */

    /**
     * {@inheritDoc}
     * @link http://help.postageapp.com/kb/api/send_message
     * @return string id of message (if sent correctly)
     */
    public function send(Message $message)
    {
        $from = $message->getFrom();
        if (count($from) !== 1) {
            throw new Exception\RuntimeException(
                'Postage API requires exactly one from sender'
            );
        }

        $parameters = array(
            'from'    => $from->rewind()->toString(),
            'subject' => $message->getSubject(),
            'text'    => $this->extractText($message),
            'html'    => $this->extractHtml($message)
        );

        $to = array();
        foreach ($message->getTo() as $address) {
            $to[] = $address->toString();
        }

        $parameters['to'] = implode(',', $to);

        $cc = array();
        foreach ($message->getCc() as $address) {
            $cc[] = $address->toString();
        }

        $parameters['cc'] = implode(',', $cc);

        $bcc = array();
        foreach ($message->getBcc() as $address) {
            $bcc[] = $address->toString();
        }

        $parameters['bcc'] = implode(',', $bcc);

        if ($message instanceof MailgunMessage) {
            foreach ($message->getAttachments() as $attachment) {
                $parameters['attachment'][] = $attachment->getName();
            }

            foreach ($message->getOptions() as $key => $value) {
                if (array_key_exists($key, $this->validOptions)) {
                    $parameters[$this->validOptions[$key]] = $value;
                }
            }

            $parameters['o:tag'] = $message->getTags();
        }

        $client = $this->prepareHttpClient('/messages', $parameters);

        // Eventually add files. This cannot be done before prepareHttpClient call because prepareHttpClient
        // reset all parameters (response, request...), therefore we would loose the file upload
        if ($message instanceof MailgunMessage) {
            foreach ($message->getAttachments() as $attachment) {
                $this->getClient()->setFileUpload($attachment->getName(), 'attachment', $attachment->getContent(), $attachment->getContentType());
            }
        }

        $response = $client->send();

        $result = $this->parseResponse($response);
        return $result['id'];
    }

    /**
     * Get log entries
     *
     * @link   http://documentation.mailgun.com/api-logs.html
     * @param  int $limit
     * @param  int $offset
     * @return array
     */
    public function getLogs($limit = 100, $offset = 0)
    {
        $parameters = array('limit' => $limit, 'skip' => $offset);

        $response = $this->prepareHttpClient('/log')
                         ->setMethod(HttpRequest::METHOD_GET)
                         ->setParameterGet($this->filterParameters($parameters))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * ------------------------------------------------------------------------------------------
     * SPAM
     * ------------------------------------------------------------------------------------------
     */

    /**
     * Get spam complaints (this happens when recipients click "report spam")
     *
     * @link   http://documentation.mailgun.com/api-complaints.html
     * @param  int $limit
     * @param  int $offset
     * @return array
     */
    public function getSpamComplaints($limit = 100, $offset = 0)
    {
        $parameters = array('limit' => $limit, 'skip' => $offset);

        $response = $this->prepareHttpClient('/complaints')
                         ->setMethod(HttpRequest::METHOD_GET)
                         ->setParameterGet($this->filterParameters($parameters))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get a single spam complaint by a given email address. This is useful to check if a particular
     * user has complained
     *
     * @link   http://documentation.mailgun.com/api-complaints.html
     * @param  string $address
     * @return array
     */
    public function getSpamComplaint($address)
    {
        $response = $this->prepareHttpClient('/complaints/' . $address)
                         ->setMethod(HttpRequest::METHOD_GET)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Add an address to the complaints table
     *
     * @link   http://documentation.mailgun.com/api-complaints.html
     * @param  string $address
     * @return array
     */
    public function addSpamComplaint($address)
    {
        $response = $this->prepareHttpClient('/complaints', array('address' => $address))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Delete an address to the complaints table
     *
     * @link   http://documentation.mailgun.com/api-complaints.html
     * @param  string $address
     * @return array
     */
    public function deleteSpamComplaint($address)
    {
        $response = $this->prepareHttpClient('/complaints/' . $address)
                         ->setMethod(HttpRequest::METHOD_DELETE)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * ------------------------------------------------------------------------------------------
     * BOUNCES
     * ------------------------------------------------------------------------------------------
     */

    /**
     * Get bounces emails
     *
     * @link   http://documentation.mailgun.com/api-bounces.html
     * @param  int $limit
     * @param  int $offset
     * @return array
     */
    public function getBounces($limit = 100, $offset = 0)
    {
        $parameters = array('limit' => $limit, 'skip' => $offset);

        $response = $this->prepareHttpClient('/bounces')
                         ->setMethod(HttpRequest::METHOD_GET)
                         ->setParameterGet($this->filterParameters($parameters))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get a single bounce event by a given email address
     *
     * @link   http://documentation.mailgun.com/api-bounces.html
     * @param  string $address
     * @return array
     */
    public function getBounce($address)
    {
        $response = $this->prepareHttpClient('/bounces/' . $address)
                         ->setMethod(HttpRequest::METHOD_GET)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Add a bounce
     *
     * @link   http://documentation.mailgun.com/api-bounces.html
     * @param  string $address
     * @param  int $code
     * @param  string $error
     * @return array
     */
    public function addBounce($address, $code = 550, $error = '')
    {
        $response = $this->prepareHttpClient('/bounces', compact('address', 'code', 'error'))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Delete a bounce
     *
     * @link   http://documentation.mailgun.com/api-bounces.html
     * @param  string $address
     * @return array
     */
    public function deleteBounce($address)
    {
        $response = $this->prepareHttpClient('/bounces/' . $address)
                         ->setMethod(HttpRequest::METHOD_DELETE)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * @param string $uri
     * @param array $parameters
     * @return \Zend\Http\Client
     */
    private function prepareHttpClient($uri, array $parameters = array())
    {
        $client = $this->getClient()->resetParameters();
        $client->getRequest()
               ->getHeaders()
               ->addHeaderLine('Authorization', 'Basic ' . base64_encode('api:' . $this->apiKey));

        return $client->setMethod(HttpRequest::METHOD_POST)
                      ->setUri(self::API_ENDPOINT . $this->domain . $uri)
                      ->setParameterPost($this->filterParameters($parameters));
    }

    /**
     * @param  HttpResponse $response
     * @throws Exception\InvalidCredentialsException
     * @throws Exception\ValidationErrorException
     * @throws Exception\RuntimeException
     * @return array
     */
    private function parseResponse(HttpResponse $response)
    {
        $result = json_decode($response->getBody(), true);

        if ($response->isSuccess()) {
            return $result;
        }

        switch ($response->getStatusCode()) {
            case 400:
                throw new Exception\ValidationErrorException(sprintf(
                    'An error occured on Mailgun, reason: %s', $response->getReasonPhrase()
                ));
            case 401:
                throw new Exception\InvalidCredentialsException('Authentication error: missing or incorrect Mailgun authorization');
            case 402:
                throw new Exception\RuntimeException(sprintf(
                    'An error occured on Mailgun, reason: %s', $response->getReasonPhrase()
                ));
            case 500:
            case 502:
            case 503:
            case 504:
                throw new Exception\RuntimeException('Mailgun server error, please try again');
            default:
                throw new Exception\RuntimeException('Unknown error during request to Mailgun server');
        }
    }
}
