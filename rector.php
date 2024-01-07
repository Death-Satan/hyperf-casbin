<?php

declare(strict_types=1);
/**
 * This file is part of hyperf components.
 *
 * @link     https://github.com/hyperf/hyperf
 * @contact  2771717608@qq.com
 */
use Rector\Config\RectorConfig;
use Rector\Hyperf\Set\HyperfSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->sets([
        HyperfSetList::HYPERF_31,
    ]);
};
