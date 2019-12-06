<?php

namespace App\Clients\Mq;

class RabbitMqConfig {

    protected $config = [];

    protected $queueName = null;

    public function __construct($queueName, array $config = [])
    {
        $this->queueName = $queueName;
        $this->config    = $config;
    }

    private function _getAccount()
    {
        return [
            'host'     => '',
            'port'     => '5672',
            'user'     => '',
            'password' => '',
        ];
    }

    public function getAccount()
    {
        return array_merge($this->_getAccount(), $this->config['account'] ?? []);
    }


    private function _getQueue()
    {
        return [
            'vhost'       => ['name' => ''],
            'exchange'    => ['name' => 'amq.direct', 'type' => 'direct', 'durable' => true, 'auto_delete' => false],
            'queue'       => ['name' => '', 'routing_key' => '', 'durable' => true, 'auto_delete' => false],
            'message'     => ['delivery_mode' => 2, 'content_type' => 'text/plain', 'type' => 'string'],
            'publish'     => ['fail_reconnect_try_time' => 0],
            'consume'     => ['time_out' => 2],
            'delay_queue' => '',
        ];
    }

    public function getQueue()
    {
        $config  = [];
        $default = $this->_getQueue();
        foreach ($this->config['queues'][$this->queueName] ?? [] as $key => $item) {
            if (isset($default[$key])) {
                if (is_array($default[$key])) {
                    $config[$key] = array_merge($default[$key], $item);
                } else {
                    $config[$key] = $item;
                }
            } else {
                $config[$key] = $item;
            }
        }
        return $config ?? $default;
    }


}
/*
return [
    'account' => [
        'host'     => '192.168.2.22',
        'port'     => '5672',
        'user'     => 'php-nurse',
        'password' => 'php-nurse',
    ],
    'queues' => [
        'broadcastMessage' => [
            'vhost'    => ['name' => 'web_nurse'],
            'exchange' => ['name' => 'amq.direct', 'type' => 'direct', 'durable' => true, 'auto_delete' => false],
            'queue'    => ['name' => 'broadcastMessage', 'routing_key' => 'broadcastMessage', 'durable' => true, 'auto_delete' => false],
            'message'  => ['delivery_mode' => 2, 'content_type' => 'text/plain', 'type' => 'string'],
            'publish'  => ['fail_reconnect_try_time' => 1],
            'consume'  => ['time_out' => 2],
        ],
    ],
];
*/