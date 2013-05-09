<?php

namespace SlmMail\Service;

use SlmMail\Mail\Message\Provider\SendGrid as SendGridMessage;
use SlmMail\Service\AbstractMailService;
use Zend\Http\Request as HttpRequest;
use Zend\Http\Response as HttpResponse;
use Zend\Mail\Address;
use Zend\Mail\Message;

class SendGridService extends AbstractMailService
{
    /**
     * API endpoint
     */
    const API_ENDPOINT = 'https://sendgrid.com/api';

    /**
     * SendGrid username
     *
     * @var string
     */
    protected $username;

    /**
     * SendGrid API key
     *
     * @var string
     */
    protected $apiKey;


    /**
     * @param string $username
     * @param string $apiKey
     */
    public function __construct($username, $apiKey)
    {
        $this->username = (string) $username;
        $this->apiKey   = (string) $apiKey;
    }

    /**
     * ------------------------------------------------------------------------------------------
     * MESSAGES
     * ------------------------------------------------------------------------------------------
     */

    /**
     * {@inheritDoc}
     * @link http://sendgrid.com/docs/API_Reference/Web_API/mail.html
     * @return mixed
     */
    public function send(Message $message)
    {
        $from = $message->getFrom();
        if (count($from) !== 1) {
            throw new Exception\RuntimeException(
                'SendGrid API requires exactly one from sender'
            );
        }

        if (count($message->getCc())) {
            throw new Exception\RuntimeException('SendGrid does not support CC addresses');
        }

        $parameters = array(
            'from'     => $from->rewind()->getEmail(),
            'fromname' => $from->rewind()->getName(),
            'subject'  => $message->getSubject(),
            'text'     => $this->extractText($message),
            'html'     => $this->extractHtml($message)
        );

        foreach ($message->getTo() as $address) {
            $parameters['to'][] = $address->getEmail();
        }

        foreach ($message->getBcc() as $address) {
            $parameters['bcc'][] = $address->getEmail();
        }

        $replyTo = $message->getReplyTo();
        if (count($replyTo) > 1) {
            throw new Exception\RuntimeException('SendGrid has only support for one Reply-To address');
        } elseif (count($replyTo)) {
            $parameters['replyto'] = $replyTo->rewind()->getEmail();
        }

        if ($message instanceof SendGridMessage) {
            foreach ($message->getAttachments() as $attachment) {
                $parameters['files'][] = $attachment->getName();
            }
        }

        $client = $this->prepareHttpClient('/mail.send.json', $parameters);

        // Eventually add files. This cannot be done before prepareHttpClient call because prepareHttpClient
        // reset all parameters (response, request...), therefore we would loose the file upload
        if ($message instanceof SendGridMessage) {
            foreach ($message->getAttachments() as $attachment) {
                $this->getClient()->setFileUpload($attachment->getName(), 'files', $attachment->getContent(), $attachment->getContentType());
            }
        }

        $response = $client->send();

        return $this->parseResponse($response);
    }

    /**
     * Get a list of bounces with addresses and response codes, optionally with dates
     *
     * @param  int    $date      must be 1 if you want to retrieve dates
     * @param  string $startDate if specified, must be in YYYY-MM-DD format and < $endDate
     * @param  string $endDate   if specified, must be in YYYY-MM-DD format and > $startDate
     * @param  bool   $aggregate true if you are interested in all-time totals
     * @return array
     */
    public function getStatistics($date = 1, $startDate = '', $endDate = '', $aggregate = false)
    {
        $parameters = array('date' => $date, 'start_date' => $startDate, 'end_date' => $endDate, 'aggregate' => (int)$aggregate);

        $response = $this->prepareHttpClient('/stats.get.json', $parameters)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * ------------------------------------------------------------------------------------------
     * BOUNCES
     * ------------------------------------------------------------------------------------------
     */

    /**
     * Get a list of bounces with addresses and response codes, optionally with dates
     *
     * @param  int    $date      must be 1 if you want to retrieve dates
     * @param  int    $days      if specified, must be superior to 0
     * @param  string $startDate if specified, must be in YYYY-MM-DD format and < $endDate
     * @param  string $endDate   if specified, must be in YYYY-MM-DD format and > $startDate
     * @param  string $email     optional email to search for
     * @param  int $limit        optional field to limit the number of returned results
     * @param  int $offset       optional beginning point to retrieve results
     * @return array
     */
    public function getBounces($date = 1, $days = 1, $startDate = '', $endDate = '', $email = '', $limit = 100, $offset = 0)
    {
        $parameters = array('date' => $date, 'days' => $days, 'start_date' => $startDate, 'end_date' => $endDate,
                            'email' => $email, 'limit' => $limit, 'offset' => $offset);

        $response = $this->prepareHttpClient('/bounces.get.json', $parameters)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Delete an address from the Bounce list. Note that if no parameters are specified the ENTIRE list will be deleted.
     *
     * @param  string $startDate if specified, must be in YYYY-MM-DD format and < $endDate
     * @param  string $endDate   if specified, must be in YYYY-MM-DD format and > $startDate
     * @param  string $email     optional email to search for
     * @return array
     */
    public function deleteBounces($startDate = '', $endDate = '', $email = '')
    {
        $parameters = array('start_date' => $startDate, 'end_date' => $endDate, 'email' => $email);

        $response = $this->prepareHttpClient('/bounces.delete.json', $parameters)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * @param  string $startDate if specified, must be in YYYY-MM-DD format and < $endDate
     * @param  string $endDate   if specified, must be in YYYY-MM-DD format and > $startDate
     * @return array
     */
    public function countBounces($startDate = '', $endDate = '')
    {
        $parameters = array('start_date' => $startDate, 'end_date' => $endDate);

        $response = $this->prepareHttpClient('/bounces.count.json', $parameters)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * ------------------------------------------------------------------------------------------
     * SPAMS
     * ------------------------------------------------------------------------------------------
     */

    /**
     * Retrieve and delete entries in the Spam Reports list
     *
     * @param  int    $date      must be 1 if you want to retrieve dates
     * @param  int    $days      if specified, must be superior to 0
     * @param  string $startDate if specified, must be in YYYY-MM-DD format and < $endDate
     * @param  string $endDate   if specified, must be in YYYY-MM-DD format and > $startDate
     * @param  string $email     optional email to search for
     * @param  int $limit        optional field to limit the number of returned results
     * @param  int $offset       optional beginning point to retrieve results
     * @return array
     */
    public function getSpamReports($date = 1, $days = 1, $startDate = '', $endDate = '', $email = '', $limit = 100, $offset = 0)
    {
        $parameters = array('date' => $date, 'days' => $days, 'start_date' => $startDate, 'end_date' => $endDate,
                            'email' => $email, 'limit' => $limit, 'offset' => $offset);

        $response = $this->prepareHttpClient('/spamreports.get.json', $parameters)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Delete an address from the Spam Reports list
     *
     * @param  string $email email to search for
     * @return array
     */
    public function deleteSpamReport($email = '')
    {
        $response = $this->prepareHttpClient('/spamreports.delete.json', array('email' => $email))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * ------------------------------------------------------------------------------------------
     * BLOCKS
     * ------------------------------------------------------------------------------------------
     */

    /**
     * Get a list of blocks with addresses and response codes, optionally with dates
     *
     * @link http://sendgrid.com/docs/API_Reference/Web_API/blocks.html
     * @param  int    $date      must be 1 if you want to retrieve dates
     * @param  int    $days      if specified, must be superior to 0
     * @param  string $startDate if specified, must be in YYYY-MM-DD format and < $endDate
     * @param  string $endDate   if specified, must be in YYYY-MM-DD format and > $startDate
     * @return array
     */
    public function getBlocks($date = 1, $days = 1, $startDate = '', $endDate = '')
    {
        $parameters = array(
            'date'       => $date,
            'days'       => $days,
            'start_date' => $startDate,
            'end_date'   => $endDate
        );

        $response = $this->prepareHttpClient('/blocks.get.json', $parameters)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Delete an address from the block list
     *
     * @link http://sendgrid.com/docs/API_Reference/Web_API/blocks.html
     * @param  string $email
     * @return array
     */
    public function deleteBlock($email)
    {
        $response = $this->prepareHttpClient('/blocks.delete', array('email' => $email))
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
        $parameters = array_merge(array('api_user' => $this->username, 'api_key' => $this->apiKey), $parameters);

        return $this->getClient()
                    ->resetParameters()
                    ->setMethod(HttpRequest::METHOD_GET)
                    ->setUri(self::API_ENDPOINT . $uri)
                    ->setParameterGet($this->filterParameters($parameters));
    }

    /**
     * @param  HttpResponse $response
     * @throws Exception\RuntimeException
     * @return array
     */
    private function parseResponse(HttpResponse $response)
    {
        $result = json_decode($response->getBody(), true);

        if ($response->isSuccess()) {
            return $result;
        }

        // There is a 4xx error
        if ($response->isClientError()) {
            if (isset($result->errors) && is_array($result->errors)) {
                $message = implode(', ', $result->errors);
            } elseif (isset($result->error)) {
                $message = $result->error;
            } else {
                $message = 'Unknown error';
            }

            throw new Exception\RuntimeException(sprintf(
                'An error occured on SendGrid (http code %s), message: %s', $response->getStatusCode(), $message
            ));
        }

        // There is a 5xx error
        throw new Exception\RuntimeException('SendGrid server error, please try again');
    }
}
