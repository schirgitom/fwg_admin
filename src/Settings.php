<?php
namespace App;

class Settings
{
    private static ?array $config = null;      // aus config/config.php
    private static ?array $cache = null;       // geparste Settings (Array)
    private static int $cacheLoadedAt = 0;     // Unix timestamp

    public static function init(array $appConfig): void
    {
        self::$config = $appConfig;
    }

    /**
     * Lädt (oder refresht) die Settings aus Consul und cached sie für cache_ttl Sekunden.
     */
    private static function ensureLoaded(): void
    {
        if (self::$config === null) {
            throw new \RuntimeException('Settings not initialized. Call Settings::init($config) first.');
        }

        $ttl = (int)(self::$config['cache_ttl'] ?? 60);
        $isFresh = (time() - self::$cacheLoadedAt) < $ttl;

        if ($isFresh && self::$cache !== null) {
            return;
        }

        $key = self::$config['settings_key'] ?? 'config/app';
        $kv  = new ConsulKV(self::$config['consul'] ?? []);

        $json = $kv->get($key);

        if ($json === null || $json === '') {
            // Kein JSON im KV: leeren Cache setzen, aber nicht crashen
            self::$cache = [];
            self::$cacheLoadedAt = time();
            return;
        }

        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Ungültiges JSON – wir werfen eine aussagekräftige Exception
            throw new \RuntimeException(
                'Invalid JSON from Consul KV at key "' . $key . '": ' . json_last_error_msg()
            );
        }

        self::$cache = $data;
        self::$cacheLoadedAt = time();
    }

    /**
     * Hole den kompletten Settings-Array (gecached).
     */
    public static function all(): array
    {
        self::ensureLoaded();
        return self::$cache ?? [];
    }

    /**
     * Hole einen Wert per "dot notation", z. B. get('db.host', 'localhost')
     */
    public static function get(string $path, $default = null)
    {
        self::ensureLoaded();

        $segments = explode('.', $path);
        $cursor = self::$cache ?? [];

        foreach ($segments as $seg) {
            if (is_array($cursor) && array_key_exists($seg, $cursor)) {
                $cursor = $cursor[$seg];
            } else {
                return $default;
            }
        }
        return $cursor;
    }

    /**
     * Manuelles Invalidieren des Caches (z. B. nach Admin-Änderungen).
     */
    public static function refresh(): void
    {
        self::$cacheLoadedAt = 0;
        self::$cache = null;
        self::ensureLoaded();
    }
}
