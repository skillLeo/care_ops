<?php

it('redirects guests from the home page', function () {
    $response = $this->get('/');

    $response->assertRedirect();
});
