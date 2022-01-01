<?php

namespace SwooleTW\Http\Controllers;

class RoomController
{

    protected array $rooms = [];

    public function createRoom(int $id, array $options, string $room = 'Room')
    {
        $this->rooms[$id] = app()->make($room, [$options]);
    }

    public function call(int $id, string $method, array $arguments)
    {
        if (!isset($this->rooms[$id])) return;
        $room = $this->rooms[$id];
        if (is_callable([$room, $method])) $room->$method($arguments);
    }

}