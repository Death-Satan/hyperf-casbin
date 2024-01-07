<?php

declare(strict_types=1);
/**
 * This file is part of hyperf components.
 *
 * @link     https://github.com/hyperf/hyperf
 * @contact  2771717608@qq.com
 */

namespace Donjan\Casbin\Process;

use Donjan\Casbin\Event\PipeMessage;
use Hyperf\Process\AbstractProcess;
use Hyperf\Redis\Redis;
use Psr\Container\ContainerInterface;
use Swoole\Server;

class CasbinProcess extends AbstractProcess
{
    /**
     * @var Server
     */
    protected $server;

    public function __construct(protected ContainerInterface $container)
    {
        $this->name = 'casbin-watcher';
        parent::__construct($container);
    }

    public function handle(): void
    {
        $redis = $this->container->get(Redis::class);
        $redis->setOption(\Redis::OPT_READ_TIMEOUT, -1);
        $channel = \Hyperf\Config\config('casbin.watcher.constructor.channel') ?? 'casbin';
        $redis->subscribe([$channel], function ($instance, $channel, $message) {
            $server = $this->server;
            $workerCount = $server->setting['worker_num'] + ($server->setting['task_worker_num'] ?? 0) - 1;
            for ($workerId = 0; $workerId <= $workerCount; ++$workerId) {
                $server->sendMessage(new PipeMessage(PipeMessage::LOAD_POLICY), $workerId);
            }
        });
    }

    public function bind($server): void
    {
        $this->server = $server;
        parent::bind($server);
    }

    public function isEnable($server): bool
    {
        return \Hyperf\Config\config('casbin.watcher.enabled') == true;
    }
}
