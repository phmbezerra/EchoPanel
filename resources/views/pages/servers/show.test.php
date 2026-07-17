<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('pages::servers.show')
        ->assertStatus(200);
});
