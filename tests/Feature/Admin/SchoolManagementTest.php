<?php

test('admin schools index route requires authentication', function () {
    $response = $this->get('http://admin.lms.local/schools');

    $response->assertRedirect('http://admin.lms.local/login');
});
