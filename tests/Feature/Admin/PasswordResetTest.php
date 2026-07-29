<?php

use App\Models\User;
use Backpack\CRUD\app\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;

test('backpack password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('backpack.auth.password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
        $response = $this->post(route('backpack.auth.password.reset'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(backpack_url());

        return true;
    });
});
