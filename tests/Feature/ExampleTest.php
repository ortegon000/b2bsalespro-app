<?php

test('home redirects to the login screen', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('login'));
});
