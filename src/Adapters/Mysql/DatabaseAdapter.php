<?php

declare(strict_types=1);
/**
 * This file is part of hyperf components.
 *
 * @link     https://github.com/hyperf/hyperf
 * @contact  2771717608@qq.com
 */

namespace Donjan\Casbin\Adapters\Mysql;

use Casbin\Exceptions\InvalidFilterTypeException;
use Casbin\Model\Model;
use Casbin\Persist\Adapter;
use Casbin\Persist\AdapterHelper;
use Casbin\Persist\Adapters\Filter;
use Casbin\Persist\BatchAdapter;
use Casbin\Persist\FilteredAdapter;
use Casbin\Persist\UpdatableAdapter;
use Donjan\Casbin\Event\PolicyChanged;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;
use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * DatabaseAdapter.
 */
class DatabaseAdapter implements Adapter, BatchAdapter, UpdatableAdapter, FilteredAdapter
{
    use AdapterHelper;

    /**
     * Rules eloquent model.
     *
     * @var Rule
     */
    protected $eloquent;

    /**
     * Db.
     * @var Db
     */
    protected $db;

    /**
     * Db.
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * tableName.
     * @var tableName
     */
    protected $tableName;

    /**
     * @var bool
     */
    private $filtered = false;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * the DatabaseAdapter constructor.
     *
     * @param mixed $tableName
     */
    public function __construct(ContainerInterface $container, $tableName)
    {
        $this->tableName = $tableName;
        $this->eloquent = \Hyperf\Support\make(Rule::class, ['attributes' => [], 'table' => $this->tableName]);
        $this->container = $container;
        $this->db = $this->container->get(Db::class);
        $this->eventDispatcher = $this->container->get(EventDispatcherInterface::class);
        $this->initTable();
    }

    public function initTable()
    {
        if (! Schema::hasTable($this->tableName)) {
            Schema::create($this->tableName, function (Blueprint $table) {
                $table->increments('id');
                $table->string('ptype')->nullable();
                $table->string('v0')->nullable();
                $table->string('v1')->nullable();
                $table->string('v2')->nullable();
                $table->string('v3')->nullable();
                $table->string('v4')->nullable();
                $table->string('v5')->nullable();
            });
        }
    }

    /**
     * savePolicyLine function.
     */
    public function savePolicyLine(string $ptype, array $rule)
    {
        $col['ptype'] = $ptype;
        foreach ($rule as $key => $value) {
            $col['v' . strval($key)] = $value;
        }
        return $col;
    }

    /**
     * loads all policy rules from the storage.
     */
    public function loadPolicy(Model $model): void
    {
        $rows = $this->eloquent->select('ptype', 'v0', 'v1', 'v2', 'v3', 'v4', 'v5')->get()->toArray();

        foreach ($rows as $row) {
            $line = implode(', ', array_filter($row, function ($val) {
                return $val != '' && ! is_null($val);
            }));
            $this->loadPolicyLine(trim($line), $model);
        }
    }

    /**
     * saves all policy rules to the storage.
     */
    public function savePolicy(Model $model): void
    {
        foreach ($model['p'] as $ptype => $ast) {
            foreach ($ast->policy as $rule) {
                $row = $this->savePolicyLine($ptype, $rule);
                $this->eloquent->create($row);
            }
        }

        foreach ($model['g'] as $ptype => $ast) {
            foreach ($ast->policy as $rule) {
                $row = $this->savePolicyLine($ptype, $rule);
                $this->eloquent->create($row);
            }
        }
        $this->eventDispatcher->dispatch(new PolicyChanged(__METHOD__, func_get_args()));
    }

    /**
     * adds a policy rule to the storage.
     * This is part of the Auto-Save feature.
     */
    public function addPolicy(string $sec, string $ptype, array $rule): void
    {
        $row = $this->savePolicyLine($ptype, $rule);
        $this->eloquent->create($row);
        $this->eventDispatcher->dispatch(new PolicyChanged(__METHOD__, func_get_args()));
    }

    /**
     * Adds a policy rules to the storage.
     * This is part of the Auto-Save feature.
     *
     * @param string[][] $rules
     */
    public function addPolicies(string $sec, string $ptype, array $rules): void
    {
        $rows = [];
        foreach ($rules as $rule) {
            $rows[] = $this->savePolicyLine($ptype, $rule);
        }
        $this->eloquent->insert($rows);
        $this->eventDispatcher->dispatch(new PolicyChanged(__METHOD__, func_get_args()));
    }

    /**
     * This is part of the Auto-Save feature.
     */
    public function removePolicy(string $sec, string $ptype, array $rule): void
    {
        $query = $this->eloquent->where('ptype', $ptype);
        foreach ($rule as $key => $value) {
            $query->where('v' . strval($key), $value);
        }
        $query->delete();
        $this->eventDispatcher->dispatch(new PolicyChanged(__METHOD__, func_get_args()));
    }

    /**
     * Removes policy rules from the storage.
     * This is part of the Auto-Save feature.
     *
     * @param string[][] $rules
     */
    public function removePolicies(string $sec, string $ptype, array $rules): void
    {
        $this->db->beginTransaction();
        try {
            foreach ($rules as $rule) {
                $this->removePolicy($sec, $ptype, $rule);
            }
            $this->db->commit();
            $this->eventDispatcher->dispatch(new PolicyChanged(__METHOD__, func_get_args()));
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * RemoveFilteredPolicy removes policy rules that match the filter from the storage.
     * This is part of the Auto-Save feature.
     */
    public function removeFilteredPolicy(string $sec, string $ptype, int $fieldIndex, string ...$fieldValues): void
    {
        $query = $this->eloquent->where('ptype', $ptype);
        foreach (range(0, 5) as $value) {
            if ($fieldIndex <= $value && $value < $fieldIndex + count($fieldValues)) {
                if ($fieldValues[$value - $fieldIndex] != '') {
                    $query->where('v' . strval($value), $fieldValues[$value - $fieldIndex]);
                }
            }
        }
        $query->delete();
        $this->eventDispatcher->dispatch(new PolicyChanged(__METHOD__, func_get_args()));
    }

    /**
     * Updates a policy rule from storage.
     * This is part of the Auto-Save feature.
     *
     * @param string[] $oldRule
     * @param string[] $newPolicy
     */
    public function updatePolicy(string $sec, string $ptype, array $oldRule, array $newPolicy): void
    {
        $query = $this->eloquent->where('ptype', $ptype);
        foreach ($oldRule as $k => $v) {
            $query->where('v' . $k, $v);
        }
        $update = [];
        foreach ($newPolicy as $k => $v) {
            $update['v' . $k] = $v;
        }
        $query->update($update);
        $this->eventDispatcher->dispatch(new PolicyChanged(__METHOD__, func_get_args()));
    }

    /**
     * UpdatePolicies updates some policy rules to storage, like db, redis.
     *
     * @param string[][] $oldRules
     * @param string[][] $newRules
     */
    public function updatePolicies(string $sec, string $ptype, array $oldRules, array $newRules): void
    {
        $this->db->beginTransaction();
        try {
            foreach ($oldRules as $i => $oldRule) {
                $this->updatePolicy($sec, $ptype, $oldRule, $newRules[$i]);
            }
            $this->db->commit();
            $this->eventDispatcher->dispatch(new PolicyChanged(__METHOD__, func_get_args()));
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * UpdateFilteredPolicies deletes old rules and adds new rules.
     */
    public function updateFilteredPolicies(string $sec, string $ptype, array $newPolicies, int $fieldIndex, string ...$fieldValues): array
    {
        $query = $this->eloquent->where('ptype', $ptype);
        foreach (range(0, 5) as $idx) {
            if ($fieldIndex <= $idx && $idx < $fieldIndex + count($fieldValues)) {
                $value = $fieldValues[$idx - $fieldIndex];
                if ($value) {
                    $query->where('v' . strval($idx), $value);
                }
            }
        }
        $wheres = \Hyperf\Collection\collect($query->getQuery()->wheres);
        $wheres->shift(); // remove ptype
        $oldRules = [];
        $oldRules[] = $wheres->pluck('value')->all();
        $this->db->beginTransaction();
        try {
            $this->addPolicies($sec, $ptype, $newPolicies);
            $query->delete();
            $this->db->commit();
            $this->eventDispatcher->dispatch(new PolicyChanged(__METHOD__, func_get_args()));
            return $oldRules;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Loads only policy rules that match the filter.
     *
     * @param mixed $filter
     */
    public function loadFilteredPolicy(Model $model, $filter): void
    {
        $query = $this->eloquent->newQuery();

        if (is_string($filter)) {
            $query->whereRaw($filter);
        } elseif ($filter instanceof Filter) {
            foreach ($filter->p as $k => $v) {
                $query->where($v, $filter->g[$k]);
            }
        } elseif ($filter instanceof \Closure) {
            $query->where($filter);
        } else {
            throw new InvalidFilterTypeException('invalid filter type');
        }
        $rows = $query->get()->makeHidden(['id'])->toArray();
        foreach ($rows as $row) {
            $row = array_filter($row, function ($value) {
                return ! is_null($value) && $value !== '';
            });
            $line = implode(', ', array_filter($row, function ($val) {
                return $val != '' && ! is_null($val);
            }));
            $this->loadPolicyLine(trim($line), $model);
        }
        $this->setFiltered(true);
    }

    /**
     * Returns true if the loaded policy has been filtered.
     */
    public function isFiltered(): bool
    {
        return $this->filtered;
    }

    /**
     * Sets filtered parameter.
     */
    public function setFiltered(bool $filtered): void
    {
        $this->filtered = $filtered;
    }
}
