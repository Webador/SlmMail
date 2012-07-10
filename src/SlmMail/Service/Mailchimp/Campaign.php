<?php
/**
 * Copyright (c) 2012 Jurian Sluiman.
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
 * @package     SlmMail
 * @subpackage  Service
 * @author      Jurian Sluiman <jurian@juriansluiman.nl>
 * @copyright   2012 Jurian Sluiman.
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link        http://juriansluiman.nl
 */
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

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $host;

    /**
     * Constructor
     * @param string $api_key
     */
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
     * List all the folders for a user account
     *
     * @param http://apidocs.mailchimp.com/api/1.3/folders.func.php
     * @param string $type
     * @return array
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
     * Add a new folder to file campaigns or autoresponders in
     *
     * @link http://apidocs.mailchimp.com/api/1.3/folderadd.func.php
     * @param string $name
     * @param string $type
     * @return int
     */
    public function addFolder ($name, $type = null)
    {
        $params   = compact('name', 'type');
        $response = $this->prepareHttpClient('folderAdd', $params)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Update the name of a folder for campaigns or autoresponders
     *
     * @link http://apidocs.mailchimp.com/api/1.3/folderupdate.func.php
     * @param string|int $id
     * @param string $name
     * @return bool
     */
    public function updateFolder ($id, $name)
    {
        $params   = compact('id', 'name');
        $response = $this->prepareHttpClient('folderUpdate', $params)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Delete a campaign or autoresponder folder
     *
     * Note that this will simply make campaigns in the folder appear unfiled,
     * they are not removed.
     *
     * @link http://apidocs.mailchimp.com/api/1.3/folderdel.func.php
     * @param string|int $id
     * @return bool
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

    public function getLists ($filters, $start, $limit) {}

    public function getWebhooks ($id) {}
    public function addWebhook ($id, $url, $actions = null, $sources = null) {}
    public function deleteWebhook ($id, $url) {}

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
     * Retrieve a list of all MailChimp API Keys for this User
     *
     * @link http://apidocs.mailchimp.com/api/1.3/apikeys.func.php
     * @param string $username
     * @param string $password
     * @param bool $expired
     * @param array
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
     * Add an API Key to your account
     *
     * We will generate a new key for you and return it.
     *
     * @link http://apidocs.mailchimp.com/api/1.3/apikeyadd.func.php
     * @param string $username
     * @param string $password
     * @return string
     */
    public function addApiKey ($username, $password)
    {
        $params   = compact('username', 'password');
        $response = $this->prepareHttpClient('apikeyAdd', $params)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Expire a Specific API Key
     *
     *  Note that if you expire all of your keys, just visit your API dashboard
     * to create a new one. If you are trying to shut off access to your account
     * for an old developer, change your MailChimp password, then expire all of
     * the keys they had access to. Note that this takes effect immediately, so
     * make sure you replace the keys in any working application before expiring
     * them! Consider yourself warned...
     *
     * @link http://apidocs.mailchimp.com/api/1.3/apikeyexpire.func.php
     * @param string $username
     * @param string $password
     * @param string $api_key If null, api key for this service is used
     * @return string
     */
    public function expireApiKey ($username, $password, $api_key = null)
    {
        $params   = compact('username', 'password', 'api_key');
        $params   = $this->filterNullParams($params, array('username', 'password'));
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
     * Have HTML content auto-converted to a text-only format
     *
     * ou can send: plain HTML, an array of Template content, an existing
     * Campaign Id, or an existing Template Id. Note that this will not save
     * anything to or update any of your lists, campaigns, or templates.
     *
     * @link http://apidocs.mailchimp.com/api/1.3/generatetext.func.php
     * @param string $type
     * @param string $content
     * @return string
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
     * Retrieve lots of account information
     *
     * This is including payments made, plan info, some account stats, installed
     * modules, contact info, and more. No private information like Credit Card
     * numbers is available.
     *
     * @param http://apidocs.mailchimp.com/api/1.3/getaccountdetails.func.php
     * @return array
     */
    public function getAccountDetails ()
    {
        $response = $this->prepareHttpClient('getAccountDetails')
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Send your HTML content to have the CSS inlined and optionally remove the
     * original styles.
     *
     * @link http://apidocs.mailchimp.com/api/1.3/inlinecss.func.php
     * @param string $html
     * @param string $strip_css
     * @return string
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
     * "Ping" the MailChimp API
     *
     * A simple method you can call that will return a constant value as long as
     * everything is good. Note than unlike most all of our methods, we don't
     * throw an Exception if we are having issues. You will simply receive a
     * different string back that will explain our view on what is going on.
     *
     * @link http://apidocs.mailchimp.com/api/1.3/ping.func.php
     * @return string
     */
    public function ping ()
    {
        $response = $this->prepareHttpClient('ping')
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Return the current Chimp Chatter messages for an account.
     *
     * @link http://apidocs.mailchimp.com/api/1.3/chimpchatter.func.php
     * @return array
     */
    public function chimpChatter ()
    {
        $response = $this->prepareHttpClient('chimpChatter')
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Http client getter
     *
     * @return Client
     */
    public function getHttpClient ()
    {
        if (null === $this->client) {
            $this->client = new Client;

            $this->client->setMethod(Request::METHOD_POST)
                         ->setUri($this->getHost());
        }

        return $this->client;
    }

    /**
     * Http client setter
     *
     * @param Client $client
     */
    public function setHttpClient (Client $client)
    {
        $this->client = $client;
    }

    /**
     * Prepare client with data
     *
     * @param string $method
     * @param array $data
     * @return Client
     */
    protected function prepareHttpClient ($method, array $data = array())
    {
        $params = array('apikey' => $this->apiKey,
            'method' => $method,
            'output' => 'php');

        return $this->getHttpClient()
                    ->setParameterGet($params)
                    ->setParameterPost($data);
    }

    /**
     * Parse Mailchimp host based on api key
     *
     * @return string
     */
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

    /**
     * Parse response from server and check for errors
     *
     * @param Response $response
     * @return array
     */
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