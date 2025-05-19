<?php

namespace App\Helper;

class JsonHelper
{
    private array $data = [];

    public function __construct($json)
    {
        if($json) {
            $this->data = json_decode($json, true);
        }
    }

    public function has(string $key) : bool
    {
        return array_key_exists($key, $this->data);
    }

    public function get(string $key, $defaultValue = null)
    {
        return $this->has($key) ? $this->data[$key] : $defaultValue;
    }
}