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
use Zend\Mime\Part;

class Mandrill extends Message
{
    /**
     * @var array
     */
    protected $options = array();

    /**
     * @var array
     */
    protected $validOptions = array(
        'auto_html',
        'auto_text',
        'google_analytics_campaign',
        'google_analytics_domains',
        'important',
        'inline_css',
        'merge',
        'merge_language',
        'metadata',
        'preserve_recipients',
        'return_path_domain',
        'signing_domain',
        'subaccount',
        'track_clicks',
        'track_opens',
        'tracking_domain',
        'url_strip_qs',
        'view_content_link'
    );

    /**
     * @var array
     */
    protected $tags = array();

    /**
     * @var string|null
     */
    protected $template;

    /**
     * @var array
     */
    protected $templateContent = array();

    /**
     * @var array
     */
    protected $globalVariables = array();

    /**
     * @var array
     */
    protected $variables = array();

    /**
     * @var array
     */
    protected $globalMetadata = array();

    /**
     * @var array
     */
    protected $metadata = array();

    /**
     * @var Part[]|array
     */
    protected $images = array();

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
            if (!in_array($key, $this->validOptions)) {
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
        if (!in_array($key, $this->validOptions)) {
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
     * @return self
     */
    public function setTags(array $tags)
    {
        $this->tags = $tags;
        return $this;
    }

    /**
     * Add a tag to this message
     *
     * @param string $tag
     * @return self
     */
    public function addTag($tag)
    {
        $this->tags[] = (string) $tag;
        return $this;
    }

    /**
     * Set Mandrill template name to use
     *
     * @param  string $template
     * @return self
     */
    public function setTemplate($template)
    {
        $this->template = (string) $template;
        return $this;
    }

    /**
     * Get Mandrill template name to use
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set template content to inject
     *
     * @param  array $templateContent
     * @return Mandrill
     */
    public function setTemplateContent(array $templateContent)
    {
        $this->templateContent = $templateContent;
        return $this;
    }

    /**
     * Get template content to inject
     *
     * @return array
     */
    public function getTemplateContent()
    {
        return $this->templateContent;
    }

    /**
     * Set the global parameters to use with the template
     *
     * @param  array $globalVariables
     * @return self
     */
    public function setGlobalVariables(array $globalVariables)
    {
        $this->globalVariables = $globalVariables;
        return $this;
    }

    /**
     * Get the global parameters to use with the template
     *
     * @return array
     */
    public function getGlobalVariables()
    {
        return $this->globalVariables;
    }

    /**
     * Set the template parameters for a given recipient address
     *
     * @param  string $recipient
     * @param  array  $variables
     * @return Mandrill
     */
    public function setVariables($recipient, array $variables)
    {
        $this->variables[$recipient] = $variables;
        return $this;
    }

    /**
     * Get the template parameters for all recipients
     *
     * @return array
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * Set the global metadata to send with with message
     *
     * @param  array $globalVariables
     * @return self
     */
    public function setGlobalMetadata(array $globalMetadata)
    {
        $this->globalMetadata = $globalMetadata;
        return $this;
    }

    /**
     * Get the global metadata to send with with message
     *
     * @return array
     */
    public function getGlobalMetadata()
    {
        return $this->globalMetadata;
    }

    /**
     * Set the metadata for a given recipient address
     *
     * @param  string $recipient
     * @param  array  $variables
     * @return Mandrill
     */
    public function setMetadata($recipient, array $metadata)
    {
        $this->metadata[$recipient] = $metadata;
        return $this;
    }

    /**
     * Get the metadata for all recipients
     *
     * @return array
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Set attachments to the message
     *
     * @param  Part[]|array $images
     * @return self
     */
    public function setImages(array $images)
    {
        $this->images = $images;
        return $this;
    }

    /**
     * Add image to the message
     *
     * @param  Part $image
     * @return self
     */
    public function addImage(Part $image)
    {
        $this->images[] = $image;
        return $this;
    }

    /**
     * Get images of the message
     *
     * @return array|Part[]
     */
    public function getImages()
    {
        return $this->images;
    }
}
