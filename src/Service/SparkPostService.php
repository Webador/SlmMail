<?php

/**
 * Created by PhpStorm.
 * User: niki
 * Date: 11/15/18
 * Time: 2:30 PM
 */

namespace SlmMail\Service;

use Laminas\Http\Client as HttpClient;
use Laminas\Http\Response;
use Laminas\Mail\Message;
use SlmMail\Mail\Message\SparkPost as SparkPostMessage;
use Laminas\Mail\Address;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response as HttpResponse;

class SparkPostService extends AbstractMailService
{
    /**
     * API endpoint
     */
    protected const API_ENDPOINT = 'https://api.eu.sparkpost.com/api/v1';

    /**
     * SparkPost API key
     *
     * @var string
     */
    protected $apiKey;

    /**
     * Constructor.
     *
     * @param string $apiKey
     */
    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }


    public function send(Message $message): array
    {
        if ($message instanceof SparkPostMessage) {
            $options = $message->getOptions();
        }

        $spec['api_key'] = $this->apiKey;

        // Prepare message
        $from = $this->prepareFromAddress($message);
        $recipients = $this->prepareRecipients($message);
        $headers = $this->prepareHeaders($message);
        $body = $this->prepareBody($message);

        $post = [
            'options' => $options,
            'content' => [
                'from' => $from,
                'subject' => $message->getSubject(),
                'html' => $body,
            ],
        ];

        $post = array_merge($post, $recipients);
        if ((count($recipients) == 0) && (!empty($headers) || !empty($body))) {
            throw new Exception\RuntimeException(
                sprintf(
                    '%s transport expects at least one recipient if the message has at least one header or body',
                    __CLASS__
                )
            );
        }

        $response = $this->prepareHttpClient('/transmissions', $post)
            ->send()
        ;

        return $this->parseResponse($response);
    }

    /**
     * Retrieve email address for envelope FROM
     *
     * @param  Message $message
     *
     * @throws Exception\RuntimeException
     * @return string
     */
    protected function prepareFromAddress(Message $message): string
    {
        #if ($this->getEnvelope() && $this->getEnvelope()->getFrom()) {
        #    return $this->getEnvelope()->getFrom();
        #}

        $sender = $message->getSender();
        if ($sender instanceof Address\AddressInterface) {
            return $sender->getEmail();
        }

        $from = $message->getFrom();
        if (!count($from)) {
            // Per RFC 2822 3.6
            throw new Exception\RuntimeException(
                sprintf(
                    '%s transport expects either a Sender or at least one From address in the Message; none provided',
                    __CLASS__
                )
            );
        }

        $from->rewind();
        $sender = $from->current();

        return $sender->getEmail();
    }

    /**
     * Prepare array of email address recipients
     *
     * @param  Message $message
     *
     * @return array
     */
    protected function prepareRecipients(Message $message): array
    {
        #if ($this->getEnvelope() && $this->getEnvelope()->getTo()) {
        #    return (array) $this->getEnvelope()->getTo();
        #}

        $recipients = [];
        $recipients['recipients'] = $this->prepareAddresses($message->getTo());
        //preparing email recipients we set $recipients['xx'] to be equal to prepareAddress() for different messages
        !($cc = $this->prepareAddresses($message->getCc())) || $recipients['cc'] = $cc;
        !($bcc = $this->prepareAddresses($message->getBcc())) || $recipients['bcc'] = $bcc;

        return $recipients;
    }

    protected function prepareAddresses($addresses)
    {
        $recipients = [];
        foreach ($addresses as $address) {
            $item = [];
            if ($address->getName()) {
                $item['name'] = $address->getName();
            }
            $recipients[]['address'] = $address->getEmail();
        }

        return $recipients;
    }

    /**
     * Prepare header string from message
     *
     * @param  Message $message
     *
     * @return string
     */
    protected function prepareHeaders(Message $message): string
    {
        $headers = clone $message->getHeaders();
        $headers->removeHeader('Bcc');

        return $headers->toString();
    }

    /**
     * Prepare body string from message
     *
     * @param  Message $message
     *
     * @return string
     */
    protected function prepareBody(Message $message): string
    {
        return $message->getBodyText();
    }

    /**
     * @param string $uri
     * @param array  $parameters
     * @return HttpClient
     */
    private function prepareHttpClient(string $uri, array $parameters = []): HttpClient
    {
        $parameters = json_encode($parameters);
        $return = $this->getClient()
            ->resetParameters()
            ->setHeaders(['Authorization' => $this->apiKey])
            ->setMethod(HttpRequest::METHOD_POST)
            ->setUri(self::API_ENDPOINT . $uri)
            ->setRawBody($parameters, 'application/json')
        ;

        return $return;
    }

    /**
     * @param  HttpResponse $response
     *
     * @throws Exception\RuntimeException
     * @return array
     */
    private function parseResponse(HttpResponse $response): array
    {
        $result = json_decode($response->getBody(), true);
        if ($response->isSuccess()) {
            return $result;
        }

        // There is a 4xx error
        if ($response->isClientError()) {
            if (isset($result['errors']) && is_array($result['errors'])) {
                $message = implode(', ', $result['errors']);
            } elseif (isset($result['error'])) {
                $message = $result['error'];
            } else {
                $message = 'Unknown error';
            }

            throw new Exception\RuntimeException(
                sprintf(
                    'An error occured on SparkPost (http code %s), message: %s',
                    $response->getStatusCode(),
                    $message
                )
            );
        }

        // There is a 5xx error
        throw new Exception\RuntimeException('SparkPost server error, please try again');
    }
}
