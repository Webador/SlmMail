<?php

namespace SlmMail\Service;

use Zend\Mail\Message,
    Zend\Http\Client,
    Zend\Http\Request,
    Zend\Http\Response,
    Zend\Json\Json,
    Zend\Mail\Exception\RuntimeException,
    SlmMail\Mail\Message\Postmark as PostmarkMessage;

class Postmark
{
    const API_URI         = 'http://api.postmarkapp.com/';
    const RECIPIENT_LIMIT = 20;
    
    protected $apiKey;
    protected $client;
    protected $filters = array(
        'HardBounce', 
        'Transient', 
        'Unsubscribe', 
        'Subscribe', 
        'AutoResponder', 
        'AddressChange', 
        'DnsError', 
        'SpamNotification', 
        'OpenRelayTest', 
        'Unknown', 
        'SoftBounce', 
        'VirusNotification', 
        'ChallengeVerification', 
        'BadEmailAddress', 
        'SpamComplaint', 
        'ManuallyDeactivated', 
        'Unconfirmed', 
        'Blocked'
    );
    
    /**
     * Set api key for this service instance
     * 
     * @param string $api_key 
     */
    public function setApiKey ($api_key)
    {
        $this->apiKey = $api_key;
    }
    
    /**
     * Send message to Postmark service
     * 
     * @link http://developer.postmarkapp.com/developer-build.html
     * @param Message $message
     * @return stdClass
     */
    public function sendEmail (Message $message)
    {
        $data = array(
            'Subject'  => $message->getSubject(),
            'HtmlBody' => $message->getBody(),
            'TextBody' => $message->getBodyText(),
        );
        
        $to = array();
        foreach ($message->to() as $address) {
            $to[] = $address->toString();
        }
        $data['To'] = implode(',', $to);
        
        $cc = array();
        foreach ($message->cc() as $address) {
            $cc[] = $address->toString();
        }
        if (self::RECIPIENT_LIMIT < count($cc)) {
            throw new RuntimeException('Limitation exceeded for CC recipients');
        } elseif (count($cc)) {
            $data['Cc'] = implode(',', $cc);
        }
        
        $bcc = array();
        foreach ($message->bcc() as $address) {
            $bcc[] = $address->toString();
        }
        if (self::RECIPIENT_LIMIT < count($bcc)) {
            throw new RuntimeException('Limitation exceeded for BCC recipients');
        } elseif (count($bcc)) {
            $data['Bcc'] = implode(',', $bcc);
        }
        
        $from = $message->from();
        if (1 !== count($from)) {
            throw new RuntimeException('Postmark requires a registered and confirmed from address');
        } elseif (count($from)) {
            $from->rewind();
            $data['From'] = $from->current()->toString();
        }
        
        $replyTo = $message->replyTo();
        if (1 < count($replyTo)) {
            throw new RuntimeException('Postmark has only support for one reply-to address');
        } elseif (count($replyTo)) {
            $from->rewind();
            $data['ReplyTo'] = $replyTo->current()->toString();
        }
        
        if ($message instanceof PostmarkMessage 
            && null !== ($tag = $message->getTag())
        ) {
            $data['Tag'] = $tag;
        }
        
        /**
         * @todo Handling attachments for emails
         * 
         * Example code how that possibly might work:
         * 
         * <code>
         * if ($hasAttachment) {
         *      $attachments = array();
         *      foreach ($message->getAttachmentCollection() as $attachment) {
         *          $attachments[] = array(
         *              'ContentType' => $attachment->getContentType(),
         *              'Name'        => $attachment->getName(),
         *              'Content'     => $attachment->getContent(),
         *          );
         *      }
         *      $data['Attachments'] = $attachments;
         *  }
         * </code>
         */

        $response = $this->getHttpClient('/email')
                         ->setMethod(Request::METHOD_POST)
                         ->setRawBody(Json::encode($data))
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    /**
     * Get a summary of inactive emails and bounces by type
     * 
     * @link http://developer.postmarkapp.com/developer-bounces.html#get-delivery-stats
     * @return StdClass
     */
    public function getDeliveryStats ()
    {
        $response = $this->getHttpClient('/deliverystats')
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    /**
     * Get a portion of bounces according to the specified input criteria
     * 
     * The $count and $offset are mandatory. For type, a specific set of types
     * are available, defined as filter.
     * 
     * @see $filters
     * @link http://developer.postmarkapp.com/developer-bounces.html#get-bounces
     * @param int $count
     * @param int $offset
     * @param string $type
     * @param string $inactive
     * @param string $emailFilter
     * @return StdClass
     */
    public function getBounces ($count, $offset, $type = null, $inactive = null, $emailFilter = null)
    {   
        if (null !== $type &&!in_array($type, $this->filters)) {
            throw new RuntimeException(sprintf(
                'Type %s is not a supported filter',
                $type
            ));
        }
        
        $params   = compact('count', 'offset', 'type', 'inactive', 'emailFilter');
        $params   = $this->filterNullParams($params);
        $response = $this->getHttpClient('/bounces')
                         ->setParameterGet($params)
                         ->send();
                         
        return $this->parseResponse($response);
    }
    
    /**
     * Get details about a single bounce
     * 
     * @link http://developer.postmarkapp.com/developer-bounces.html#get-a-single-bounce
     * @param int $id
     * @return stdClass
     */
    public function getBounce ($id)
    {
        $response = $this->getHttpClient('/bounces/' . $id)
                         ->send();
                                 
        return $this->parseResponse($response);
    }
    
    /**
     * Get the raw source of the bounce Postmark accepted
     * 
     * @link http://developer.postmarkapp.com/developer-bounces.html#get-bounce-dump
     * @param int $id
     * @return string
     */
    public function getBounceDump ($id)
    {
        $response = $this->getHttpClient('/bounces/' . $id . '/dump')
                         ->send();
                                 
        $response = $this->parseResponse($response);
        return $response->Body;
    }
    
    /**
     * Get a list of tags used for the current Postmark server
     * 
     * @link http://developer.postmarkapp.com/developer-bounces.html#get-bounce-tags
     * @return array
     */
    public function getBounceTags ()
    {
        $response = $this->getHttpClient('/bounces/tags')
                         ->send();
                                 
        return $this->parseResponse($response);
    }
    
    /**
     * Activates a deactivated bounce
     * 
     * @link http://developer.postmarkapp.com/developer-bounces.html#activate-a-bounce
     * @param int $id
     * @return StdClass
     */
    public function activateBounce ($id)
    {
        $response = $this->getHttpClient('/bounces/' . $id . '/activate')
                         ->setMethod(Request::METHOD_PUT)
                         ->send();
                                 
        return $this->parseResponse($response);
    }
    
    /**
     * Filter null values from the array
     * 
     * Because parameters get interpreted when they are send, remove them 
     * from the list before the request is sent.
     * 
     * @param array $params
     * @param array $exceptions
     * @return array
     */
    protected function filterNullParams (array $params, array $exceptions = array())
    {
        $return = array();
        foreach ($params as $key => $value) {
            if (null !== $value || in_array($key, $exceptions)) {
                $return[$key] = $value;
            }
        }
        
        return $return;
    }
    
    /**
     * Get a http client instance
     * 
     * @param string $path
     * @return Client
     */
    protected function getHttpClient ($path)
    {
        if (null === $this->client) {
            if (null === $this->apiKey) {
                throw new RuntimeException('Required api key not set');
            }
            
            $headers = array(
                'Accept'                  => 'application/json',
                'X-Postmark-Server-Token' => $this->apiKey
            );
            
            $this->client = new Client();
            $this->client->setUri(self::API_URI)
                         ->setMethod(Request::METHOD_GET)
                         ->setHeaders($headers);
        }
        
        $this->client->getUri()->setPath($path);
        return $this->client;
    }
    
    /**
     * Parse a Reponse object and check for errors
     * 
     * @param Response $response
     * @return StdClass
     */
    protected function parseResponse (Response $response)
    {
        if (!$response->isOk()) {
            switch ($response->getStatusCode()) {
                case 401:
                    throw new RuntimeException('Could not send request: authentication error');
                    break;
                case 422:
                    $error = Json::decode($response->getBody());
                    throw new RuntimeException(sprintf(
                        'Could not send request: api error code %s (%s)', 
                        $error->ErrorCode, $error->Message));
                    break;
                case 500:
                    throw new RuntimeException('Could not send request: Postmark server error');
                    break;
                default:
                    throw new RuntimeException('Unknown error during request to Postmark server');
            }
        }
        
        return Json::decode($response->getBody());
    }
}