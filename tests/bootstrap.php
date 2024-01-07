<?php

declare(strict_types=1);
/**
 * This file is part of hyperf components.
 *
 * @link     https://github.com/hyperf/hyperf
 * @contact  2771717608@qq.com
 */
error_reporting(E_ALL);
date_default_timezone_set('Asia/Shanghai');

use Hyperf\Config\Config;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSourceFactory;

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__, 1));
! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', SWOOLE_HOOK_ALL);

Swoole\Runtime::enableCoroutine(true);

require BASE_PATH . '/vendor/autoload.php';

$container = new Container((new DefinitionSourceFactory(true))());
$container->set(ConfigInterface::class, $config = new Config([]));

Hyperf\Context\ApplicationContext::setContainer($container);

$container->get(Hyperf\Contract\ApplicationInterface::class);
