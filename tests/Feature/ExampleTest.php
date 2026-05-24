<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('the application returns a successful response for authenticated users', function () {
    $user = User::factory()->create();

    $response = actingAs($user)->get('/');

    $response->assertStatus(200);
});

