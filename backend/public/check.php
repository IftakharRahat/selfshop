<?php

header('Content-Type: text/plain; charset=UTF-8');

echo 'SAPI: ' . php_sapi_name() . PHP_EOL;
echo 'Loaded INI: ' . (php_ini_loaded_file() ?: '(none)') . PHP_EOL;
echo 'OpenSSL: ' . (extension_loaded('openssl') ? 'enabled' : 'missing') . PHP_EOL;

