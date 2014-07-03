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

use Aws\Ses\Exception\SesException;
use Aws\Ses\SesClient;
use Zend\Mail\Address;
use Zend\Mail\Message;

class SesService extends AbstractMailService
{
    /**
     * SES supports a maximum of 50 recipients per messages
     */
    const RECIPIENT_LIMIT = 50;

    /**
     * @param SesClient $client
     */
    public function __construct(SesClient $client)
    {
        $this->client = $client;
    }

    /**
     * ------------------------------------------------------------------------------------------
     * MESSAGES
     * ------------------------------------------------------------------------------------------
     */

    /**
     * {@inheritDoc}
     * @link http://help.postageapp.com/kb/api/send_message
     * @throws Exception\RuntimeException if the mail is sent to more than 50 recipients (Amazon SES limit)
     * @return array The id and UID of the sent message (if sent correctly)
     */
    public function send(Message $message)
    {
        $from = $message->getFrom();
        if (count($from) !== 1) {
            throw new Exception\RuntimeException(
                'Amazon SES requires exactly one from sender'
            );
        }

        $parameters = array(
            'Source'  => $from->rewind()->toString(),
            'Message' => array(
                'Subject' => array('Data' => $message->getSubject()),
            )
        );

        $textContent = $this->extractText($message);
        if (!empty($textContent)) {
            $parameters['Message']['Body']['Text']['Data'] = $textContent;
        }

        $htmlContent = $this->extractHtml($message);
        if (!empty($htmlContent)) {
            $parameters['Message']['Body']['Html']['Data'] = $htmlContent;
        }

        $countRecipients = count($message->getTo());

        $to = array();
        foreach ($message->getTo() as $address) {
            $to[] = $address->toString();
        }

        $parameters['Destination']['ToAddresses'] = $to;

        $countRecipients += count($message->getCc());

        $cc = array();
        foreach ($message->getCc() as $address) {
            $cc[] = $address->toString();
        }

        $parameters['Destination']['CcAddresses'] = $cc;

        $countRecipients += count($message->getBcc());

        $bcc = array();
        foreach ($message->getBcc() as $address) {
            $bcc[] = $address->toString();
        }

        $parameters['Destination']['BccAddresses'] = $bcc;

        if ($countRecipients > self::RECIPIENT_LIMIT) {
            throw new Exception\RuntimeException(sprintf(
                'You have exceeded limitation for Amazon SES count recipients (%s maximum, %s given)',
                self::RECIPIENT_LIMIT,
                $countRecipients
            ));
        }

        $replyTo = array();
        foreach ($message->getReplyTo() as $address) {
            $replyTo[] = $address->toString();
        }

        $parameters['ReplyToAddresses'] = $replyTo;

        try {
            return $this->client->sendEmail($this->filterParameters($parameters))->toArray();
        } catch (SesException $exception) {
            $this->parseException($exception);
        }
    }

    /**
     * Get the user's current sending limits
     *
     * @link http://docs.aws.amazon.com/ses/latest/APIReference/API_GetSendQuota.html
     * @return array
     */
    public function getSendQuota()
    {
        try {
            return $this->client->getSendQuota()->toArray();
        } catch (SesException $exception) {
            $this->parseException($exception);
        }
    }

    /**
     * Get the user's sending statistics. The result is a list of data points, representing the last two weeks
     * of sending activity
     *
     * @link http://docs.aws.amazon.com/ses/latest/APIReference/API_GetSendStatistics.html
     * @return array
     */
    public function getSendStatistics()
    {
        try {
            return $this->client->getSendStatistics()->toArray();
        } catch (SesException $exception) {
            $this->parseException($exception);
        }
    }

    /**
     * ------------------------------------------------------------------------------------------
     * IDENTITIES AND EMAILS
     * ------------------------------------------------------------------------------------------
     */

    /**
     * Get a list containing all of the identities (email addresses and domains) for a specific AWS Account,
     * regardless of verification status
     *
     * @link http://docs.aws.amazon.com/ses/latest/APIReference/API_ListIdentities.html
     * @param  string $identityType can be EmailAddress or Domain
     * @param  int    $maxItems can be between 1 and 100 inclusive
     * @param  string $nextToken token to use for pagination
     * @return array
     */
    public function getIdentities($identityType = '', $maxItems = 50, $nextToken = '')
    {
        $parameters = array(
            'IdentityType' => $identityType,
            'MaxItems'     => $maxItems,
            'NextToken'    => $nextToken
        );

        try {
            return $this->client->listIdentities(array_filter($parameters))->toArray();
        } catch (SesException $exception) {
            $this->parseException($exception);
        }
    }

    /**
     * Delete the specified identity (email address or domain) from the list of verified identities
     *
     * @link http://docs.aws.amazon.com/ses/latest/APIReference/API_DeleteIdentity.html
     * @param  string $identity
     * @return void
     */
    public function deleteIdentity($identity)
    {
        try {
            $this->client->deleteIdentity(array('Identity' => $identity));
        } catch (SesException $exception) {
            $this->parseException($exception);
        }
    }

    /**
     * Get the current status of Easy DKIM signing for an entity
     *
     * @link http://docs.aws.amazon.com/ses/latest/APIReference/API_GetIdentityDkimAttributes.html
     * @param  array $identities
     * @return array
     */
    public function getIdentityDkimAttributes(array $identities)
    {
        try {
            return $this->client->getIdentityDkimAttributes(array('Identities' => $identities))->toArray();
        } catch (SesException $exception) {
            $this->parseException($exception);
        }
    }

    /**
     * Given a list of verified identities (email addresses and/or domains), returns a structure describing
     * identity notification attributes
     *
     * @link http://docs.aws.amazon.com/ses/latest/APIReference/API_GetIdentityNotificationAttributes.html
     * @param  array $identities
     * @return array
     */
    public function getIdentityNotificationAttributes(array $identities)
    {
        try {
            return $this->client->getIdentityNotificationAttributes(array('Identities' => $identities))->toArray();
        } catch (SesException $exception) {
            $this->parseException($exception);
        }
    }

    /**
     * Given a list of identities (email addresses and/or domains), returns the verification status and (for domain
     * identities) the verification token for each identity
     *
     * @link http://docs.aws.amazon.com/ses/latest/APIReference/API_GetIdentityVerificationAttributes.html
     * @param  array $identities
     * @return array
     */
    public function getIdentityVerificationAttributes(array $identities)
    {
        try {
            return $this->client->getIdentityVerificationAttributes(array('Identities' => $identities))->toArray();
        } catch (SesException $exception) {
            $this->parseException($exception);
        }
    }

    /**
     * Enable or disable Easy DKIM signing of email sent from an identity
     *
     * @link http://docs.aws.amazon.com/ses/latest/APIReference/API_SetIdentityDkimEnabled.html
     * @param  string $identity
     * @param  bool $dkimEnabled
     * @return void
     */
    public function setIdentityDkimEnabled($identity, $dkimEnabled)
    {
        try {
            $this->client->setIdentityDkimEnabled(array(
                'Identity'    => $identity,
                'DkimEnabled' => (bool) $dkimEnabled
            ));
        } catch (SesException $exception) {
            $this->parseException($exception);
        }
    }

    /**
     * Given an identity (email address or domain), enables or disables whether Amazon SES forwards feedback
     * notifications as email
     *
     * @link http://docs.aws.amazon.com/ses/latest/APIReference/API_SetIdentityFeedbackForwardingEnabled.html
     * @param  string $identity
     * @param  bool $forwardingEnabled
     * @return void
     */
    public function setIdentityFeedbackForwardingEnabled($identity, $forwardingEnabled)
    {
        try {
            $this->client->setIdentityFeedbackForwardingEnabled(array(
                'Identity'          => $identity,
                'ForwardingEnabled' => (bool) $forwardingEnabled
            ));
        } catch (SesException $exception) {
            $this->parseException($exception);
        }
    }

    /**
     * Given an identity (email address or domain), sets the Amazon SNS topic to which Amazon SES will publish bounce
     * and complaint notifications for emails sent with that identity as the Source
     *
     * @link http://docs.aws.amazon.com/ses/latest/APIReference/API_SetIdentityNotificationTopic.html
     * @param string $identity
     * @param string $notificationType
     * @param string $snsTopic
     * @return void
     */
    public function setIdentityNotificationTopic($identity, $notificationType, $snsTopic = '')
    {
        $parameters = array(
            'Identity'         => $identity,
            'NotificationType' => $notificationType,
            'SnsTopic'         => $snsTopic
        );

        try {
            $this->client->setIdentityNotificationTopic(array_filter($parameters));
        } catch (SesException $exception) {
            $this->parseException($exception);
        }
    }

    /**
     * Get a set of DKIM tokens for a domain
     *
     * DKIM tokens are character strings that represent your domain's identity. Using these tokens, you will need to
     * create DNS CNAME records that point to DKIM public keys hosted by Amazon SES. This action is throttled at
     * one request per second.
     *
     * @link http://docs.aws.amazon.com/ses/latest/APIReference/API_VerifyDomainDkim.html
     * @param  string $domain
     * @return array
     */
    public function verifyDomainDkim($domain)
    {
        try {
            return $this->client->verifyDomainDkim(array('Domain' => $domain))->toArray();
        } catch (SesException $exception) {
            $this->parseException($exception);
        }
    }

    /**
     * Verifies a domain identity
     *
     * @link http://docs.aws.amazon.com/ses/latest/APIReference/API_VerifyDomainIdentity.html
     * @param string $domain
     * @return void
     */
    public function verifyDomainIdentity($domain)
    {
        try {
            $this->client->verifyDomainIdentity(array('Domain' => $domain));
        } catch (SesException $exception) {
            $this->parseException($exception);
        }
    }

    /**
     * Verify an email address. This action causes a confirmation email message to be sent to the specified address
     *
     * @link http://docs.aws.amazon.com/ses/latest/APIReference/API_VerifyEmailIdentity.html
     * @param  string $email
     * @return void
     */
    public function verifyEmailIdentity($email)
    {
        try {
            $this->client->verifyEmailIdentity(array('EmailAddress' => $email));
        } catch (SesException $exception) {
            $this->parseException($exception);
        }
    }

    /**
     * @param  SesException $exception
     * @throws Exception\InvalidCredentialsException
     * @throws Exception\ValidationErrorException
     * @throws Exception\RuntimeException
     */
    private function parseException(SesException $exception)
    {
        switch ($exception->getStatusCode()) {
            case 400:
                throw new Exception\ValidationErrorException(sprintf(
                    'An error occurred on Amazon SES (code %s): %s', $exception->getStatusCode(), $exception->getMessage()
                ));
            case 403:
                throw new Exception\InvalidCredentialsException(sprintf(
                    'Amazon SES authentication error (code %s): %s', $exception->getStatusCode(), $exception->getMessage()
                ));
            default:
                throw new Exception\RuntimeException(sprintf(
                    'An error occurred on Amazon SES (code %s): %s', $exception->getStatusCode(), $exception->getMessage()
                ));
        }
    }
}
