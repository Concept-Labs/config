<?php

namespace Concept\Config\Cache;

use Psr\SimpleCache\CacheInterface;

class LRUCache implements CacheInterface
{
    private int $capacity;
    private array $cache = []; 
    private array $nodes = []; 
    private \SplDoublyLinkedList $order;

    public function __construct(int $capacity = 10000)
    {
        $this->capacity = $capacity;
        $this->order = new \SplDoublyLinkedList();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!isset($this->cache[$key])) {
            return $default;
        }

        return $this->cache[$key];
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        if (isset($this->cache[$key])) {
            
            $this->cache[$key] = $value;
        } else {
            
            if (count($this->cache) >= $this->capacity) {
                $oldestKey = $this->order->shift();
                unset($this->cache[$oldestKey], $this->nodes[$oldestKey]);
            }

            
            $this->cache[$key] = $value;
            $this->order->push($key);
            $this->nodes[$key] = $this->order->top();
        }

        return true;
    }

    public function delete(string $key): bool
    {
        if (isset($this->cache[$key])) {
            unset($this->cache[$key]);

            foreach ($this->order as $index => $listKey) {
                if ($listKey === $key) {
                    $this->order->offsetUnset($index);
                    break;
                }
            }

            unset($this->nodes[$key]);
        }

        return true;
    }

    public function clear(): bool
    {
        $this->cache = [];
        $this->order = new \SplDoublyLinkedList();
        $this->nodes = [];

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $default);
        }

        return $values;
        
    }

    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return isset($this->cache[$key]);
    }
}
