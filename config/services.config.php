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
 * @author      Jurian Sluiman <jurian@juriansluiman.nl>
 * @copyright   2012 Jurian Sluiman.
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link        http://juriansluiman.nl
 */

use SlmMail\Mail\Transport;
use SlmMail\Service;
use SlmMail\Exception;

return array(
	'aliases' => array(
		'amazonses-transport'    => 'SlmMail\Mail\Transport\AmazonSes',
		'elasticemail-transport' => 'SlmMail\Mail\Transport\ElasticEmail',
		'mailchimp-transport'    => 'SlmMail\Mail\Transport\Mailchimp',
		'postage-transport'      => 'SlmMail\Mail\Transport\Postage',
		'postmark-transport'     => 'SlmMail\Mail\Transport\Postmark',
		'sendgrid-transport'     => 'SlmMail\Mail\Transport\SendGrid',

		'amazonses-sevice'       => 'SlmMail\Service\AmazonSes',
		'elasticemail-sevice'    => 'SlmMail\Service\ElasticEmail',
		'mailchimp-sevice'       => 'SlmMail\Service\Mailchimp\Sts',
		'postage-sevice'         => 'SlmMail\Service\Postage',
		'postmark-sevice'        => 'SlmMail\Service\Postmark',
		'sendgrid-service'       => 'SlmMail\Service\SendGrid',
	),
	'factories' => array(
		'SlmMail\Mail\Transport\AmazonSes' => function($sm) {
			$service   = $sm->get('SlmMail\Service\AmazonSes');
			$transport = new Transport\AmazonSes($service);

			return $transport;
		},
		'SlmMail\Mail\Transport\ElasticEmail' => function($sm) {
			$service   = $sm->get('SlmMail\Service\ElasticEmail');
			$transport = new Transport\ElasticEmail($service);

			return $transport;
		},
		'SlmMail\Mail\Transport\Mailchimp' => function($sm) {
			$service   = $sm->get('SlmMail\Service\Mailchimp\Sts');
			$transport = new Transport\Mailchimp($service);

			return $transport;
		},
		'SlmMail\Mail\Transport\Postage' => function($sm) {
			$service   = $sm->get('SlmMail\Service\Postage');
			$transport = new Transport\Postage($service);

			return $transport;
		},
		'SlmMail\Mail\Transport\Postmark' => function($sm) {
			$service   = $sm->get('SlmMail\Service\Postmark');
			$transport = new Transport\Postmark($service);

			return $transport;
		},
		'SlmMail\Mail\Transport\SendGrid' => function($sm) {
			$service   = $sm->get('SlmMail\Service\SendGrid');
			$transport = new Transport\SendGrid($service);

			return $transport;
		},

		'SlmMail\Service\AmazonSes' => function($sm) {
			$config = $sm->get('config');
			$config = $config['slm_mail'];

			if (!isset($config['amazon_ses'])) {
				throw new Exception\ConfigurationException('No config key "amazon_ses" for SlmMail AmazonSes provided');
			}
			$config = $config['amazon_ses'];
			if (!isset($config['host']) || !isset($config['access_key']) || !isset($config['secret_key'])) {
				throw new Exception\ConfigurationException('Config for "amazon_ses" must contain host, access_key and secret_key');
			}

			$service = new Service\AmazonSes(
				$config['host'],
				$config['access_key'],
				$config['secret_key']
			);
			return $service;
		},
		'SlmMail\Service\ElasticEmail' => function($sm) {
			$config = $sm->get('config');
			$config = $config['slm_mail'];

			if (!isset($config['elastic_email'])) {
				throw new Exception\ConfigurationException('No config key "elastic_email" for SlmMail ElasticEmail provided');
			}
			$config = $config['elastic_email'];
			if (!isset($config['username']) || !isset($config['api_key'])) {
				throw new Exception\ConfigurationException('Config for "elastic_email" must contain username and api_key');
			}

			$service = new Service\ElasticEmail(
				$config['username'],
				$config['api_key']
			);
			return $service;
		},
		'SlmMail\Service\Mailchimp' => function($sm) {
			$config = $sm->get('config');
			$config = $config['slm_mail'];

			if (!isset($config['mailchimp'])) {
				throw new Exception\ConfigurationException('No config key "mailchimp" for SlmMail Mailchimp provided');
			}
			$config = $config['mailchimp'];
			if (!isset($config['api_key'])) {
				throw new Exception\ConfigurationException('Config for "mailchimp" must contain api_key');
			}

			$service = new Service\Mailchimp(
				$config['api_key']
			);
			return $service;
		},
		'SlmMail\Service\Postage' => function($sm) {
			$config = $sm->get('config');
			$config = $config['slm_mail'];

			if (!isset($config['postage'])) {
				throw new Exception\ConfigurationException('No config key "postage" for SlmMail Postage provided');
			}
			$config = $config['postage'];
			if (!isset($config['api_key'])) {
				throw new Exception\ConfigurationException('Config for "postage" must contain api_key');
			}

			$service = new Service\Postage(
				$config['api_key']
			);
			return $service;
		},
		'SlmMail\Service\Postmark' => function($sm) {
			$config = $sm->get('config');
			$config = $config['slm_mail'];

			if (!isset($config['postmark'])) {
				throw new Exception\ConfigurationException('No config key "postmark" for SlmMail Postmark provided');
			}
			$config = $config['postmark'];
			if (!isset($config['api_key'])) {
				throw new Exception\ConfigurationException('Config for "postmark" must contain api_key');
			}

			$service = new Service\Postmark(
				$config['api_key']
			);
			return $service;
		},
		'SlmMail\Service\SendGrid' => function($sm) {
			$config = $sm->get('config');
			$config = $config['slm_mail'];

			if (!isset($config['sendgrid'])) {
				throw new Exception\ConfigurationException('No config key "sendgrid" for SlmMail SendGrid provided');
			}
			$config = $config['sendgrid'];
			if (!isset($config['username']) || !isset($config['password'])) {
				throw new Exception\ConfigurationException('Config for "sendgrid" must contain username and password');
			}

			$service = new Service\SendGrid(
				$config['username'],
				$config['password']
			);
			return $service;
		},
	),
);