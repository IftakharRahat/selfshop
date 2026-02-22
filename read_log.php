<?php
$logPath = 'd:/selfshop/backend/storage/logs/laravel.log';
if (file_exists($logPath)) {
    $size = filesize($logPath);
    $readSize = min($size, 10240); // Read last 10KB
    $f = fopen($logPath, 'rb');
    fseek($f, -$readSize, SEEK_END);
    echo fread($f, $readSize);
    fclose($f);
} else {
    echo "Log file not found at " . $logPath;
}
