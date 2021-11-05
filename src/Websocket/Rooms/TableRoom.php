<?php

namespace SwooleTW\Http\Websocket\Rooms;

use InvalidArgumentException;
use Swoole\Table;
use SwooleTW\Http\Table\SwooleTable;

class TableRoom implements RoomContract
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var Table
     */
    protected Table $fds, $rooms, $online_users;

    /**
     * TableRooms constructor.
     */
    public function __construct()
    {
        /** @var SwooleTable $table */
        $table = app('swoole.table');
        var_dump($table->get('params')->incr('room_id', 'counter'));
        var_dump($table->get('params')->get('room_id', 'counter'));
    }

    /**
     * Do some init stuffs before workers started.
     *
     * @return RoomContract
     */
    public function prepare(): RoomContract
    {
        $this->initRoomsTable();
        $this->initFdsTable();

        return $this;
    }

    /**
     * Add a socket fd to multiple rooms.
     *
     * @param  int fd
     * @param  array|string rooms
     */
    public function subscribe(int $fd, $room)
    {
        $rooms = $this->getRooms($fd);
        $roomNames = is_array($roomNames) ? $roomNames : [$roomNames];

        foreach ($roomNames as $room) {
            $fds = $this->getClients($room);

            if (in_array($fd, $fds)) {
                continue;
            }

            $fds[] = $fd;
            $rooms[] = $room;

            $this->setClients($room, $fds);
        }

        $this->setRooms($fd, $rooms);
    }

    /**
     * Delete a socket fd from multiple rooms.
     *
     * @param  int fd
     * @param  array|string rooms
     */
    public function unsubscribe(int $fd, $roomNames = [])
    {
        $allRooms = $this->getRooms($fd);
        $roomNames = is_array($roomNames) ? $roomNames : [$roomNames];
        $rooms = count($roomNames) ? $roomNames : $allRooms;

        $removeRooms = [];
        foreach ($rooms as $room) {
            $fds = $this->getClients($room);

            if (!in_array($fd, $fds)) {
                continue;
            }

            $this->setClients($room, array_values(array_diff($fds, [$fd])));
            $removeRooms[] = $room;
        }

        $this->setRooms($fd, array_values(array_diff($allRooms, $removeRooms)));
    }

    /**
     * Get all sockets by a room key.
     *
     * @param  string room
     *
     * @return array
     */
    public function getClients(string $room)
    {
        return $this->getValue($room, RoomContract::ROOMS_KEY) ?? [];
    }

    /**
     * Get all rooms by a fd.
     *
     * @param  int fd
     *
     * @return array
     */
    public function getRooms(int $fd)
    {
        return $this->getValue($fd, RoomContract::DESCRIPTORS_KEY) ?? [];
    }

    /**
     * @param  string  $room
     * @param  array  $fds
     *
     * @return TableRoom
     */
    protected function setClients(string $room, array $fds): TableRoom
    {
        return $this->setValue($room, $fds, RoomContract::ROOMS_KEY);
    }

    /**
     * @param  int  $fd
     * @param  array  $rooms
     *
     * @return TableRoom
     */
    protected function setRooms(int $fd, array $rooms): TableRoom
    {
        return $this->setValue($fd, $rooms, RoomContract::DESCRIPTORS_KEY);
    }

    /**
     * Init rooms table
     */
    protected function initRoomsTable(): void
    {
        $this->rooms = new Table($this->config['room_rows']);
        $this->rooms->column('value', Table::TYPE_STRING, $this->config['room_size']);
        $this->rooms->create();
    }

    /**
     * Init descriptors table
     */
    protected function initFdsTable()
    {
        $this->fds = new Table($this->config['client_rows']);
        $this->fds->column('value', Table::TYPE_STRING, $this->config['client_size']);
        $this->fds->create();
    }

    /**
     * Set value to table
     *
     * @param $key
     * @param  array  $value
     * @param  string  $table
     *
     * @return $this
     */
    public function setValue($key, array $value, string $table)
    {
        $this->checkTable($table);

        $this->$table->set($key, ['value' => json_encode($value)]);

        return $this;
    }

    /**
     * Get value from table
     *
     * @param  string  $key
     * @param  string  $table
     *
     * @return array|mixed
     */
    public function getValue(string $key, string $table)
    {
        $this->checkTable($table);

        $value = $this->$table->get($key);

        return $value ? json_decode($value['value'], true) : [];
    }

    /**
     * Check table for exists
     *
     * @param  string  $table
     */
    protected function checkTable(string $table)
    {
        if (!property_exists($this, $table) || !$this->$table instanceof Table) {
            throw new InvalidArgumentException("Invalid table name: `{$table}`.");
        }
    }
}
