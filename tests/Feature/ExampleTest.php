<?php

use Inertia\Testing\AssertableInertia as Assert;

test('home displays the public landing page', function () {
    $response = $this->get(route('home'));

    $response
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('welcome')
        );
});
