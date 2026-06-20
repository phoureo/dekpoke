<?php

$config = require __DIR__ . '/config.example.php';
$localConfigPath = __DIR__ . '/config.local.php';

if (is_file($localConfigPath)) {
    $config = array_replace_recursive($config, require $localConfigPath);
}

return $config;
