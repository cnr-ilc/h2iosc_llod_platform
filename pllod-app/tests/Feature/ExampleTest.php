<?php

use Filament\Facades\Filament;

// RedirectToLoginIfNotAuthenticated: chi non è autenticato finisce sempre sul login del pannello "auth"
it('redirects guests to the login page', function () {
    $this->get('/')
        ->assertRedirect(Filament::getPanel('auth')->getLoginUrl());
});

it('shows the login page to guests', function () {
    $this->get(Filament::getPanel('auth')->getLoginUrl())
        ->assertOk();
});
