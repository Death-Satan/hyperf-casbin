<?php

declare(strict_types=1);
/**
 * This file is part of hyperf components.
 *
 * @link     https://github.com/hyperf/hyperf
 * @contact  2771717608@qq.com
 */

namespace Donjan\Casbin\Exceptions;

class UnauthorizedException extends \Exception
{
    /**
     * Create a new exception instance.
     */
    public function __construct()
    {
        parent::__construct('This action is unauthorized.', 403);
    }
}
