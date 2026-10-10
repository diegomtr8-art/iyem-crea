<?php

test('las respuestas incluyen las cabeceras de seguridad', function () {
    $response = $this->get('/');

    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

test('HSTS no se envia por HTTP', function () {
    $response = $this->get('http://localhost/');

    $response->assertHeaderMissing('Strict-Transport-Security');
});

test('HSTS se envia cuando la peticion llega por HTTPS', function () {
    $response = $this->get('https://localhost/');

    $response->assertHeader('Strict-Transport-Security', 'max-age=31536000');
});
