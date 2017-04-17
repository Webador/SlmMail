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

namespace SlmMail\Service;

use DateTime;
use DateTimeZone;
use SlmMail\Mail\Message\Mandrill as MandrillMessage;
use Zend\Http\Request  as HttpRequest;
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
     * @param  Message       $message
     * @param  DateTime|null $sendAt
     * @return array
     */
    public function send(Message $message, DateTime $sendAt = null)
    {
        if ($message instanceof MandrillMessage && $message->getTemplate()) {
            return $this->sendTemplate($message, $sendAt);
        }

        $response = $this->prepareHttpClient('/messages/send.json', $this->parseMessage($message, $sendAt))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Set a message from a template stored at Mandrill
     *
     * @link https://mandrillapp.com/api/docs/messages.html#method=send-template
     * @param  MandrillMessage $message
     * @param  DateTime|null   $sendAt
     * @return array
     */
    public function sendTemplate(MandrillMessage $message, DateTime $sendAt = null)
    {
        $response = $this->prepareHttpClient('/messages/send-template.json', $this->parseMessage($message, $sendAt))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get all the information about a message by its Mandrill id
     *
     * @link https://mandrillapp.com/api/docs/messages.JSON.html#method=info
     * @param  string $id
     * @return array
     */
    public function getMessageInfo($id)
    {
        $response = $this->prepareHttpClient('/messages/info.json', array('id' => $id))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get the scheduled messages, optionally filtered by an email To address
     *
     * @link https://mandrillapp.com/api/docs/messages.JSON.html#method=list-scheduled
     * @param  string $to
     * @return array
     */
    public function getScheduledMessages($to = '')
    {
        $response = $this->prepareHttpClient('/messages/list-scheduled.json', array('to' => $to))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Cancel a scheduled message using the mail identifier (you get it by from send methods or getScheduledMessages method)
     *
     * @link https://mandrillapp.com/api/docs/messages.JSON.html#method=cancel-scheduled
     * @param  string $id
     * @return array
     */
    public function cancelScheduledMessage($id)
    {
        $response = $this->prepareHttpClient('/messages/cancel-scheduled.json', array('id' => $id))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Reschedule an already scheduled message to a new date
     *
     * @link https://mandrillapp.com/api/docs/messages.JSON.html#method=reschedule
     * @param  string   $id
     * @param  DateTime $sendAt
     * @return array
     */
    public function rescheduleMessage($id, DateTime $sendAt)
    {
        // Mandrill needs to have date in UTC, using this format
        $sendAt->setTimezone(new DateTimeZone('UTC'));

        $parameters = array('id' => $id, 'send_at' => $sendAt->format('Y-m-d H:i:s'));

        $response = $this->prepareHttpClient('/messages/reschedule.json', $parameters)
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
     * ----------------------------------------------------------------------------------------------------
     * REJECTION (BLACKLIST)
     * ----------------------------------------------------------------------------------------------------
     */

    /**
     * Add an email to the rejection blacklist (optionally in a subaccount)
     *
     * @link https://mandrillapp.com/api/docs/rejects.html#method=add
     * @param string $email
     * @param string $subaccount
     * @param string $comment
     * @return array
     */
    public function addRejectionBlacklist($email, $subaccount = '', $comment = '')
    {
        $response = $this->prepareHttpClient('/rejects/add.json', compact('email', 'subaccount', 'comment'))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Deletes an email rejection blacklist. There is no limit to how many rejections you can remove from your
     * blacklist, but keep in mind that each deletion has an affect on your reputation
     *
     * @link https://mandrillapp.com/api/docs/rejects.html#method=delete
     * @param  string $email
     * @param  string $subaccount
     * @return array
     */
    public function deleteRejectionBlacklist($email, $subaccount = '')
    {
        $response = $this->prepareHttpClient('/rejects/delete.json', compact('email', 'subaccount'))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get all the email rejection blacklist
     *
     * @link https://mandrillapp.com/api/docs/rejects.html#method=list
     * @param  string $email
     * @param  bool   $includeExpired
     * @param  string $subaccount
     * @return array
     */
    public function getRejectionBlacklist($email = '', $includeExpired = false, $subaccount = '')
    {
        $parameters = array(
            'email'           => $email,
            'include_expired' => $includeExpired,
            'subaccount'      => $subaccount
        );

        $response = $this->prepareHttpClient('/rejects/list.json', $parameters)
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
     * SUB ACCOUNTS
     * ----------------------------------------------------------------------------------------------------
     */

    /**
     * Add a new subaccount to Mandrill
     *
     * @link https://mandrillapp.com/api/docs/subaccounts.JSON.html#method=add
     * @param  string   $id   Unique identifier (max of 255 characters)
     * @param  string   $name Optional display name to identify the subaccount (max of 1024 characters)
     * @param  string   $notes Optional text associated with the subaccount
     * @param  int|null $customQuota Optional manual hourly quota for subaccount (let null for letting Mandril decide)
     * @return array
     */
    public function addSubaccount($id, $name = '', $notes = '', $customQuota = null)
    {
        $parameters = array(
            'id'           => $id,
            'name'         => $name,
            'notes'        => $notes,
            'custom_quota' => $customQuota
        );

        $response = $this->prepareHttpClient('/subaccounts/add.json', $parameters)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Delete an existing subaccount from its identifier
     *
     * @link https://mandrillapp.com/api/docs/subaccounts.JSON.html#method=delete
     * @param  string $id Subaccount's unique identifier
     * @return array
     */
    public function deleteSubaccount($id)
    {
        $response = $this->prepareHttpClient('/subaccounts/delete.json', array('id' => $id))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get information about a given subaccount
     *
     * @link https://mandrillapp.com/api/docs/subaccounts.JSON.html#method=info
     * @param  string $id Subaccount's unique identifier
     * @return array
     */
    public function getSubaccountInfo($id)
    {
        $response = $this->prepareHttpClient('/subaccounts/info.json', array('id' => $id))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get all subaccounts, optionally filtered by a prefix
     *
     * @link https://mandrillapp.com/api/docs/subaccounts.JSON.html#method=list
     * @param  string $prefix Optional prefix to filter the subaccounts ids and names
     * @return array
     */
    public function getSubaccounts($prefix = '')
    {
        $response = $this->prepareHttpClient('/subaccounts/list.json', array('q' => $prefix))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Pause an existing subaccount
     *
     * @link https://mandrillapp.com/api/docs/subaccounts.JSON.html#method=pause
     * @param  string $id Subaccount's unique identifier
     * @return array
     */
    public function pauseSubaccount($id)
    {
        $response = $this->prepareHttpClient('/subaccounts/pause.json', array('id' => $id))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Resume a paused subaccount
     *
     * @link https://mandrillapp.com/api/docs/subaccounts.JSON.html#method=resume
     * @param  string $id Subaccount's unique identifier
     * @return array
     */
    public function resumeSubaccount($id)
    {
        $response = $this->prepareHttpClient('/subaccounts/resume.json', array('id' => $id))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Update an existing subaccount from Mandrill
     *
     * @link https://mandrillapp.com/api/docs/subaccounts.JSON.html#method=update
     * @param  string   $id   Unique identifier (max of 255 characters)
     * @param  string   $name Optional display name to identify the subaccount (max of 1024 characters)
     * @param  string   $notes Optional text associated with the subaccount
     * @param  int|null $customQuota Optional manual hourly quota for subaccount (let null for letting Mandril decide)
     * @return array
     */
    public function updateSubaccount($id, $name = '', $notes = '', $customQuota = null)
    {
        $parameters = array(
            'id'           => $id,
            'name'         => $name,
            'notes'        => $notes,
            'custom_quota' => $customQuota
        );

        $response = $this->prepareHttpClient('/subaccounts/update.json', $parameters)
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
     * @param  string $name
     * @param  Address|null $address
     * @param  string $subject
     * @param  string $html
     * @param  string $text
     * @param  array $labels
     * @return array
     * @throws Exception\RuntimeException If there are more than 10 template labels
     */
    public function addTemplate($name, Address $address = null, $subject = '',
                                $html = '', $text = '', array $labels = array())
    {
        if (count($labels) > 10) {
            throw new Exception\RuntimeException(
                'Mandrill only supports up to 10 template labels'
            );
        }

        $parameters = array(
            'name'       => $name,
            'from_email' => ($address !== null) ? $address->getEmail() : '',
            'from_name'  => ($address !== null) ? $address->getName() : '',
            'subject'    => $subject,
            'code'       => $html,
            'text'       => $text,
            'labels'     => $labels
        );

        $response = $this->prepareHttpClient('/templates/add.json', $parameters)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Update an existing template
     *
     * @link https://mandrillapp.com/api/docs/templates.html#method=update
     * @param  string $name
     * @param  Address $address
     * @param  string $subject
     * @param  string $html
     * @param  string $text
     * @param  array $labels
     * @return array
     * @throws Exception\RuntimeException If there are more than 10 template labels
     */
    public function updateTemplate($name, Address $address = null, $subject = '',
                                   $html = '', $text = '', array $labels = array())
    {
        if (count($labels) > 10) {
            throw new Exception\RuntimeException(
                'Mandrill only supports up to 10 template labels'
            );
        }

        $parameters = array(
            'name'       => $name,
            'from_email' => ($address !== null) ? $address->getEmail() : '',
            'from_name'  => ($address !== null) ? $address->getName() : '',
            'subject'    => $subject,
            'code'       => $html,
            'text'       => $text,
            'labels'     => $labels
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
     * Get all registered templates on Mandrill (optionally filtered by a label)
     *
     * @link https://mandrillapp.com/api/docs/templates.html#method=list
     * @param  string $label
     * @return array
     */
    public function getTemplates($label = '')
    {
        $response = $this->prepareHttpClient('/templates/list.json', array('label' => $label))
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
     * @param  Message       $message
     * @param  DateTime|null $sendAt
     * @throws Exception\RuntimeException
     * @return array
     */
    private function parseMessage(Message $message, DateTime $sendAt = null)
    {
        $hasTemplate = ($message instanceof MandrillMessage && null !== $message->getTemplate());

        $from = $message->getFrom();
        if (($hasTemplate && count($from) > 1) || (!$hasTemplate && count($from) !== 1)) {
            throw new Exception\RuntimeException(
                'Mandrill API requires exactly one from sender (or none if send with a template)'
            );
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
                'name'  => $to->getName(),
                'type'  => 'to'
            );
        }

        foreach ($message->getCc() as $cc) {
            $parameters['message']['to'][] = array(
                'email' => $cc->getEmail(),
                'name'  => $cc->getName(),
                'type'  => 'cc'
            );
        }

        foreach ($message->getBcc() as $bcc) {
            $parameters['message']['to'][] = array(
                'email' => $bcc->getEmail(),
                'name'  => $bcc->getName(),
                'type'  => 'bcc'
            );
        }

        $replyTo = $message->getReplyTo();

        if (count($replyTo) > 1) {
            throw new Exception\RuntimeException('Mandrill has only support for one Reply-To address');
        } elseif (count($replyTo)) {
            $parameters['message']['headers']['Reply-To'] = $replyTo->rewind()->toString();
        }

        if ($message instanceof MandrillMessage) {
            if ($hasTemplate) {
                $parameters['template_name'] = $message->getTemplate();

                foreach ($message->getTemplateContent() as $key => $value) {
                    $parameters['template_content'][] = array(
                        'name'    => $key,
                        'content' => $value
                    );
                }

                // Currently, Mandrill API requires a content for template_content, even if it is an
                // empty array
                if (!isset($parameters['template_content'])) {
                    $parameters['template_content'] = array();
                }
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

            $parameters['message']['metadata'] = $message->getGlobalMetadata();

            foreach ($message->getMetadata() as $recipient => $variables) {
                $parameters['message']['recipient_metadata'][] = array(
                    'rcpt'   => $recipient,
                    'values' => $variables
                );
            }

            foreach ($message->getOptions() as $key => $value) {
                $parameters['message'][$key] = $value;
            }

            foreach ($message->getTags() as $tag) {
                $parameters['message']['tags'][] = $tag;
            }

            foreach ($message->getImages() as $image) {
                $parameters['message']['images'][] = array(
                    'type'    => $image->type,
                    'name'    => $image->filename,
                    'content' => base64_encode($image->getRawContent())
                );
            }
        }

        $attachments = $this->extractAttachments($message);
        foreach ($attachments as $attachment) {
            $parameters['message']['attachments'][] = array(
                'type'    => $attachment->type,
                'name'    => $attachment->filename,
                'content' => base64_encode($attachment->getRawContent())
            );
        }

        if (null !== $sendAt) {
            // Mandrill needs to have date in UTC, using this format
            $sendAt->setTimezone(new DateTimeZone('UTC'));
            $parameters['send_at'] = $sendAt->format('Y-m-d H:i:s');
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
     * @throws Exception\UnknownTemplateException
     * @throws Exception\RuntimeException
     * @return array
     */
    private function parseResponse(HttpResponse $response)
    {
        $result = json_decode($response->getBody(), true);

        if ($response->isSuccess()) {
            return $result;
        }

        switch ($result['name']) {
            case 'InvalidKey':
                throw new Exception\InvalidCredentialsException(sprintf(
                    'Mandrill authentication error (code %s): %s', $result['code'], $result['message']
                ));
            case 'ValidationError':
                throw new Exception\ValidationErrorException(sprintf(
                    'An error occurred on Mandrill (code %s): %s', $result['code'], $result['message']
                ));
            case 'Unknown_Template':
                throw new Exception\UnknownTemplateException(sprintf(
                    'An error occurred on Mandrill (code %s): %s', $result['code'], $result['message']
                ));
            default:
                throw new Exception\RuntimeException(sprintf(
                    'An error occurred on Mandrill (code %s): %s', $result['code'], $result['message']
                ));
        }
    }
}
