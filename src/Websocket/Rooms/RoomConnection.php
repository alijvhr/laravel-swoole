<?php

namespace SwooleTW\Http\Websocket\Rooms;

use SwooleTW\Http\Controllers\RoomController;

class RoomConnection
{
    protected int $worker;

    public function __construct(protected $id)
    {
        $this->worker = $this->id % config('swoole_http.server.options.task_worker_num');
    }

    public function __call(string $name, array $arguments)
    {
        app('swoole.server')->task(['method' => RoomController::class . '@call', 'data' => [$this->id, $name, $arguments]], $this->getWorker());
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getWorker(): int
    {
        return $this->worker;
    }
}