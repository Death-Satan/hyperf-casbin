<?php

declare(strict_types=1);
/**
 * This file is part of hyperf components.
 *
 * @link     https://github.com/hyperf/hyperf
 * @contact  2771717608@qq.com
 */

namespace Donjan\Casbin\Event;

class PipeMessage
{
    public const LOAD_POLICY = 'loadPolicy';

    protected $data = [];

    protected $action;

    public function __construct($action, $data = [])
    {
        $this->action = $action;
        $this->data = $data;
    }

    public function __get($name)
    {
        return $this->{$name};
    }

    public function __set($name, $value)
    {
        $this->{$name} = $value;
    }
}
