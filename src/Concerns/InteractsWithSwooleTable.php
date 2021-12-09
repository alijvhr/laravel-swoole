<?php

namespace SwooleTW\Http\Concerns;

use Illuminate\Contracts\Console\Application as ConsoleApp;
use Illuminate\Support\Facades\App;
use Swoole\Table;
use SwooleTW\Http\Table\SwooleTable;

/**
 * Trait InteractsWithSwooleTable
 *
 * @property \Illuminate\Contracts\Container\Container $container
 * @property \Illuminate\Contracts\Container\Container $app
 */
trait InteractsWithSwooleTable
{
    /**
     * @var \SwooleTW\Http\Table\SwooleTable
     */
    protected $currentTable;

    /**
     * Register customized swoole talbes.
     */
    protected function createTables()
    {
        $this->bindSwooleTable();
        $this->currentTable = new SwooleTable;
        $this->registerTables();
    }

    /**
     * Register user-defined swoole tables.
     */
    protected function registerTables()
    {
        $tables = $this->container->make('config')->get('swoole_http.tables', []);
        $defaults = [
            'params' => [
                'size' => 250,
                'columns' => [
                    ['name' => 'value', 'type' => Table::TYPE_STRING, 'size' => 4096],
                    ['name' => 'counter', 'type' => Table::TYPE_INT],
                ]
            ],
            'rooms' => [
                'size' => 4096,
                'columns' => [
                    ['name' => 'type', 'type' => Table::TYPE_STRING, 'size' => 128],
                    ['name' => 'worker', 'type' => Table::TYPE_INT],
                    ['name' => 'params', 'type' => Table::TYPE_STRING, 'size' => 102400],
                    ['name' => 'subscribers', 'type' => Table::TYPE_STRING, 'size' => 5500],
                    ['name' => 'subscribers_count', 'type' => Table::TYPE_INT],
                ]
            ],
        ];
        $tables = array_merge($defaults, $tables);
        foreach ($tables as $key => $value) {
            $table = new Table($value['size']);
            $columns = $value['columns'] ?? [];
            foreach ($columns as $column) {
                if (isset($column['size'])) {
                    $table->column($column['name'], $column['type'], $column['size']);
                } else {
                    $table->column($column['name'], $column['type']);
                }
            }
            $table->create();

            $this->currentTable->add($key, $table);
        }
    }

    /**
     * Bind swoole table to Laravel app container.
     */
    protected function bindSwooleTable()
    {
        $dest = $this->app?? $this->container;
        if (!$dest->bound('swoole.table')) {
            $dest->singleton(SwooleTable::class, function () {
                return $this->currentTable;
            });

            $dest->alias(SwooleTable::class, 'swoole.table');
        }
    }
}
