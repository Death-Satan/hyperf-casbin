<?php
/**
 * This file is part of hyperf components.
 *
 * @link     https://github.com/hyperf/hyperf
 * @contact  2771717608@qq.com
 */
use Donjan\Casbin\Adapters\Mysql\DatabaseAdapter;
use Donjan\Casbin\Watchers\RedisWatcher;

declare(strict_types=1);

return [
    /*
     * Casbin model setting.
     */
    'model' => [
        // Available Settings: "file", "text"
        'config_type' => 'file',
        'config_file_path' => BASE_PATH . '/config/autoload/casbin-rbac-model.conf',
        'config_text' => '',
    ],
    /*
     * Casbin adapter .
     */
    'adapter' => [
        'class' => DatabaseAdapter::class,
        'constructor' => [
            'tableName' => 'casbin_rule',
        ],
    ],
    /*
     * Casbin watcher
     */
    'watcher' => [
        'enabled' => false,
        'class' => RedisWatcher::class,
        'constructor' => [
            'channel' => 'casbin',
        ],
    ],
    'log' => [
        'enabled' => false,
    ],
];
