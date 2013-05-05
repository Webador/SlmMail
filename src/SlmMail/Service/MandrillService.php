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
    const API_ENDPOINT = 'https://mandrillapp.com/api/1.0';

    /**
     * Mandrill API key
     *
     * @var string
     */
    protected $apiKey;

    /**
     * Valid Mandrill options
     *
     * @var array
     */
    protected $validOptions = array(
        'important',
        'track_opens',
        'track_clicks',
        'auto_text',
        'auto_html',
        'inline_css',
        'url_strip_qs',
        'preserve_recipients',
        'tracking_domain',
        'signing_domain',
        'merge',
        'google_analytics_domains',
        'google_analytics_campaign'
    );

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
     * @link https://mandrillapp.com/api/docs/messages.html#method=send
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
     * @link https://mandrillapp.com/api/docs/messages.html#method=send-template
     * @param  MandrillMessage $message
     * @return array
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
     * @link https://mandrillapp.com/api/docs/users.html#method=info
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
     * @link https://mandrillapp.com/api/docs/users.html#method=ping
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
     * @link https://mandrillapp.com/api/docs/senders.html#method=list
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
     * @link https://mandrillapp.com/api/docs/senders.html#method=domains
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
     * @link https://mandrillapp.com/api/docs/senders.html#method=info
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
     * @link https://mandrillapp.com/api/docs/senders.html#method=time-series
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
     * @link https://mandrillapp.com/api/docs/tags.html#method=list
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
     * @link https://mandrillapp.com/api/docs/tags.html#method=delete
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
     * @link https://mandrillapp.com/api/docs/tags.html#method=info
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
     * @link https://mandrillapp.com/api/docs/tags.html#method=time-series
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
     * REJECTION (BLACKLIST)
     * ----------------------------------------------------------------------------------------------------
     */

    /**
     * Add an email to the rejection blacklist
     *
     * @link https://mandrillapp.com/api/docs/rejects.html#method=add
     * @param string $email
     * @return array
     */
    public function addRejectionBlacklist($email)
    {
        $response = $this->prepareHttpClient('/rejects/add.json', array('email' => $email))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Deletes an email rejection blacklist. There is no limit to how many rejections you can remove from your
     * blacklist, but keep in mind that each deletion has an affect on your reputation
     *
     * @link https://mandrillapp.com/api/docs/rejects.html#method=delete
     * @param  string $email
     * @return array
     */
    public function deleteRejectionBlacklist($email)
    {
        $response = $this->prepareHttpClient('/rejects/delete.json', array('email' => $email))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get all the email rejection blacklist
     *
     * @link https://mandrillapp.com/api/docs/rejects.html#method=list
     * @param  string $email
     * @param  bool $includeExpired
     * @return array
     */
    public function getRejectionBlacklist($email = '', $includeExpired = false)
    {
        $response = $this->prepareHttpClient('/rejects/list.json', array('email' => $email, 'include_expired' => $includeExpired))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * REJECTION (WHITELIST)
     * ----------------------------------------------------------------------------------------------------
     */

    /**
     * Add an email to the rejection whitelist
     *
     * @link https://mandrillapp.com/api/docs/whitelists.html#method=add
     * @param string $email
     * @return array
     */
    public function addRejectionWhitelist($email)
    {
        $response = $this->prepareHttpClient('/whitelists/add.json', array('email' => $email))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Deletes an email rejection whitelist
     *
     * @link https://mandrillapp.com/api/docs/whitelists.html#method=delete
     * @param  string $email
     * @return array
     */
    public function deleteRejectionWhitelist($email)
    {
        $response = $this->prepareHttpClient('/whitelists/delete.json', array('email' => $email))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get all the email rejection whitelist
     *
     * @link https://mandrillapp.com/api/docs/whitelists.html#method=list
     * @param  string $email
     * @return array
     */
    public function getRejectionWhitelist($email = '')
    {
        $response = $this->prepareHttpClient('/whitelists/list.json', array('email' => $email))
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
     * @link https://mandrillapp.com/api/docs/urls.html#method=list
     * @link https://mandrillapp.com/api/docs/urls.html#method=search
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
     * @link https://mandrillapp.com/api/docs/urls.html#method=time-series
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
     * @link https://mandrillapp.com/api/docs/templates.html#method=add
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
     * @link https://mandrillapp.com/api/docs/templates.html#method=update
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
     * @link https://mandrillapp.com/api/docs/templates.html#method=delete
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
     * @link https://mandrillapp.com/api/docs/templates.html#method=list
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
     * @link https://mandrillapp.com/api/docs/templates.html#method=info
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
     * @link https://mandrillapp.com/api/docs/templates.html#method=time-series
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
     * @link https://mandrillapp.com/api/docs/templates.html#method=render
     * @param  string $name
     * @param  array $content
     * @param  array $variables
     * @return array
     */
    public function renderTemplate($name, array $content = array(), array $variables = array())
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
                'Mandrill API requires exactly one from sender (or none if send with a template)'
            );
        }

        if (count($message->getCc())) {
            throw new Exception\RuntimeException('Mandrill does not support CC addresses');
        }

        if (count($message->getBcc())) {
            throw new Exception\RuntimeException('Mandrill does not support BCC addresses');
        }

        $from = $from->rewind();

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

        $replyTo = $message->getReplyTo();
        if (count($replyTo) > 1) {
            throw new Exception\RuntimeException('Mandrill has only support for one Reply-To address');
        } elseif (count($replyTo)) {
            $parameters['message']['headers']['Reply-To'] = $replyTo->rewind()->toString();
        }

        if ($message instanceof MandrillMessage) {
            if ($isTemplate) {
                $parameters['template_name'] = $message->getTemplate();

                foreach ($message->getTemplateContent() as $key => $value) {
                    $parameters['template_content'] = array(
                        'name'    => $key,
                        'content' => $value
                    );
                }

                // Currently, Mandrill API requires a content for template_content, even if it is an
                // empty array
                if (!isset($parameters['template_content'])) {
                    $parameters['template_content'] = array();
                }

                foreach ($message->getGlobalVariables() as $key => $value) {
                    $parameters['message']['global_merge_vars'][] = array(
                        'name'    => $key,
                        'content' => $value
                    );
                }

                foreach ($message->getVariables() as $recipient => $variables) {
                    $recipientVariables = array();

                    foreach ($variables as $key => $value) {
                        $recipientVariables[] = array(
                            'name'    => $key,
                            'content' => $value
                        );
                    }

                    $parameters['message']['merge_vars'][] = array(
                        'rcpt' => $recipient,
                        'vars' => $recipientVariables
                    );
                }
            }

            foreach ($message->getOptions() as $key => $value) {
                if (in_array($key, $this->validOptions)) {
                    $parameters['message'][$key] = $value;
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

        $client = $this->getClient()->resetParameters();
        $client->getRequest()
               ->getHeaders()
               ->addHeaderLine('Content-Type', 'application/json');

        return $client->setMethod(HttpRequest::METHOD_POST)
                      ->setUri(self::API_ENDPOINT . $uri)
                      ->setRawBody(json_encode($this->filterParameters($parameters)));
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
                throw new Exception\InvalidCredentialsException(sprintf(
                    'Mandrill authentication error (code %s): %s', $result['code'], $result['message']
                ));
            case 'ValidationError':
                throw new Exception\ValidationErrorException(sprintf(
                    'An error occurred on Mandrill (code %s): %s', $result['code'], $result['message']
                ));
            default:
                throw new Exception\RuntimeException(sprintf(
                    'An error occurred on Mandrill (code %s): %s', $result['code'], $result['message']
                ));
        }
    }
}
