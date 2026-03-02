<?php

it('redirects guests from client list report', function () {
    $response = $this->get('/reports/clients-list');

    $response->assertRedirect();
});
