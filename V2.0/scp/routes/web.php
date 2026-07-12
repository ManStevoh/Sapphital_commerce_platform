<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/docs');

Route::get('/docs', function () {
    return response(
        'SAPPHITAL SCP API — specification and implementation guides live in V2.0/docs/.',
        200,
        ['Content-Type' => 'text/plain; charset=UTF-8']
    );
});
