<?php

use App\Models\User;

test('change password page contains loading state elements', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/change-password');

    $html = $response->getContent();

    // Verify the submit button wires up a loading target
    expect($html)->toContain('id="update-password-btn"');
    expect($html)->toContain('wire:target="updatePassword"');
    expect($html)->toContain('wire:loading');

    // Verify loading spinner animation is defined
    expect($html)->toContain('animate-spin');
    expect($html)->toContain('@keyframes spin');

    // Verify disabled state CSS is present
    expect($html)->toContain('#update-password-btn:disabled');
    expect($html)->toContain('cursor: not-allowed');
});
