<?php

declare(strict_types=1);
/**
 * This file is part of hyperf components.
 *
 * @link     https://github.com/hyperf/hyperf
 * @contact  2771717608@qq.com
 */

namespace Donjan\Casbin;

use Casbin\Enforcer;
use Donjan\Casbin\Listener\OnPipeMessageListener;
use Donjan\Casbin\Listener\OnPolicyChangedListener;
use Donjan\Casbin\Process\CasbinProcess;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                Enforcer::class => EnforcerFactory::class,
            ],
            'listeners' => [
                OnPipeMessageListener::class,
                OnPolicyChangedListener::class,
            ],
            'processes' => [
                CasbinProcess::class,
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for casbin.',
                    'source' => __DIR__ . '/../publish/casbin.php',
                    'destination' => BASE_PATH . '/config/autoload/casbin.php',
                ],
                [
                    'id' => 'model',
                    'description' => 'The model for casbin.',
                    'source' => __DIR__ . '/../publish/casbin-rbac-model.conf',
                    'destination' => BASE_PATH . '/config/autoload/casbin-rbac-model.conf',
                ],
            ],
        ];
    }
}
