<?php
return [
    'slm_mail' => [
        'elastic_email' => [
            'username' => 'my-username',
            'key' => 'my-secret-key'
        ],
        
        'mandrill' => [
            'key' => 'my-secret-key'
        ],
        
        'postage' => [
            'key' => 'my-secret-key'
        ],
        
        'postmark' => [
            'key' => 'my-secret-key'
        ],
        
        'mailgun' => [
            'domain' => 'my-domain',
            'key' => 'my-key',
            'api_endpoint' => 'mailgun-api-endpoint',
        ],
        
        'send_grid' => [
            'username' => 'my-username',
            'key' => 'my-key'
        ],

        'spark_post' => [
            'key' => 'my-secret-key'
        ]
    ],
    
    'aws' => [
        'region' => 'us-east-1',
        'version' => 'latest'
    ]
];
