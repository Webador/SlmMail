<?php

namespace SlmMail\Service;

use SlmMail\Mail\Message\Provider\Mandrill as MandrillMessage;
use SlmMail\Service\AbstractMailService;
use Zend\Http\Request as HttpRequest;
use Zend\Http\Response as HttpResponse;
use Zend\Mail\Address;
use Zend\Mail\Message;

class MandrillService extends AbstractMailService
{
    /**
     * API endpoint
     */
    const API_ENDPOINT = 'https://mandrillapp.com/api/1.0/';

    /**
     * Mandrill API key
     *
     * @var string
     */
    protected $apiKey;

    /**
     * @param string $apiKey
     */
    public function __construct($apiKey)
    {
        $this->apiKey = (string) $apiKey;
    }

    /**
     * ------------------------------------------------------------------------------------------
     * MESSAGES
     * ------------------------------------------------------------------------------------------
     */

    /**
     * {@inheritDoc}
     */
    public function send(Message $message)
    {
        if ($message instanceof MandrillMessage && $message->getTemplate()) {
            return $this->sendTemplate($message);
        }

        $response = $this->prepareHttpClient('/messages/send.json', $this->parseMessage($message, false))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Set a message from a template stored at Mandrill
     *
     * @param  MandrillMessage $message
     * @return array
     * @throws Exception\RuntimeException
     */
    public function sendTemplate(MandrillMessage $message)
    {
        $response = $this->prepareHttpClient('/messages/send-template.json', $this->parseMessage($message, true))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * ------------------------------------------------------------------------------------------
     * USERS
     * ------------------------------------------------------------------------------------------
     */

    /**
     * Get the information about the API-connected user
     *
     * @return array
     */
    public function getUserInfo()
    {
        $response = $this->prepareHttpClient('/users/info.json')
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Validate an API key and respond to a ping
     *
     * @return array
     */
    public function pingUser()
    {
        $response = $this->prepareHttpClient('/users/ping.json')
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * SENDERS
     * ----------------------------------------------------------------------------------------------------
     */

    /**
     * Get the senders that have tried to use this account, both verified and unverified
     *
     * @return array
     */
    public function getSenders()
    {
        $response = $this->prepareHttpClient('/senders/list.json')
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get the sender domains that have been added to this account
     *
     * @return array
     */
    public function getSenderDomains()
    {
        $response = $this->prepareHttpClient('/senders/domains.json')
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get more detailed information about a single sender, including aggregates of recent stats
     *
     * @param  string $address
     * @return array
     */
    public function getSenderInfo($address)
    {
        $response = $this->prepareHttpClient('/senders/info.json', array('address' => $address))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get recent detailed information (last 30 days) about a single sender
     *
     * @param  string $address
     * @return array
     */
    public function getRecentSenderInfo($address)
    {
        $response = $this->prepareHttpClient('/senders/time-series.json', array('address' => $address))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * TAGS
     * ----------------------------------------------------------------------------------------------------
     */

    /**
     * Get all of the user-defined tag information
     *
     * @return array
     */
    public function getTags()
    {
        $response = $this->prepareHttpClient('/tags/list.json')
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Delete a tag permanently
     *
     * @param  string $tag
     * @return array
     */
    public function deleteTag($tag)
    {
        $response = $this->prepareHttpClient('/tags/delete.json', array('tag' => $tag))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get more detailed information about a single tag, including aggregates of recent stats
     *
     * @param string $tag
     * @return array
     */
    public function getTagInfo($tag)
    {
        $response = $this->prepareHttpClient('/tags/info.json', array('tag' => $tag))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get recent detailed information (last 30 days) about a single tag, including aggregates of recent stats
     *
     * @param string $tag
     * @return array
     */
    public function getRecentTagInfo($tag)
    {
        $response = $this->prepareHttpClient('/tags/time-series.json', array('tag' => $tag))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * REJECTION
     * ----------------------------------------------------------------------------------------------------
     */

    /**
     * Get all the email rejection blacklist
     *
     * @param  string $email
     * @param  bool $includeExpired
     * @return array
     */
    public function getRejectionBlacklist($email, $includeExpired = false)
    {
        $response = $this->prepareHttpClient('/rejects/list.json', array('email' => $email, 'include_expired' => $includeExpired))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Deletes an email rejection. There is no limit to how many rejections you can remove from your blacklist,
     * but keep in mind that each deletion has an affect on your reputation
     *
     * @param  string $email
     * @return array
     */
    public function deleteRejection($email)
    {
        $response = $this->prepareHttpClient('/rejects/list.json', array('email' => $email))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * URLS
     * ----------------------------------------------------------------------------------------------------
     */

    /**
     * Get the 100 most clicked URLs optionally filtered by search query
     *
     * @param  string $query
     * @return array
     */
    public function getMostClickedUrls($query = '')
    {
        if (empty($query)) {
            $response = $this->prepareHttpClient('/urls/list.json')
                             ->send();
        } else {
            $response = $this->prepareHttpClient('/urls/search.json', array('q' => $query))
                             ->send();
        }

        return $this->parseResponse($response);
    }

    /**
     * Get the recent history (hourly stats for the last 30 days) for a url
     *
     * @param  string $url
     * @return array
     */
    public function getRecentUrlInfo($url)
    {
        $response = $this->prepareHttpClient('/url/time-series.json', array('url' => $url))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * TEMPLATES
     * ----------------------------------------------------------------------------------------------------
     */

    /**
     * Add a new template to Mandrill
     *
     * @param  string       $name
     * @param  Address|null $address
     * @param  string       $subject
     * @param  string       $html
     * @param  string       $text
     * @return array
     */
    public function addTemplate($name, Address $address = null, $subject = '', $html = '', $text = '')
    {
        $parameters = array(
            'name'       => $name,
            'from_email' => ($address !== null) ? $address->getEmail() : '',
            'from_name'  => ($address !== null) ? $address->getName() : '',
            'subject'    => $subject,
            'code'       => $html,
            'text'       => $text
        );

        $response = $this->prepareHttpClient('/templates/add.json', $parameters)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Update an existing template
     *
     * @param  string  $name
     * @param  Address $address
     * @param  string  $subject
     * @param  string  $html
     * @param  string  $text
     * @return array
     */
    public function updateTemplate($name, Address $address = null, $subject = '', $html = '', $text = '')
    {
        $parameters = array(
            'name'       => $name,
            'from_email' => ($address !== null) ? $address->getEmail() : '',
            'from_name'  => ($address !== null) ? $address->getName() : '',
            'subject'    => $subject,
            'code'       => $html,
            'text'       => $text
        );

        $response = $this->prepareHttpClient('/templates/update.json', $parameters)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Delete a template
     *
     * @param  string $name
     * @return array
     */
    public function deleteTemplate($name)
    {
        $response = $this->prepareHttpClient('/templates/delete.json', array('name' => $name))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get all registered templates on Mandrill
     *
     * @return array
     */
    public function getTemplates()
    {
        $response = $this->prepareHttpClient('/templates/list.json')
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get template info
     *
     * @param  string $name
     * @return array
     */
    public function getTemplateInfo($name)
    {
        $response = $this->prepareHttpClient('/templates/info.json', array('name' => $name))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get recent template info (last 30 days)
     *
     * @param  string $name
     * @return array
     */
    public function getRecentTemplateInfo($name)
    {
        $response = $this->prepareHttpClient('/templates/time-series.json', array('name' => $name))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Render an existing template stored on Mandrill
     *
     * @param  string $name
     * @param  array $content
     * @param  array $variables
     * @return array
     */
    public function renderTemplate($name, array $content, array $variables = array())
    {
        $parameters = array(
            'template_name'    => $name,
            'template_content' => $content,
            'merge_vars'       => $variables
        );

        $response = $this->prepareHttpClient('/templates/render.json', $parameters)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * @param  Message $message
     * @param  bool $isTemplate
     * @throws Exception\RuntimeException
     * @return array
     */
    private function parseMessage(Message $message, $isTemplate)
    {
        $from = $message->getFrom();
        if (($isTemplate && count($from) > 1) || (!$isTemplate && count($from) !== 1)) {
            throw new Exception\RuntimeException(
                'Mandrill API requires only one from sender (or none if send with a template)'
            );
        }

        $from = $from->current();

        $parameters['message'] = array(
            'subject'    => $message->getSubject(),
            'text'       => $this->extractText($message),
            'html'       => $this->extractHtml($message),
            'from_email' => ($from ? $from->getEmail() : ''),
            'from_name'  => ($from ? $from->getName() : '')
        );

        foreach ($message->getTo() as $to) {
            $parameters['message']['to'][] = array(
                'email' => $to->getEmail(),
                'name'  => $to->getName()
            );
        }

        foreach ($message->getHeaders() as $header) {
            $parameters['message']['headers'][] = $header->toString();
        }

        if ($message instanceof MandrillMessage) {
            if ($isTemplate) {
                $parameters['template_name']    = $message->getTemplate();
                $parameters['template_content'] = $message->getTemplateContent();

                $parameters['message']['global_merge_vars'] = $message->getGlobalVariables();

                foreach ($message->getVariables() as $recipient => $variables) {
                    $parameters['message']['merge_vars'][] = array(
                        'rcpt' => $recipient,
                        'vars' => $variables
                    );
                }
            }

            foreach ($message->getTags() as $tag) {
                $parameters['message']['tags'][] = $tag;
            }

            foreach ($message->getAttachments() as $attachment) {
                $parameters['message']['attachments'][] = array(
                    'type'    => $attachment->getContentType(),
                    'name'    => $attachment->getName(),
                    'content' => $attachment->getContent()
                );
            }

            foreach ($message->getImages() as $image) {
                $parameters['message']['images'][] = array(
                    'type'    => $image->getContentType(),
                    'name'    => $image->getName(),
                    'content' => $image->getContent()
                );
            }
        }

        return $parameters;
    }

    /**
     * @param string $uri
     * @param array $parameters
     * @return \Zend\Http\Client
     */
    private function prepareHttpClient($uri, array $parameters = array())
    {
        $parameters = array_merge(array('key' => $this->apiKey), $parameters);

        return $this->getClient()
                    ->setMethod(HttpRequest::METHOD_POST)
                    ->setUri(self::API_ENDPOINT . $uri)
                    ->setParameterPost(array_filter($parameters));
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

        switch($result['name']) {
            case 'InvalidKey':
                throw new Exception\InvalidCredentialsException($result['message'], $result['code']);
            case 'ValidationError':
                throw new Exception\ValidationErrorException($result['message'], $result['code']);
            default:
                throw new Exception\RuntimeException($result['message'], $result['code']);
        }
    }
}
