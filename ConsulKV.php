<?php


use DCarbone\PHPConsulAPI\Config;
use DCarbone\PHPConsulAPI\Consul;

class ConsulKV
{
    private $kv;

    public function __construct(string $address = "127.0.0.1:8500")
    {
        $config = Config::newDefaultConfig();
        $config->Address = $address;

        $consul = new Consul($config);
        $this->kv = $consul->KV;
    }


    public function get(string $key): ?string
    {
        $resp = $this->kv->get($key);
        if ($resp->Err !== null || $resp->getValue() === null) {
            return null;
        }
        return $resp->getValue()->getValue();
    }
}

