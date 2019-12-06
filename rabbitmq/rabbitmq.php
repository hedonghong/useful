<?php
/**
 * composer require php-amqplib/php-amqplib
 * "php-amqplib/php-amqplib": "^2.9"
 */
return [
    'account' => [
        'host'     => '192.168.2.22',
        'port'     => '5672',
        'user'     => 'php-nurse',
        'password' => 'php-nurse',
    ],
    'queues' => [
        'broadcastMessage' => [
            'vhost'       => ['name' => 'web_nurse'],
            'exchange'    => ['name' => 'amq.direct', 'type' => 'direct', 'durable' => true, 'auto_delete' => false],
            'queue'       => ['name' => 'broadcastMessage', 'routing_key' => 'broadcastMessage', 'durable' => true, 'auto_delete' => false],
            'message'     => ['delivery_mode' => 2, 'content_type' => 'text/plain', 'type' => 'json'],
            'publish'     => ['fail_reconnect_try_time' => 1],
            'consume'     => ['time_out' => 2],
            'delay_queue' => 'delayBroadcastMessage',
        ],
        'delayBroadcastMessage'=> [
            'vhost'       => ['name' => 'web_nurse'],
            'exchange'    => ['name' => 'amq.direct', 'type' => 'direct', 'durable' => true, 'auto_delete' => false],
            'queue'       => ['name' => 'delayBroadcastMessage', 'routing_key' => 'delayBroadcastMessage', 'durable' => true, 'auto_delete' => false],
            'message'     => ['delivery_mode' => 2, 'content_type' => 'text/plain', 'type' => 'json'],
            'publish'     => ['fail_reconnect_try_time' => 1],
            'consume'     => ['time_out' => 2],
            'delay_queue' => '',
        ],
    ],
];