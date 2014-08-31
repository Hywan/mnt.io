<?php

require_once '/usr/local/lib/Hoa/Core/Core.php';

$router = new Hoa\Router\Http();
$router
    ->get(
        'home',
        '/'
    )
    ->get(
        'post',
        '/P/(?<url>[\w\d\-_]+)'
    )

    ->_get(
        '_resource',
        '/(?<resource>)'
    );

return $router;
