<?php

namespace SlmMail\Service;

use Zend\Mail\Message,
    Zend\Http\Client,
    Zend\Http\Request,
    Zend\Http\Response,
    Zend\Json\Json,
    Zend\Mail\Exception\RuntimeException;

class SendGrid
{
    const API_URI = 'https://sendgrid.com/';

    protected $username;
    protected $password;
    protected $client;

    public function __construct ($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /** Mail */
    public function sendMail (Message $message)
    {
        $data = array(
            'api_user' => $this->username,
            'api_key'  => $this->password,
            'subject'  => $message->getSubject(),
            'html'     => $message->getBody(),
            'text'     => $message->getBodyText(),
        );
        
        foreach ($message->to() as $address) {
            $data['to'][]    = $address->getEmail();
            $data['names'][] = $address->getName();
        }
        foreach ($message->cc() as $address) {
            $data['to'][]    = $address->getEmail();
            $data['names'][] = $address->getName();
        }
        
        if (count($message->bcc())) {
            foreach ($message->bcc() as $address) {
                $data['bcc'][] = $address->getEmail();
            }
        }
        
        $from = $message->from();
        if (1 !== count($from)) {
            throw new RuntimeException('SendGrid requires exactly one from address');
        }
        $from->rewind();
        $from = $from->current();
        $data['from']      = $from->getEmail();
        $data['fromname'] = $from->getName();
        
        $replyTo = $message->replyTo();
        if (1 < count($replyTo)) {
            throw new RuntimeException('SendGrid has only support for one reply-to address');
        } elseif (count($replyTo)) {
            $replyTo->rewind();
            $replyTo = $replyTo->current();
            
            $data['replyto']      = $replyTo->getEmail();
        }
        
        /**
         * @todo Handling attachments for emails
         */
        
        $response = $this->getHttpClient('mail.send')
                         ->setParameterGet($data)
                         ->send();
        
        return $this->parseResponse($response);
    }

    /** Blocks */
    public function getBlocks ($date, $days, $start_date, $end_date)
    {
        $params   = compact($date, $days, $start_date, $end_date);
        $response = $this->getHttpClient('blocks.get')
                         ->setParameterGet($params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    public function deleteBlock ($email)
    {
        $params   = compact($email);
        $response = $this->getHttpClient('blocks.delete')
                         ->setParameterGet($params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    /** Bounces */
    public function getBounces ($date, $days, $start_date, $end_date, $limit, $offset, $type, $email)
    {
        $params   = compact($date, $days, $start_date, $end_date, $limit, $offset, $type, $email);
        $response = $this->getHttpClient('bounces.get')
                         ->setParameterGet($params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    public function deleteBounces ($start_date, $end_date, $type, $email)
    {
        $params   = compact($start_date, $end_date, $type, $email);
        $response = $this->getHttpClient('bounces.delete')
                         ->setParameterGet($params)
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function countBounces ($start_date, $end_date, $type)
    {
        $params   = compact($start_date, $end_date, $type);
        $response = $this->getHttpClient('bounces.count')
                         ->setParameterGet($params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    /** Email parse settings */
    public function getParseSettings ()
    {
        $response = $this->getHttpClient('parse.get')
                         ->send();
        
        return $this->parseResponse($response);
    }

    public function addParseSetting ($hostname, $url, $spam_check)
    {
        $params   = compact($hostname, $url, $spam_check);
        $response = $this->getHttpClient('parse.set')
                         ->setParameterGet($params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    public function editParseSetting ($hostname, $url, $spam_check)
    {
        $params   = compact($hostname, $url, $spam_check);
        $response = $this->getHttpClient('parse.set')
                         ->setParameterGet($params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    public function deleteParseSetting ($hostname)
    {
        $params   = compact($hostname);
        $response = $this->getHttpClient('parse.delete')
                         ->setParameterGet($params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    /** Events */
    public function getEventPostUrl ()
    {
        $response = $this->getHttpClient('eventposturl.get')
                         ->send();
        
        return $this->parseResponse($response);
    }

    public function setEventPostUrl ($url)
    {
        $params   = compact($url);
        $response = $this->getHttpClient('eventposturl.set')
                         ->setParameterGet($params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    public function deleteEventPostUrl ()
    {
        $response = $this->getHttpClient('eventposturl.delete')
                         ->send();
        
        return $this->parseResponse($response);
    }

    /** Filters */
    public function getFilters () {}
    public function activateFilters () {}
    public function deactivateFilters () {}
    public function setupFilters () {}
    public function getFilterSettings () {}

    /** Invalid emails */
    public function getInvalidEmails ($date, $days, $start_date, $end_date, $limit, $offset, $email)
    {
        $params   = compact($date, $days, $start_date, $end_date, $limit, $offset, $email);
        $response = $this->getHttpClient('invalidemails.get')
                         ->setParameterGet($params)
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function deleteInvalidEmails ($start_date, $end_date, $email)
    {
        $params   = compact($start_date, $end_date, $email);
        $response = $this->getHttpClient('invalidemails.delete')
                         ->setParameterGet($params)
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function countInvalidEmails ($start_date, $end_date)
    {
        $params   = compact($start_date, $end_date);
        $response = $this->getHttpClient('invalidemails.count')
                         ->setParameterGet($params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    /** Profile */
    public function getProfile ()
    {
        $response = $this->getHttpClient('profile.get')
                         ->send();
        
        return $this->parseResponse($response);
    }

    public function updateProfile ($firstname, $lastname, $address, $city, $state, $country, $zip, $phone, $website)
    {
        $params   = compact($firstname, $lastname, $address, $city, $state, $country, $zip, $phone, $website);
        $response = $this->getHttpClient('profile.set')
                         ->setParameterGet($params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    /**
     * This is disabled for now because of potential problems with Zend\Di
     * 
     * @todo Fix method call
     */
//    public function setUsername ($username)
//    {
//        $params   = compact($username);
//        $response = $this->getHttpClient('profile.setUsername')
//                         ->setParameterGet($params)
//                         ->send();
//        
//        return $this->parseResponse($response);
//    }
//
//    public function setPassword ($password)
//    {
//        $params   = array('password' => $password, 'confirm_password' => $password);
//        $response = $this->getHttpClient('password.set')
//                         ->setParameterGet($params)
//                         ->send();
//        
//        return $this->parseResponse($response);
//    }

    public function setEmail ($email)
    {
        $params   = compact($email);
        $response = $this->getHttpClient('profile.setEmail')
                         ->setParameterGet($params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    /** Spam reports */
    public function getSpamReports ($date, $days, $start_date, $end_date, $limit, $offset, $email)
    {
        $params   = compact($date, $days, $start_date, $end_date, $limit, $offset, $email);
        $response = $this->getHttpClient('spamreports.get')
                         ->setParameterGet($params)
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function deleteSpamReports ($start_date, $end_date, $email)
    {
        $params   = compact($start_date, $end_date, $email);
        $response = $this->getHttpClient('spamreports.delete')
                         ->setParameterGet($params)
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function countSpamReports ($start_date, $end_date)
    {
        $params   = compact($start_date, $end_date);
        $response = $this->getHttpClient('spamreports.count')
                         ->setParameterGet($params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    /** Stats */
    public function getStats ($days, $start_date, $end_date)
    {
        $params   = compact($days, $start_date, $end_date);
        $response = $this->getHttpClient('stats.get')
                         ->setParameterGet($params)
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function getStatsAggregate ()
    {
        $params   = array('aggregate' => '1');
        $response = $this->getHttpClient('stats.get')
                         ->setParameterGet($params)
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function getCategoryList ()
    {
        $params   = array('list' => 'true');
        $response = $this->getHttpClient('stats.get')
                         ->setParameterGet($params)
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function getCategoryStats ($category, $days, $start_date, $end_date)
    {    
        $params   = compact($category, $days, $start_date, $end_date);
        $response = $this->getHttpClient('stats.get')
                         ->setParameterGet($params)
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function getCategoryAggregate ($category, $days, $start_date)
    {
        $params   = compact($category, $days, $start_date) + array('aggregate' => '1');
        $response = $this->getHttpClient('stats.get')
                         ->setParameterGet($params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    /** Unsubscribes */
    public function getUnsubscribes ($date, $days, $start_date, $end_date, $limit, $offset, $email)
    {
        $params   = compact($date, $days, $start_date, $end_date, $limit, $offset, $email);
        $response = $this->getHttpClient('unsubscribes.get')
                         ->setParameterGet($params)
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function addUnsubscribes ($email)
    {
        $params   = compact($email);
        $response = $this->getHttpClient('unsubscribes.add')
                         ->setParameterGet($params)
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function deleteUnsubscribes ($start_date, $end_date, $email)
    {
        $params   = compact($start_date, $end_date, $email);
        $response = $this->getHttpClient('unsubscribes.delete')
                         ->setParameterGet($params)
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function countUnsubscribes ($start_date, $end_date)
    {
        $params   = compact($start_date, $end_date);
        $response = $this->getHttpClient('unsubscribes.count')
                         ->setParameterGet($params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    protected function getHttpClient ($path, $format = 'json')
    {
        if (null === $this->client) {
            $this->client = new Client;
            $this->client->setUri(self::API_URI)
                         ->setMethod(Request::METHOD_GET);
        }

        $this->client->getUri()->setPath('/api/' . $path . '.' . $format);
        return $this->client;
    }

    protected function parseResponse (Response $response)
    {
        if (!$response->isSuccess()) {
            if ($response->isClientError()) {
                $error = Json::decode($response->getBody());
                throw new RuntimeException(sprintf(
                                'Could not send request: api errors (%s)', implode(', ', $error->errors)));
            } elseif ($response->isServerError()) {
                throw new RuntimeException('Could not send request: Sendgrid server error');
            } else {
                throw new RuntimeException('Unknown error during request to SendGrid server');
            }
        }

        return Json::decode($response->getBody());
    }
}