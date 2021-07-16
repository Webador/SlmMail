<?php

/**
 * Created by PhpStorm.
 * User: niki
 * Date: 11/15/18
 * Time: 2:30 PM
 */

namespace SlmMail\Service;

use Laminas\Http\Client as HttpClient;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mail\Address\AddressInterface;
use Laminas\Mail\Message;
use SlmMail\Mail\Message\SparkPost as SparkPostMessage;

class SparkPostService extends AbstractMailService
{
    /**
     * API endpoint
     */
    protected const API_ENDPOINT = 'https://api.eu.sparkpost.com/api/v1';

    /**
     * SparkPost API key
     */
    protected string $apiKey;

    /**
     * Constructor
     */
    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Send a message via the SparkPost Transmissions API
     */
    public function send(Message $message): array
    {
        $recipients = $this->prepareRecipients($message);

        if (count($recipients) == 0) {
            throw new Exception\RuntimeException(
                sprintf(
                    '%s transport expects at least one recipient',
                    __CLASS__
                )
            );
        }

        // Prepare POST-body
        $post = $recipients;
        $post['content'] = $this->prepareContent($message);
        $post['options'] = $message instanceof SparkPostMessage ? $message->getOptions() : [];
        $post['metadata'] = $this->prepareMetadata($message);

        if($message instanceof SparkPostMessage && $message->getGlobalVariables()) {
            $post['substitution_data'] = $message->getGlobalVariables();
        }

        $response = $this->prepareHttpClient('/transmissions', $post)
            ->send()
        ;

        return $this->parseResponse($response);
    }

    /**
     * Prepare the 'content' structure for the SparkPost Transmission call
     */
    protected function prepareContent(Message $message): array
    {
        $content = [
            'from' => $this->prepareFromAddress($message),
            'subject' => $message->getSubject(),
        ];

        if ($message instanceof SparkPostMessage && $message->getTemplateId()) {
            $content['template_id'] = $message->getTemplateId();
        } else {
            $content['html'] = $this->prepareBody($message);
        }

        if ($message->getHeaders()) {
            $content['headers'] = $this->prepareHeaders($message);
        }

        if ($message->getReplyTo()) {
            $replyToList = $message->getReplyTo();
            $replyToList->rewind();
            $replyToAddress = $replyToList->current();

            if($replyToAddress instanceof AddressInterface) {
                $content['reply_to'] = $replyToAddress->getName() ? $replyToAddress->toString() : $replyToAddress->getEmail();
            }
        }

        return $content;
    }

    /**
     * Retrieve From address from Message and format it according to
     * the structure that the SparkPost API expects
     *
     * @param  Message $message
     *
     * @throws Exception\RuntimeException
     * @return array
     */
    protected function prepareFromAddress(Message $message): array
    {
        $sender = $message->getSender();

        if (!($sender instanceof AddressInterface)) {
            // Per RFC 2822 3.6
            throw new Exception\RuntimeException(
                sprintf(
                    '%s transport expects either a Sender or at least one From address in the Message; none provided',
                    __CLASS__
                )
            );
        }

        $fromStructure = [];
        $fromStructure['email'] = $sender->getEmail();

        if ($sender->getName()) {
            $fromStructure['name'] = $sender->getName();
        }

        return $fromStructure;
    }

    /**
     * Prepare array of recipients (note: multiple keys are used to distinguish To/Cc/Bcc-lists)
     */
    protected function prepareRecipients(Message $message): array
    {
        #if ($this->getEnvelope() && $this->getEnvelope()->getTo()) {
        #    return (array) $this->getEnvelope()->getTo();
        #}

        $recipients = [];
        $recipients['recipients'] = $this->prepareAddresses($message->getTo(), $message);
        //preparing email recipients we set $recipients['xx'] to be equal to prepareAddress() for different messages
        !($cc = $this->prepareAddresses($message->getCc(), $message)) || $recipients['cc'] = $cc;
        !($bcc = $this->prepareAddresses($message->getBcc(), $message)) || $recipients['bcc'] = $bcc;

        return $recipients;
    }

    /**
     * Prepare an addressee-sub structure based on (a subset of) addresses from a corresponding message
     */
    protected function prepareAddresses($addresses, $message): array
    {
        $recipients = [];

        foreach ($addresses as $address) {
            $recipient = []; // will contain addressee-block and optional substitution_data-block

            // Format address-block
            $addressee = [];
            $addressee['email'] = $address->getEmail();

            if ($address->getName()) {
                $addressee['name'] = $address->getName();
            }

            $recipient['address'] = $addressee;

            // Format optional substitution_data-block
            if ($message instanceof SparkPostMessage && $message->getVariables())
            {
                // Array of recipient-specific substitution variables indexed by email address
                $substitutionVariables = $message->getVariables();

                if (array_key_exists($addressee['email'], $substitutionVariables)) {
                    $recipient['substitution_data'] = $substitutionVariables[$addressee['email']];
                }
            }

            $recipients[] = $recipient;
        }

        return $recipients;
    }

    /**
     * Prepare header structure from message
     */
    protected function prepareHeaders(Message $message): array
    {
        $headers = clone $message->getHeaders();

        $removeTheseHeaders = [
            'Bcc',
            'Subject',
            'From',
            'To',
            'Reply-To',
            'Content-Type',
            'Content-Transfer-Encoding',
        ];

        foreach ($removeTheseHeaders as $headerName) {
            $headers->removeHeader($headerName);
        }

        return $headers->toArray();
    }

    /**
     * Prepare the 'metadata' structure for the SparkPost Transmission call
     */
    protected function prepareMetadata(Message $message): array
    {
        $metadata = [];

        if ($message->getSubject()) {
            $metadata['subject'] = $message->getSubject();
        }

        if ($message->getSender()) {
            $sender = $message->getSender();

            if($sender instanceof AddressInterface) {
                $metadata['from'] = [];
                $metadata['from']['email'] = $sender->getEmail();
                $metadata['from']['name'] = $sender->getName() ?: $sender->getEmail();
            }
        }

        if ($message->getReplyTo()) {
            $replyToList = $message->getReplyTo();
            $replyToList->rewind();
            $replyToAddress = $replyToList->current();

            if ($replyToAddress instanceof AddressInterface) {
                \Logger::info('Reply-to: ' + $replyToAddress->toString());
                $metadata['reply_to'] = $replyToAddress->getName() ? $replyToAddress->toString() : $replyToAddress->getEmail();
            }
        }

        return $metadata;
    }

    /**
     * Prepare body string from message
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
        return $this->getClient()
            ->resetParameters()
            ->setHeaders([
                'Authorization' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->setMethod(HttpRequest::METHOD_POST)
            ->setUri(self::API_ENDPOINT . $uri)
            ->setRawBody($parameters)
        ;
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

        if (!is_array($result)) {
            throw new Exception\RuntimeException(sprintf(
                'An error occurred on Sparkpost (http code %s), could not interpret result as JSON. Body: %s',
                $response->getStatusCode(),
                $response->getBody()
            ));
        }

        if ($response->isSuccess()) {
            return $result;
        }

        // There is a 4xx error
        if ($response->isClientError()) {
            if (isset($result['errors']) && is_array($result['errors'])) {
                $message = implode(', ', array_map(
                    function ($error) {
                        return $error['message'];
                    },
                    $result['errors']
                ));
            } elseif (isset($result['error'])) {
                $message = $result['error'];
            } else {
                $message = 'Unknown error';
            }

            throw new Exception\RuntimeException(
                sprintf(
                    'An error occurred on SparkPost (http code %s), messages: %s',
                    $response->getStatusCode(),
                    $message
                )
            );
        }

        // There is a 5xx error
        throw new Exception\RuntimeException('SparkPost server error, please try again');
    }
}
