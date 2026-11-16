<?php

declare(strict_types=1);

return [
    // This API authenticates bearer tokens only, without SPA cookies or sessions.
    'routes' => false,
    'stateful' => [],
    'guard' => [],
];
