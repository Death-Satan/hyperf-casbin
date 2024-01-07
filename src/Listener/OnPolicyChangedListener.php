<?php

declare(strict_types=1);
/**
 * This file is part of hyperf components.
 *
 * @link     https://github.com/hyperf/hyperf
 * @contact  2771717608@qq.com
 */

namespace Donjan\Casbin\Listener;

use Donjan\Casbin\Event\PipeMessage;
use Donjan\Casbin\Event\PolicyChanged;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Process\ProcessCollector;
use Hyperf\Server\ServerManager;
use Psr\Container\ContainerInterface;
use Swoole\Server;

class OnPolicyChangedListener implements ListenerInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function listen(): array
    {
        return [
            PolicyChanged::class,
        ];
    }

    public function process(object $event): void
    {
        if (\Hyperf\Config\config('casbin.watcher.enabled')) { // 启用watcher，不响应此事件
            return;
        }
        $serverManager = $this->container->get(ServerManager::class);
        if (count($serverManager::list()) > 0 && $event instanceof PolicyChanged) {
            $server = $this->container->get(Server::class);
            $workerCount = $server->setting['worker_num'] + ($server->setting['task_worker_num'] ?? 0) - 1;
            if ($workerCount > 0) {
                for ($workerId = 0; $workerId <= $workerCount; ++$workerId) {
                    if ($server->worker_id > -1 && $server->worker_id != $workerId) {
                        $server->sendMessage(new PipeMessage(PipeMessage::LOAD_POLICY), $workerId);
                    }
                }
            }
            if (class_exists(ProcessCollector::class) && ! ProcessCollector::isEmpty()) {
                $processes = ProcessCollector::all();
                if ($processes) {
                    $string = serialize(new PipeMessage(PipeMessage::LOAD_POLICY));
                    foreach ($processes as $process) {
                        $process->exportSocket()->send($string, 10);
                    }
                }
            }
        }
    }
}
