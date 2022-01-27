<?php

namespace SwooleTW\Http\Controllers;

class RoomController
{

    protected array $rooms = [];

    public function create(int $id, array $options, string $room = 'Room')
    {
        $this->rooms[$id] = app()->make($room, ['props' => $options]);
    }

    public function fetch(int $id)
    {
        return $this->rooms[$id] ?? null;
    }

    public function destroy(int $id)
    {
        unset($this->rooms[$id]);
    }

}