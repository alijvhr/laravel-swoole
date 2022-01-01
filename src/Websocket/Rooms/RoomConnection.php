<?php

namespace SwooleTW\Http\Websocket\Rooms;

use ArrayObject;
use Illuminate\Contracts\Support\Arrayable;
use Sparrow\Setting\Models\Setting;
use Swoole\Table;

class RoomConnection
{
    public int $worker;

    public function __construct(protected $id)
    {
        $this->worker = $this->id % config('swoole_http.server.options.task_worker_num');
    }

    public function __call(string $name, array $arguments)
    {
        app('swoole.server')->task(['roomController.call', [$this->id, $name, $arguments]], $this->worker);
    }
}