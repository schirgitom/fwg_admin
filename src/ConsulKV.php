<?php
namespace App;

use DCarbone\PHPConsulAPI\Config;
use DCarbone\PHPConsulAPI\Consul;

class ConsulKV
{
    private $kv;

    public function __construct(array $consulConfig)
    {
        $cfg = Config::newDefaultConfig();

        // Pflicht: Adresse
        $cfg->Address = $consulConfig['address'] ?? '127.0.0.1:8500';

        // Optional: Schema / Token
        if (!empty($consulConfig['scheme'])) {
            $cfg->Scheme = $consulConfig['scheme'];
        }
        if (!empty($consulConfig['token'])) {
            $cfg->Token = $consulConfig['token'];
        }

        $consul = new Consul($cfg);
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

