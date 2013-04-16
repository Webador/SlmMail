<?php

return array(
    'slm_mail' => array(
        'mandrill' => array(
            'key' => 'my-secret-key'
        ),

        'postage' => array(
            'key' => 'my-secret-key'
        ),

        'postmark' => array(
            'key' => 'my-secret-key'
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
