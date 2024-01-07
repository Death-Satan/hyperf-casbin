<?php

declare(strict_types=1);
/**
 * This file is part of hyperf components.
 *
 * @link     https://github.com/hyperf/hyperf
 * @contact  2771717608@qq.com
 */

namespace Donjan\Casbin\Tests;

use Donjan\Casbin\Adapters\Mysql\DatabaseAdapter;
use Donjan\Casbin\Enforcer;
use Donjan\Casbin\EnforcerFactory;
use Donjan\Casbin\Listener\OnPipeMessageListener;
use Donjan\Casbin\Listener\OnPolicyChangedListener;
use Donjan\Casbin\Watchers\RedisWatcher;
use Hyperf\Contract\ApplicationInterface;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Database\Schema\Schema;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected $container;

    protected $config;

    protected $app;

    protected function setUp(): void
    {
        $this->container = \Hyperf\Context\ApplicationContext::getContainer();
        $this->container->define(\Casbin\Enforcer::class, EnforcerFactory::class);
        $this->config = $this->container->get(ConfigInterface::class);
        $this->app = $this->container->get(ApplicationInterface::class);
        $this->initConfig();
        $this->initTable();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists(\Hyperf\Config\config('casbin.adapter.constructor.tableName'));
        $this->delDir(BASE_PATH . '/runtime/container');
        \Mockery::close();
    }

    public function delDir($path)
    {
        if (is_dir($path)) {
            $dirs = scandir($path);
            foreach ($dirs as $dir) {
                if ($dir != '.' && $dir != '..') {
                    $sonDir = $path . '/' . $dir;
                    if (is_dir($sonDir)) {
                        $this->delDir($sonDir);
                        @rmdir($sonDir);
                    } else {
                        @unlink($sonDir);
                    }
                }
            }
            @rmdir($path);
        }
    }

    protected function initConfig()
    {
        $this->config->set('databases', [
            'default' => [
                'driver' => \Hyperf\Support\env('DB_DRIVER', 'mysql'),
                'host' => \Hyperf\Support\env('DB_HOST', 'localhost'),
                'database' => \Hyperf\Support\env('DB_DATABASE', 'hyperf'),
                'port' => \Hyperf\Support\env('DB_PORT', 3306),
                'username' => \Hyperf\Support\env('DB_USERNAME', 'root'),
                'password' => \Hyperf\Support\env('DB_PASSWORD', ''),
                'charset' => \Hyperf\Support\env('DB_CHARSET', 'utf8'),
                'collation' => \Hyperf\Support\env('DB_COLLATION', 'utf8_unicode_ci'),
                'prefix' => \Hyperf\Support\env('DB_PREFIX', ''),
                'pool' => [
                    'min_connections' => 1,
                    'max_connections' => 10,
                    'connect_timeout' => 10.0,
                    'wait_timeout' => 3.0,
                    'heartbeat' => -1,
                    'max_idle_time' => (float) \Hyperf\Support\env('DB_MAX_IDLE_TIME', 60),
                ],
            ],
        ]);
        $this->config->set('casbin', [
            'model' => [
                'config_type' => 'file',
                'config_file_path' => BASE_PATH . '/publish/casbin-rbac-model.conf',
                'config_text' => '',
            ],
            'adapter' => [
                'class' => DatabaseAdapter::class,
                'constructor' => [
                    'tableName' => 'casbin_rule',
                ],
            ],
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
        ]);
        $this->config->set('listeners', [
            OnPipeMessageListener::class,
            OnPolicyChangedListener::class,
        ]);
    }

    protected function initTable()
    {
        Enforcer::getAdapter()->initTable();
    }
}
