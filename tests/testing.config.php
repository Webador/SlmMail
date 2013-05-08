<?php

return array(
    'slm_mail' => array(
        'alpha_mail' => array(
            'username' => 'my-username',
            'key'      => 'my-secret-key'
        ),

        'elastic_email' => array(
            'username' => 'my-username',
            'key'      => 'my-secret-key'
        ),

        'mandrill' => array(
            'key' => 'my-secret-key'
        ),

        'postage' => array(
            'key' => 'my-secret-key'
        ),

        'postmark' => array(
            'key' => 'my-secret-key'
        ),

        'mailgun' => array(
            'domain' => 'my-domain',
            'key'    => 'my-key'
        ),

        'send_grid' => array(
            'username' => 'my-username',
            'key'      => 'my-key'
        )
    ),

    'aws' => array(
        'services' => array(
            'ses' => array(
                'params' => array(
                    'region' => 'us-east-1'
                )
            )
        )
    )
);
