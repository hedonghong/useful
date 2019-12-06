<?php


namespace App\Clients\Mq;

use App\Exceptions\MqException;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPChannelClosedException;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class RabbitMqClient
{
    const EXCHANGE_TYPES = [
        'direct'  => AMQPExchangeType::DIRECT,
        'fanout'  => AMQPExchangeType::FANOUT,
        'topic'   => AMQPExchangeType::TOPIC,
        'headers' => AMQPExchangeType::HEADERS,
    ];

    protected $config = [];

    protected $queueConfigName;

    protected $queueConfig = [];

    /**
     * @var AMQPStreamConnection
     */
    protected $amqpConnection = null;

    /**
     * @var \PhpAmqpLib\Channel\AMQPChannel
     */
    protected $channel = null;

    protected $consumeMsg = null;

    /**
     * 延迟队列标识
     * @var bool
     */
    protected $isDelay = false;

    /**
     * 延迟队列的配置
     * @var array
     */
    protected $delayQueueConfig = [];

    /**
     * @throws \Exception
     */
    public function shutdown()
    {
        try {
            if (!empty($this->channel)) {
                $this->channel->close();
            }
            if (!empty($this->amqpConnection)) {
                $this->amqpConnection->close();
            }
            $this->channel        = null;
            $this->amqpConnection = null;
        } catch (\Exception $e) {}
    }

    /**
     * RabbitMqClient constructor.
     * @param string $queueConfigName
     */
    public function __construct($queueConfigName = '')
    {
        $this->queueConfigName = $queueConfigName;
        register_shutdown_function([$this, 'shutdown']);
        $this->_setConfigs();
    }

    private function _setConfigs()
    {
        $configs           = app('config')->get('rabbitmq');
        $configObj         = new RabbitMqConfig($this->queueConfigName, $configs);
        $this->config      = $configObj->getAccount();
        $this->queueConfig = $configObj->getQueue();
        $this->isDelay     = !empty($this->queueConfig['delay_queue']) ? true : false;
        if ($this->isDelay) {
            $delayObj = new RabbitMqConfig($this->queueConfig['delay_queue'], $configs);
            $this->delayQueueConfig = $delayObj->getQueue();
        }
        return $this;
    }

    private function _initCheck()
    {
        if (empty($this->config['host'])
            || empty($this->config['port'])
            || empty($this->config['user'])
            || empty($this->config['password'])
            || empty($this->queueConfig['vhost']['name'])
        ) {
            throw new MqException('连接MQ配置信息异常');
        }
        $this->amqpConnection = new AMQPStreamConnection(
            $this->config['host'],
            $this->config['port'],
            $this->config['user'],
            $this->config['password'],
            $this->queueConfig['vhost']['name'],
        );
        if (!$this->amqpConnection->isConnected()) {
            throw new MqException('建立连接失败');
        }
        $this->channel = $this->amqpConnection->channel();
        if (!$this->channel->is_open()) {
            throw new MqException('建立通道失败');
        }
    }

    private function _initSend()
    {
        if (!empty($this->amqpConnection)) {
            return $this;
        }
        $this->_initCheck();

        $this->channel->exchange_declare($this->queueConfig['exchange']['name'],
        $this->_getExchangeType($this->queueConfig['exchange']['type']), false,
        $this->queueConfig['exchange']['durable'], $this->queueConfig['exchange']['auto_delete']);

        $table = null;
        if ($this->isDelay) {
            $this->channel->exchange_declare($this->delayQueueConfig['exchange']['name'],
                $this->_getExchangeType($this->delayQueueConfig['exchange']['type']), false,
                $this->delayQueueConfig['exchange']['durable'], $this->delayQueueConfig['exchange']['auto_delete']);
            $table = new AMQPTable();
            $table->set('x-dead-letter-exchange', $this->delayQueueConfig['exchange']['name']);
            $table->set('x-dead-letter-routing-key',$this->delayQueueConfig['queue']['routing_key']);
            //$tale->set('x-message-ttl',10000);//对队列设置最大过期时间
        }

        $this->channel->queue_declare($this->queueConfig['queue']['name'], false,
            $this->queueConfig['queue']['durable'], false, $this->queueConfig['queue']['auto_delete'],
            false, $table ?? []);
        $this->channel->queue_bind($this->queueConfig['queue']['name'],
            $this->queueConfig['exchange']['name'], $this->queueConfig['queue']['name']);

        if ($this->isDelay) {
            $this->channel->queue_declare($this->delayQueueConfig['queue']['name'], false,
                $this->delayQueueConfig['queue']['durable'], false, $this->delayQueueConfig['queue']['auto_delete']);
            $this->channel->queue_bind($this->delayQueueConfig['queue']['name'],
                $this->delayQueueConfig['exchange']['name'], $this->delayQueueConfig['queue']['name']);
        }

        return $this;
    }

    private function _initReceive()
    {
        if (!empty($this->amqpConnection)) {
            return $this;
        }
        $this->_initCheck();
        if ($this->isDelay) {
            $this->channel->queue_declare($this->delayQueueConfig['queue']['name'], false,
                $this->delayQueueConfig['queue']['durable'], false, $this->delayQueueConfig['queue']['auto_delete']);
            $this->channel->queue_bind($this->delayQueueConfig['queue']['name'],
                $this->delayQueueConfig['exchange']['name'], $this->delayQueueConfig['queue']['name']);
        } else {
            $this->channel->queue_declare($this->queueConfig['queue']['name'], false,
                $this->queueConfig['queue']['durable'], false, $this->queueConfig['queue']['auto_delete']);
            $this->channel->queue_bind($this->queueConfig['queue']['name'],
                $this->queueConfig['exchange']['name'], $this->queueConfig['queue']['name']);
        }
        return $this;
    }

    private function _getExchangeType($type)
    {
        return self::EXCHANGE_TYPES[$type] ?? '';
    }

    private function _oneSend($data)
    {
        $this->channel->basic_publish($data, $this->queueConfig['exchange']['name'],
            $this->queueConfig['queue']['routing_key']);
    }

    private function _batchSend(array $datas = [])
    {
        foreach ($datas as $data) {
            $this->channel->batch_basic_publish($data, $this->queueConfig['exchange']['name'],
                $this->queueConfig['queue']['routing_key']);
        }
        $this->channel->publish_batch();
    }

    private function _send($message, $isBatchSend = false)
    {
        $this->_initSend();
        try {
            if ($isBatchSend) {
                $this->_batchSend($message);
            } else {
                $this->_oneSend($message);
            }
            return true;
        } catch (\Exception $e) {
            if ($e instanceof AMQPChannelClosedException) {
                $times = $this->queueConfig['publish']['fail_reconnect_try_time'] ?? 0;
                if (!$times) {
                    throw $e;
                }
                while ($times) {
                    $this->shutdown();
                    $this->initSend();
                    try {
                        if ($isBatchSend) {
                            $this->_batchSend($message);
                        } else {
                            $this->_oneSend($message);
                        }
                        return true;
                    } catch (\Exception $ex) {
                        if (!($ex instanceof AMQPChannelClosedException)) {
                            throw $ex;
                        }
                    }
                    $times--;
                }

            }
            throw $e;
        }
    }

    public function send($data, array $config = [])
    {
        if ($this->queueConfig['message']['type'] == 'string') {
            if (!is_string($data)) {
                throw new MqException('发送的消息格式有误');
            }
        } elseif ($this->queueConfig['message']['type'] == 'json') {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        //@todo 设置消息设置最好搞一个类或者方法
        $msgSet = [
            'delivery_mode' => $this->queueConfig['message']['delivery_mode'],
            'content_type'  => $this->queueConfig['message']['content_type'],
        ];
        is_numeric($config['expiration']) && $msgSet['expiration'] = $config['expiration']*1000;
        $msg = new AMQPMessage($data, $msgSet);
        return $this->_send($msg);
    }

    public function batchSend(array $datas = [])
    {
        return $this->_send(array_map(function ($data) {
            $actulData  = $data['data'];
            $actulCofig = $data['config'];
            if ($this->queueConfig['message']['type'] == 'string') {
                if (!is_string($data)) {
                    throw new MqException('发送的消息格式有误');
                }
            } elseif ($this->queueConfig['message']['type'] == 'json') {
                $actulData = json_encode($actulData);
            }
            $msgSet = [
                'delivery_mode' => $this->queueConfig['message']['delivery_mode'],
                'content_type'  => $this->queueConfig['message']['content_type'],
            ];
            is_numeric($actulCofig['expiration']) && $msgSet['expiration'] = $actulCofig['expiration']*1000;
            return new AMQPMessage($actulData, $msgSet);
        }, $datas), true);
    }

    public function receive($callBack = [])
    {
        $this->_initReceive();
        if (empty($callBack[0])) {
            throw new MqException(' 缺少接受消息回调类');
        }
        if (empty($callBack[1])) {
            throw new MqException(' 缺少接受消息回调方法');
        }
        $callBackFunc = function ($msg) use ($callBack) {
            $this->consumeMsg = $msg;
            $data = $msg->body;
            if ($this->queueConfig['message']['type'] == 'json') {
                $data = json_decode($data, true, 512, JSON_BIGINT_AS_STRING);
            }
            call_user_func_array($callBack, [$data, $this]);
            $this->consumeMsg = null;
        };
        $this->channel->basic_consume(
            $this->isDelay ? $this->delayQueueConfig['queue']['name'] : $this->queueConfig['queue']['name'],
            '', false,false,false,false,
            $callBackFunc
        );
        while(count($this->channel->callbacks)) {
            $this->channel->wait(null, false, $this->queueConfig['consume']['time_out']);
        }
        $this->shutdown();
    }

    public function ack()
    {
        if (!empty($this->consumeMsg)) {
            $this->consumeMsg->delivery_info['channel']
                ->basic_ack($this->consumeMsg->delivery_info['delivery_tag']);
        }
    }
}