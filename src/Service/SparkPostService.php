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
use SlmMail\Service\Exception\RuntimeException;
use SlmMail\Mail\Message\SparkPost as SparkPostMessage;

class SparkPostService extends AbstractMailService
{
    /**
     * API endpoint
     */
    protected const API_ENDPOINT = 'https://api.eu.sparkpost.com/api/v1';

    /**
     * SparkPost API key
     * @var string $apiKey
     */
    protected $apiKey;

    public const SUPPRESSION_LIST_TRANSACTIONAL = 'transactional';
    public const SUPPRESSION_LIST_NON_TRANSACTIONAL = 'non_transactional';
    public const SUPPRESSION_LISTS = [
        self::SUPPRESSION_LIST_TRANSACTIONAL,
        self::SUPPRESSION_LIST_NON_TRANSACTIONAL,
    ];

    /**
     * Constructor
     */
    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    private function validateDkimConfig(array $dkimConfig): void
    {
        if (!is_array($dkimConfig)) {
            throw new RuntimeException(
                'Invalid SparkPost DKIM-configuration object, expected an associative array'
            );
        }

        foreach(['public', 'private', 'selector'] as $keyName) {
            if (!isset($dkimConfig[$keyName])) {
                throw new RuntimeException(
                    'SparkPost DKIM-configuration contains an error: Missing value for "' . $keyName . '".'
                );
            }

            if (!is_string($dkimConfig[$keyName])) {
                throw new RuntimeException(
                    'SparkPost DKIM-configuration contains an error: Invalid type for "' . $keyName . '", expected a string.'
                );
            }
        }
    }

    /**
     * Send a message via the SparkPost Transmissions API
     */
    public function send(Message $message): array
    {
        $recipients = $this->prepareRecipients($message);

        if (count($recipients) == 0) {
            throw new RuntimeException(
                sprintf(
                    '%s transport expects at least one recipient',
                    __CLASS__
                )
            );
        }

        $options = $message instanceof SparkPostMessage ? $message->getOptions() : [];
        $headers = [];

        if(array_key_exists('subaccount', $options) && is_string($options['subaccount'])) {
            $headers['X-MSYS-SUBACCOUNT'] = $options['subaccount'];
        }

        // Prepare POST-body
        $post = $recipients;
        $post['content'] = $this->prepareContent($message);
        $post['options'] = $options;
        $post['metadata'] = $this->prepareMetadata($message);

        if($message instanceof SparkPostMessage && $message->getGlobalVariables()) {
            $post['substitution_data'] = $message->getGlobalVariables();
        }

        if($message instanceof SparkPostMessage && $message->getCampaignId()) {
            $post['campaign_id'] = $message->getCampaignId();
        }

        if($message instanceof SparkPostMessage && $message->getReturnPath()) {
            $post['return_path'] = $message->getReturnPath();
        }

        $response = $this->prepareHttpClient('/transmissions', $post, $headers)
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

        if($message instanceof SparkPostMessage && count($message->getAttachments()) > 0) {
            $content['attachments'] = $message->getAttachments();
        }

        return $content;
    }

    /**
     * Retrieve From address from Message and format it according to
     * the structure that the SparkPost API expects
     *
     * @param  Message $message
     *
     * @throws RuntimeException
     * @return array
     */
    protected function prepareFromAddress(Message $message): array
    {
        $sender = $message->getSender();

        if (!($sender instanceof AddressInterface)) {
            // Per RFC 2822 3.6
            throw new RuntimeException(
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
        $recipients = [];
        $recipients['recipients'] = $this->prepareAddresses($message->getTo(), $message);

        $message->getTo()->rewind();
        $firstToAddress = $message->getTo()->current()->getEmail();

        $ccRecipients = $this->prepareAddresses($message->getCc(), $message);

        foreach($ccRecipients as $ccRecipient) {
            $ccRecipient['address']['header_to'] = $firstToAddress;
            $recipients['recipients'][] = $ccRecipient;
        }

        $bccRecipients = $this->prepareAddresses($message->getBcc(), $message);

        foreach ($bccRecipients as $bccRecipient) {
            $bccRecipient['address']['header_to'] = $firstToAddress;
            $recipients['recipients'][] = $bccRecipient;
        }

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
            'MIME-Version',
        ];

        foreach ($removeTheseHeaders as $headerName) {
            $headers->removeHeader($headerName);
        }

        if ($message->getCc()->count() === 0) {
            $headers->removeHeader('Cc');
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

    public function registerSendingDomain(string $domain, array $options = []): bool
    {
        $headers = [];

        if(array_key_exists('subaccount', $options) && is_string($options['subaccount'])) {
            $headers['X-MSYS-SUBACCOUNT'] = $options['subaccount'];
        }

        $post = [
            'domain' => urlencode($domain),
        ];

        if (array_key_exists('dkim', $options)) {
            $this->validateDkimConfig($options['dkim']);
            $post['dkim'] = $options['dkim'];
        }

        $response = $this->prepareHttpClient('/sending-domains', $post, $headers)
            ->send()
        ;

        // A 409-status means that the domains is already registered, which we consider a 'successful' result
        $results = $this->parseResponse($response, [409]);

        if($results && isset($results['results']) && isset($results['results']['message'])
            && ($results['results']['message'] === 'Successfully Created domain.')) {
            return true;
        }

        if ($response->getStatusCode() === 409) {
            return true;
        }

        return false;
    }

    /**
     * Add an email address to the suppression lists for transactional email, non-transactional email, or both
     */
    public function addToSuppressionList(string $emailAddress, string $reason, array $suppressionLists = self::SUPPRESSION_LISTS, array $options = []): void
    {
        $headers = [];

        if(array_key_exists('subaccount', $options) && is_string($options['subaccount'])) {
            $headers['X-MSYS-SUBACCOUNT'] = $options['subaccount'];
        }

        $put = [
            'recipients' => [],
        ];

        foreach($suppressionLists as $suppressionList) {
            if(in_array($suppressionList, self::SUPPRESSION_LISTS)) {
                $put['recipients'][] = array(
                    'recipient' => $emailAddress,
                    'type' => $suppressionList,
                    'description' => $reason,
                );
            }
        }

        if ($put['recipients']) {
            $response = $this->prepareHttpClient('/suppression-list/', $put, $headers)
                ->setMethod(HttpRequest::METHOD_PUT)
                ->send();

            $this->parseResponse($response);
        }
    }

    /**
     * Remove an email address from the suppression lists for transactional email, non-transactional email, or both
     */
    public function removeFromSuppressionList(string $emailAddress, array $suppressionLists = self::SUPPRESSION_LISTS, $options = []): void
    {
        $headers = [];

        if(array_key_exists('subaccount', $options) && is_string($options['subaccount'])) {
            $headers['X-MSYS-SUBACCOUNT'] = $options['subaccount'];
        }

        foreach($suppressionLists as $suppressionList) {
            if (in_array($suppressionList, self::SUPPRESSION_LISTS)) {
                $delete = [
                    'type' => $suppressionList,
                ];

                $response = $this->prepareHttpClient('/suppression-list/' . urlencode($emailAddress), $delete, $headers)
                    ->setMethod(HttpRequest::METHOD_DELETE)
                    ->send();

                $this->parseResponse($response, [403, 404]);
            }
        }
    }

    public function verifySendingDomain(string $domain, array $options = []): bool
    {
        $headers = [];

        if(array_key_exists('subaccount', $options) && is_string($options['subaccount'])) {
            $headers['X-MSYS-SUBACCOUNT'] = $options['subaccount'];
        }

        $dkimVerify = array_key_exists('dkim_verify', $options) && $options['dkim_verify'] === true;
        $post = [];

        if ($dkimVerify) {
            $post['dkim_verify'] = true;
        }

        $response = $this->prepareHttpClient(sprintf('/sending-domains/%s/verify', urlencode($domain)), $post, $headers)
            ->send()
        ;

        $results = $this->parseResponse($response);

        if (!$results || !isset($results['results'])) {
            return false;
        }

        if ($dkimVerify) {
            if (!isset($results['results']['dkim_status'])) {
                return false;
            }

            if ($results['results']['dkim_status'] !== 'valid') {
                return false;
            }
        }

        return true;
    }

    public function removeSendingDomain(string $domain, array $options = []): void
    {
        $headers = [];

        if(array_key_exists('subaccount', $options) && is_string($options['subaccount'])) {
            $headers['X-MSYS-SUBACCOUNT'] = $options['subaccount'];
        }

        $response = $this->prepareHttpClient(sprintf('/sending-domains/%s', urlencode($domain)), [], $headers)
            ->setMethod(HttpRequest::METHOD_DELETE)
            ->send()
        ;

        // When a 404-status is returned, the domains wasn't found, which is considered as a 'successful' response too
        $this->parseResponse($response, [404]);
    }

    /**
     * @param string $uri
     * @param array $parameters API-call parameters, given associative array. Will be converted to JSON in the request.
     * @param array $headers Optional extra headers. This function will always add/overwrite Authorization
     *                       and Content-Type headers regardless of what was given in this parameter.
     * @return HttpClient
     */
    private function prepareHttpClient(string $uri, array $parameters = [], array $headers = []): HttpClient
    {
        $parameters = json_encode($parameters);
        $allHeaders = $headers;
        $allHeaders['Authorization'] = $this->apiKey;
        $allHeaders['Content-Type'] = 'application/json';

        $client = $this->getClient()
            ->resetParameters()
            ->setMethod(HttpRequest::METHOD_POST)
            ->setUri(self::API_ENDPOINT . $uri)
            ->setRawBody($parameters)
        ;
        $client->setHeaders($allHeaders);

        return $client;
    }

    /**
     * @param HttpResponse $response
     * @param int[] $successCodes
     * @throws RuntimeException
     * @return array
     */
    private function parseResponse(HttpResponse $response, array $successCodes = []): array
    {
        if ($response->getBody()) {
            $result = json_decode($response->getBody(), true);
        } else {
            $result = []; // represent an empty body by an empty array; json_decode would fail on an empty string
        }

        if (!is_array($result)) {
            throw new RuntimeException(sprintf(
                'An error occurred on Sparkpost (http code %s), could not interpret result as JSON. Body: %s',
                $response->getStatusCode(),
                $response->getBody()
            ));
        }

        if ($response->isSuccess() || in_array($response->getStatusCode(), $successCodes)) {
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

            throw new RuntimeException(
                sprintf(
                    'An error occurred on SparkPost (http code %s), messages: %s',
                    $response->getStatusCode(),
                    $message
                )
            );
        }

        // There is a 5xx error
        throw new RuntimeException('SparkPost server error, please try again');
    }

    public function previewTemplate(
        string $templateId, 
        array $substitutionVariables = [], 
        array $options = [], 
        bool $useDraftIfAvailable = true
    ): array
    {
        $headers = [];

        if(array_key_exists('subaccount', $options) && is_string($options['subaccount'])) {
            $headers['X-MSYS-SUBACCOUNT'] = $options['subaccount'];
        }

        // Request: POST/api/v1/templates/{id}/preview{?draft} ; with POST body given as JSON: { "substitution_data": { "key1": "value1", "key2": "value2" } }
        // Example: POST /api/v1/templates/11714265276872/preview?draft=true
        $post = [
            'substitution_data' => $substitutionVariables,
        ];

        $response = $this->prepareHttpClient(
            sprintf(
                '/templates/%s/preview?%s',
                urlencode($templateId),
                http_build_query([
                    'draft' => $useDraftIfAvailable ? 'true' : 'false',
                ]),
            ),
            $post,
            $headers
        )->send();

        return $this->parseResponse($response)['results'] ?? [];
    }
}
