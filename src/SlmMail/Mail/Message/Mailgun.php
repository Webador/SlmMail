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

namespace SlmMail\Mail\Message;

use Zend\Mail\Message;

class Mailgun extends Message
{
    const TAG_LIMIT = 3;

    /**
     * @var array
     */
    protected $tags = array();

    /**
     * @var array
     */
    protected $options = array();

    /**
     * @var array
     */
    protected $validOptions = array(
        'dkim'            => 'o:dkim',
        'delivery_time'   => 'o:deliverytime',
        'test_mode'       => 'o:testmode',
        'tracking'        => 'o:tracking',
        'tracking_clicks' => 'o:tracking-clicks',
        'tracking_opens'  => 'o:tracking-opens',
    );

    /**
     * @var array
     */
    protected $recipientVariables = array();

    /**
     * Get all tags for this message
     *
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Set all tags for this message
     *
     * @param  array $tags
     * @throws Exception\InvalidArgumentException
     * @return self
     */
    public function setTags(array $tags)
    {
        if (count($tags) > self::TAG_LIMIT) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Mailgun only allows up to %s tags', self::TAG_LIMIT
            ));
        }

        $this->tags = $tags;
        return $this;
    }

    /**
     * Add a tag to this message
     *
     * @param string $tag
     * @throws Exception\InvalidArgumentException
     * @return self
     */
    public function addTag($tag)
    {
        if (count($this->tags) + 1 > self::TAG_LIMIT) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Mailgun only allows up to %s tags', self::TAG_LIMIT
            ));
        }

        $this->tags[] = (string) $tag;
        return $this;
    }

    /**
     * Add options to the message
     *
     * @param  array $options
     * @throws Exception\InvalidArgumentException
     * @return self
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            if (!array_key_exists($key, $this->getValidOptions())) {
                throw new Exception\InvalidArgumentException(sprintf(
                    'Invalid option "%s" given', $key
                ));
            }
        }

        $this->options = $options;
        return $this;
    }

    /**
     * Set an option to the message
     *
     * @param  string $key
     * @param  string $value
     * @throws Exception\InvalidArgumentException
     * @return self
     */
    public function setOption($key, $value)
    {
        if (!array_key_exists($key, $this->getValidOptions())) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid option "%s" given', $key
            ));
        }

        $this->options[$key] = $value;
        return $this;
    }

    /**
     * Get all the options of the message
     *
     * @return string[]
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Get list of supported options
     *
     * @return array
     */
    public function getValidOptions()
    {
        return $this->validOptions;
    }

    /**
     * @param string $recipient
     * @param array $variables
     */
    public function setRecipientVariables($recipient, array $variables)
    {
        $this->recipientVariables[(string) $recipient] = $variables;
    }

    /**
     * @param string $recipient
     * @param string $key
     * @param string $value
     */
    public function addRecipientVariable($recipient, $key, $value)
    {
        $this->recipientVariables[(string) $recipient][(string) $key] = (string) $value;
    }

    /**
     * @return array
     */
    public function getRecipientVariables()
    {
        return $this->recipientVariables;
    }
}
