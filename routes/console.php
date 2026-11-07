<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// CU43: cada día a las 18:00 (hora de Bolivia) se avisa a los pacientes de sus citas de mañana.
\Illuminate\Support\Facades\Schedule::command('citas:recordatorios')->dailyAt('18:00')->timezone('America/La_Paz');
