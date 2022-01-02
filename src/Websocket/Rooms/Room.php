<?php

namespace SwooleTW\Http\Websocket\Rooms;

use ArrayObject;
use Illuminate\Contracts\Support\Arrayable;
use Sparrow\Setting\Models\Setting;
use Swoole\Table;
use SwooleTW\Http\Controllers\RoomController;
use SwooleTW\Http\Websocket\Facades\Websocket;

class Room implements Arrayable, \JsonSerializable
{

    protected ArrayObject $params;

    protected array $subscribers = [];

    protected array $users = [];

    public function __construct(public array $props = [])
    {
        var_dump('---------IN Room--------');
        var_dump($props);
        var_dump(app('swoole.server')->getWorkerId());
        var_dump('---------IN Room--------');
    }

    public static function create(array $options = []): RoomConnection
    {
        $id = Setting::incr('sparrow.room_id');
        $name = static::class;
        $connection = new RoomConnection($id);
        app('swoole.server')->task(['method' => RoomController::class.'@createRoom', 'data' => ['id'=>$id, 'options'=>$options, 'room'=>$name]], $connection->getWorker());
        return $connection;
    }

    public function get($filter): array
    {
        $return = [];
        if (!is_array($filter)) {
            $filter = [$filter];
        }
        foreach ($filter as $key) {
            $return = $filter[$key];
        }
        return $return;
    }

    public function set(array $options, bool $notify = false): void
    {
        $updated = [];
        foreach ($options as $key => $value) {
            $keys = explode('.', $key);
            $firstKey = $keys[0];
            $val = &$this[$firstKey];
            $updatedVal = &$updated[$firstKey];
            unset($keys[0]);
            foreach ($keys as $key) {
                $val = &$val[$key];
                $updatedVal = &$updatedVal[$key];
            }
            $val = $value;
            $updatedVal = $value;
        }
        if ($notify && count($updated)) {
            $this->broadcast('update', $updated);
        }
    }

    public function join(int $userId): bool
    {
        $user = app('sparrow-user')->find($userId);
        $joinPermission = is_callable([$this, 'onJoin']) && $this->onJoin($userId);
        if (!isset($this->users[$userId]) && $joinPermission) {
            $this->set(["users.$userId" => $user]);
            return true;
        }
        return false;
    }

    public function subscribe(int $user, int $fd): bool
    {
        if (is_callable([$this, 'onSubscribe'])) $this->onSubscribe($user);
        if (!isset($this->subscribers[$fd])) {
            $this->subscribers[$fd] = $user;
        }
        return true;
    }

    public function send(int $user, $event, $data = []): void
    {
        if (isset($this->users[$user])) {
            app('swoole.websocket')->to($this->users[$user])->emit($event, $data);
        }
    }

    public function broadcast(string $event, $data = []): void
    {
        app('swoole.websocket')->to(array_keys($this->subscribers))->emit($event, $data);
    }

    public function toArray()
    {
    }

    public function jsonSerialize()
    {
    }
}