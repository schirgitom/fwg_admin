<?php
return [
    'consul' => [
        // Adresse deines Consul-Servers
        'address' => '10.150.20.103:8500',
        // Optional: 'https' wenn nötig
        'scheme'  => 'http',
        // Optional: ACL-Token, falls du eines brauchst, sonst null
        'token'   => null,
    ],

    // Der KV-Key, unter dem dein JSON liegt (z. B. {"db":{"host":"..."},"featureX":true})
    'settings_key' => 'Admin/Settings',

    // Cache-Zeit in Sekunden für die Settings
    'cache_ttl' => 60,
];