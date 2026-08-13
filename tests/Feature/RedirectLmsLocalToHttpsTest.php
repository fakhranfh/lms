<?php

beforeEach(function () {
    app()->instance('env', 'local');
});

test('redirects plain http requests on the app domain to https', function () {
    $response = $this->get('http://lms.local/');

    $response->assertRedirect('https://lms.local');
});

test('redirects plain http requests on app domain subdomains to https', function () {
    $response = $this->get('http://school.lms.local/');

    $response->assertRedirect('https://school.lms.local');
});

test('does not redirect requests already served over https', function () {
    $response = $this->get('https://lms.local/');

    expect($response->status())->not->toBe(302)->not->toBe(301);
});

test('does not redirect requests forwarded as https by the local tls proxy', function () {
    $response = $this->withHeader('X-Forwarded-Proto', 'https')
        ->get('http://lms.local/');

    expect($response->status())->not->toBe(302)->not->toBe(301);
});

test('does not redirect domains outside the app domain', function () {
    $response = $this->get('http://example.com/');

    expect($response->status())->not->toBe(302)->not->toBe(301);
});
