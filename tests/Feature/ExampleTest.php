<?php

test('la raíz lleva al inicio y, sin sesión, al login', function () {
    $this->get('/')->assertRedirect('/dashboard');

    $this->followingRedirects()->get('/')->assertOk()->assertSee('Iniciar sesión');
});
