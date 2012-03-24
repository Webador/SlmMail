<?php

namespace SlmMail\Service\Mailchimp;

use \InvalidArgumentException,
    \RuntimeException,
    Zend\Http\Client,
    Zend\Http\Request,
    Zend\Http\Response,
    Zend\Uri\Uri;

class Campaign
{
    const API_URI = 'http://%s.api.mailchimp.com/1.3/';

    protected $apiKey;
    protected $client;
    protected $host;

    public function __construct ($api_key)
    {
        $this->apiKey = $api_key;
    }

    /********************************
     *  Campaign
     ********************************/

    /**
     * @todo add a lot more campaign stuff
     */
    public function getCampainsForEmail ()
    {
        
    }
    
    /********************************
     *  eCommerce
     ********************************/

    /**
     * Retrieve the Ecommerce Orders for an account
     * 
     * @todo Accept DateTime for $since
     * 
     * @link http://apidocs.mailchimp.com/api/1.3/ecommorders.func.php
     * @param string|int $start
     * @param string|int $limit
     * @param string $since
     * @return array 
     */
    public function getEcommerceOrders ($start, $limit, $since)
    {
        $params   = compact('start', 'limit', 'since');
        $params   = $this->filterNullParams($params);
        $response = $this->prepareHttpClient('ecommOrders', $params)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Import Ecommerce Order Information to be used for Segmentation
     * 
     * This will generally be used by ecommerce package plugins that we provide
     * or by 3rd part system developers. The order parameter is according the
     * api:
     * 
     * <code>
     * $order = array(
     *   'id'       => 12,
     *   'total'    => 12.78,
     *   'store_id' => 15,
     *   'items' => array(
     *     'product_id'    => 16,
     *     'product_name'  => 'T-shirt',
     *     'category_id'   => 19,
     *     'category_name' => 'Fashion',
     *     'qty'           => 1,
     *     'cost'          => 12.78
     *   ),
     * );
     * </code>
     * 
     * More parameters can be added, above code sample shows only the required
     * parameters for the eCommerce transaction.
     * 
     * @link http://apidocs.mailchimp.com/api/1.3/ecommorderadd.func.php
     * @param array $order
     * @return bool
     */
    public function addEcommerceOrder (array $order)
    {
        $params   = compact('order');
        $response = $this->prepareHttpClient('ecommOrderAdd', $params)
                         ->send();

        return $this->parseResponse($response);
    }
    
    /**
     * Delete Ecommerce Order Information used for segmentation
     * 
     * This will generally be used by ecommerce package plugins that we provide
     * or by 3rd part system developers
     * 
     * @link http://apidocs.mailchimp.com/api/1.3/ecommorderdel.func.php
     * @param string|int $store_id
     * @param string|int $order_id
     * @return bool
     */
    public function deleteEcommerceOrder ($store_id, $order_id)
    {
        $params   = compact('store_id', 'order_id');
        $response = $this->prepareHttpClient('ecommOrderDel', $params)
                         ->send();

        return $this->parseResponse($response);
    }

    /********************************
     *  Folder
     ********************************/
    
    /**
     *
     * @param type $type
     * @return type 
     */
    public function getFolders ($type = null)
    {
        $params = array();
        if (null !== $type) {
            if (in_array($type, array('campaign', 'autoresponder'))) {
                $params['type'] = $type;
            } else {
                throw new InvalidArgumentException(sprintf(
                    'Type must be either "campaign" or "autoresponder", %s given',
                    $type));
            }
        }

        $response = $this->prepareHttpClient('folders', $params)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     *
     * @param type $name
     * @param type $type
     * @return type 
     */
    public function addFolder ($name, $type = null)
    {
        $params   = compact('name', 'type');
        $response = $this->prepareHttpClient('folderAdd', $params)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     *
     * @param type $id
     * @param type $name
     * @return type 
     */
    public function updateFolder ($id, $name)
    {
        $params   = compact('id', 'name');
        $response = $this->prepareHttpClient('folderUpdate', $params)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     *
     * @param type $id
     * @return type 
     */
    public function deleteFolder ($id)
    {
        $params   = compact('id');
        $response = $this->prepareHttpClient('folderDel', $params)
                         ->send();

        return $this->parseResponse($response);
    }

    /********************************
     *  Golden Monkeys
     ********************************/
    
    /**
     * 
     */
    public function getGoldenMonkeys ()
    {
        
    }

    /**
     * 
     */
    public function addGoldenMonkeys ()
    {
        
    }

    /**
     * 
     */
    public function deleteGoldenMonkeys ()
    {
        
    }

    /**
     * 
     */
    public function getGoldenMonkeysActivity ()
    {
        
    }

    /********************************
     *  Lists
     ********************************/

    /**
     * @todo add a lot more list stuff
     */
    
    /**
     * 
     */
    public function getListsForEmail ()
    {
        
    }

    /********************************
     *  Security
     ********************************/
    
    /**
     * 
     */
    public function getApiKeys ($username, $password, $expired = null)
    {
        $params   = compact('username', 'password', 'expired');
        $params   = $this->filterNullParams($params, array('username', 'password'));
        $response = $this->prepareHttpClient('apikeys', $params)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     *
     * @param type $username
     * @param type $password
     * @return type 
     */
    public function addApiKey ($username, $password)
    {
        $params   = compact('username', 'password');
        $response = $this->prepareHttpClient('apikeyAdd', $params)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     *
     * @param type $username
     * @param type $password
     * @return type 
     */
    public function expireApiKey ($username, $password)
    {
        $params   = compact('username', 'password');
        $response = $this->prepareHttpClient('apikeyExpire', $params)
                         ->send();

        return $this->parseResponse($response);
    }

    /********************************
     *  Templates
     ********************************/
    
    /**
     * 
     */
    public function getTemplates ()
    {
        
    }

    /**
     * 
     */
    public function getTemplate ()
    {
        
    }

    /**
     * 
     */
    public function addTemplate ()
    {
        
    }

    /**
     * 
     */
    public function updateTemplate ()
    {
        
    }

    /**
     * 
     */
    public function deleteTemplate ()
    {
        
    }

    /**
     * 
     */
    public function undeleteTemplate ()
    {
        
    }

    /********************************
     *  Helpers
     ********************************/
    
    /**
     *
     * @param type $type
     * @param type $content
     * @return type 
     */
    public function generateText ($type, $content)
    {
        if (!in_array($type, array('html', 'template', 'url', 'cid', 'tid'))) {
            throw new InvalidArgumentException(sprintf(
                'Type must be "html", "template", "url", "cid", "tid", %s given',
                $type));
        }
        
        switch ($type) {
            case 'html':
                if (!is_string($content)) {
                    throw new InvalidArgumentException('Content must be a string');
                }
                break;
            case 'template':
                if (!is_array($content)) {
                    throw new InvalidArgumentException('Content must be an array');
                }
                break;
            case 'url':
                $uri = new Uri($content);
                if (!$uri->isValid()) {
                    throw new InvalidArgumentException('Content must be a valid uri');
                }
        }
        
        $params   = compact('type', 'content');
        $response = $this->prepareHttpClient('generateText', $params)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     *
     * @return type 
     */
    public function getAccountDetails ()
    {
        $response = $this->prepareHttpClient('getAccountDetails')
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     *
     * @param type $html
     * @param type $strip_css
     * @return type 
     */
    public function inlineCss ($html, $strip_css)
    {
        $params   = compact('html', 'strip_css');
        $params   = $this->filterNullParams($params, array('html'));
        $response = $this->prepareHttpClient('inlineCss', $params)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     *
     * @return type 
     */
    public function ping ()
    {
        $response = $this->prepareHttpClient('ping')
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     *
     * @return type 
     */
    public function chimpChatter ()
    {
        $response = $this->prepareHttpClient('chimpChatter')
                         ->send();

        return $this->parseResponse($response);
    }

    public function getHttpClient ()
    {
        if (null === $this->client) {
            $this->client = new Client;

            $this->client->setMethod(Request::METHOD_POST)
                         ->setUri($this->getHost());
        }

        return $this->client;
    }

    public function setHttpClient (Client $client)
    {
        $this->client = $client;
    }

    protected function prepareHttpClient ($method, array $data = array())
    {
        $params = array('apikey' => $this->apiKey,
            'method' => $method,
            'output' => 'php');

        return $this->getHttpClient()
                    ->setParameterGet($params)
                    ->setParameterPost($data);
    }

    protected function getHost ()
    {
        if (null === $this->host) {
            $this->host = sprintf(self::API_URI, substr($this->apiKey, strpos($this->apiKey, '-') + 1));
        }

        return $this->host;
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

    protected function parseResponse (Response $response)
    {
        $body = unserialize($response->getBody());

        if (isset($body['error']) && isset($body['code'])) {
            throw new RuntimeException(sprintf(
                'Could not send request: Api error #%s (%s)',
                $body['code'],
                $body['error']));
        }

        return $body;
    }
}