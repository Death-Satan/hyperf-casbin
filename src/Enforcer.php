<?php

declare(strict_types=1);
/**
 * This file is part of hyperf components.
 *
 * @link     https://github.com/hyperf/hyperf
 * @contact  2771717608@qq.com
 */

namespace Donjan\Casbin;

use Casbin\Enforcer as BaseEnforcer;
use Psr\Container\ContainerInterface;

/**
 * Enforcer.
 * @method static bool enforce(...$rvals)
 * @method static array getRolesForUser(string $name, string ...$domain)
 * @method static array getUsersForRole(string $name, string ...$domain)
 * @method static bool hasRoleForUser(string $name, string $role, string ...$domain)
 * @method static bool addRoleForUser(string $user, string $role, string ...$domain)
 * @method static bool deleteRoleForUser(string $user, string $role, string ...$domain)
 * @method static bool deleteRolesForUser(string $user, string ...$domain)
 * @method static bool deleteUser(string $user)
 * @method static bool deleteRole(string $role)
 * @method static bool deletePermission(string ...$permission)
 * @method static bool addPermissionForUser(string $user, string ...$permission)
 * @method static bool deletePermissionForUser(string $user, string ...$permission)
 * @method static bool deletePermissionsForUser(string $user)
 * @method static array getPermissionsForUser(string $user)
 * @method static bool hasPermissionForUser(string $user, string ...$permission)
 * @method static array getImplicitRolesForUser(string $name, string ...$domain)
 * @method static array getImplicitPermissionsForUser(string $user, string ...$domain)
 * @method static array getImplicitUsersForPermission(string ...$permission)
 * @method static array getUsersForRoleInDomain(string $name, string $domain)
 * @method static array getRolesForUserInDomain(string $name, string $domain)
 * @method static array getPermissionsForUserInDomain(string $name, string $domain)
 * @method static bool addRoleForUserInDomain(string $user, string $role, string $domain)
 * @method static bool deleteRoleForUserInDomain(string $user, string $role, string $domain)
 */
class Enforcer
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __call($method, $parameters)
    {
        return $this->container->get(BaseEnforcer::class)->{$method}(...$parameters);
    }

    /**
     * @param string $method
     * @param array $parameters
     *
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return \Hyperf\Context\ApplicationContext::getContainer()->get(BaseEnforcer::class)->{$method}(...$parameters);
    }
}
