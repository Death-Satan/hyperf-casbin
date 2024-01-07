<?php

declare(strict_types=1);
/**
 * This file is part of hyperf components.
 *
 * @link     https://github.com/hyperf/hyperf
 * @contact  2771717608@qq.com
 */

namespace Donjan\Casbin\Listener;

use Casbin\Enforcer;
use Donjan\Casbin\Event\PipeMessage;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\OnPipeMessage;
use Hyperf\Process\Event\PipeMessage as UserProcessPipeMessage;
use Psr\Container\ContainerInterface;

class OnPipeMessageListener implements ListenerInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return string[] returns the events that you want to listen
     */
    public function listen(): array
    {
        return [
            OnPipeMessage::class,
            UserProcessPipeMessage::class,
        ];
    }

    /**
     * Handle the Event when the event is triggered, all listeners will
     * complete before the event is returned to the EventDispatcher.
     */
    public function process(object $event): void
    {
        if (($event instanceof OnPipeMessage || $event instanceof UserProcessPipeMessage) && $event->data instanceof PipeMessage) {
            $message = $event->data;
            switch ($message->action) {
                case PipeMessage::LOAD_POLICY:
                    $this->container->get(Enforcer::class)->loadPolicy();
                    break;
            }
        }
    }
}
