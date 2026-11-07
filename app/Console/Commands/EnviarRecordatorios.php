<?php

namespace App\Console\Commands;

use App\Services\RecordatorioService;
use Illuminate\Console\Command;

class EnviarRecordatorios extends Command
{
    protected $signature = 'citas:recordatorios';

    protected $description = 'Envía a los pacientes el recordatorio de sus citas de mañana (CU43)';

    public function handle(RecordatorioService $recordatorios): int
    {
        $r = $recordatorios->enviarPendientes();
        $this->info("Recordatorios enviados: {$r['enviados']}; sin medio de contacto: {$r['sin_medio']}; ya enviados antes: {$r['ya_enviados']}.");

        return self::SUCCESS;
    }
}
