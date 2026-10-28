<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\User;
use App\Services\CitaService;
use App\Support\Menu;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pantalla de inicio: resumen, agenda del día, calendario y avisos según el rol del usuario.
 * Todo sale de la base de datos; cada rol ve solo lo que le corresponde (RN12).
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $usuario = $request->user();
        $servicio = app(CitaService::class);
        $ahora = $servicio->ahora();
        $hoy = $ahora->toDateString();

        $verTodo = $usuario->tieneRol('administrador', 'recepcionista');
        $esMedico = $usuario->tieneRol('medico');
        $esPaciente = $usuario->tieneRol('paciente');
        $puedeVerAgenda = $verTodo || $esMedico;

        // Citas visibles para este usuario.
        $citas = fn () => Cita::query()
            ->when($esMedico, fn ($q) => $q->where('medico_id', $usuario->medico?->id ?? 0))
            ->when($esPaciente, fn ($q) => $q->where('paciente_id', $usuario->paciente?->id ?? 0));

        // ---- Tarjetas de resumen: [etiqueta, valor, ícono] ----
        $resumen = [];
        if ($verTodo || $esMedico) {
            $resumen[] = ['Citas de hoy', $citas()->ocupan()->where('fecha', $hoy)->count(), 'calendar-check'];
        }
        if ($verTodo) {
            $resumen[] = ['Pacientes activos', Paciente::activos()->count(), 'users'];
            $resumen[] = ['Médicos activos', Medico::activos()->count(), 'stethoscope'];
        }
        if ($usuario->tieneRol('administrador')) {
            $resumen[] = ['Usuarios activos', User::where('activo', true)->count(), 'lock'];
        }
        if ($esMedico) {
            $resumen[] = ['Mis horarios activos', $usuario->medico ? $usuario->medico->horarios()->activos()->count() : 0, 'clock'];
        }
        if ($verTodo || $esMedico) {
            $resumen[] = ['Citas por confirmar', $citas()->where('estado', Cita::PROGRAMADA)->where('fecha', '>=', $hoy)->count(), 'clock'];
        }
        if ($esPaciente) {
            $resumen[] = ['Mis próximas citas', $citas()->whereIn('estado', [Cita::PROGRAMADA, Cita::CONFIRMADA])->where('fecha', '>=', $hoy)->count(), 'calendar-check'];
        }
        $resumen = array_slice($resumen, 0, 4);

        // ---- Agenda de hoy por médico (línea de tiempo) ----
        $lineaTiempo = null;
        if ($puedeVerAgenda) {
            $citasHoy = $citas()->with(['paciente', 'especialidad'])->where('fecha', $hoy)->orderBy('hora_inicio')->get();
            $desde = 8;
            $hasta = 18;
            foreach ($citasHoy as $c) {
                $desde = min($desde, (int) substr($c->inicio, 0, 2));
                $hasta = max($hasta, (int) ceil(((int) substr($c->fin, 0, 2)) + ((int) substr($c->fin, 3, 2)) / 60));
            }
            $medicos = Medico::activos()
                ->when($esMedico, fn ($q) => $q->where('id', $usuario->medico?->id ?? 0))
                ->orderBy('apellidos')->orderBy('nombres')->limit(8)->get();
            $minutosAhora = ((int) $ahora->format('H')) * 60 + (int) $ahora->format('i');
            $lineaTiempo = [
                'desde' => $desde,
                'hasta' => $hasta,
                'ahora' => ($minutosAhora >= $desde * 60 && $minutosAhora <= $hasta * 60)
                    ? round(($minutosAhora - $desde * 60) / (($hasta - $desde) * 60) * 100, 2) : null,
                'horaAhora' => $ahora->format('H:i'),
                'filas' => $medicos->map(fn (Medico $m) => ['medico' => $m, 'citas' => $citasHoy->where('medico_id', $m->id)->values()]),
            ];
        }

        // ---- Próximas citas (Día / Semana / Mes) ----
        $rango = in_array($request->query('rango'), ['dia', 'semana', 'mes'], true) ? $request->query('rango') : 'semana';
        $limite = match ($rango) {
            'dia' => $hoy,
            'semana' => $ahora->addDays(6)->toDateString(),
            default => $ahora->addDays(30)->toDateString(),
        };
        $proximas = $citas()->with(['paciente', 'medico', 'especialidad'])
            ->where('estado', '!=', Cita::CANCELADA)
            ->where('fecha', '>=', $hoy)->where('fecha', '<=', $limite)
            ->orderBy('fecha')->orderBy('hora_inicio')->limit(8)->get();

        // ---- Calendario del mes con los días que tienen citas ----
        $mesParam = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $request->query('mes')) ? $request->query('mes') : $ahora->format('Y-m');
        $mes = CarbonImmutable::createFromFormat('!Y-m', $mesParam);
        $diasConCitas = $citas()->ocupan()
            ->where('fecha', '>=', $mes->startOfMonth()->toDateString())
            ->where('fecha', '<=', $mes->endOfMonth()->toDateString())
            ->get(['fecha'])->map(fn ($c) => $c->fecha->format('Y-m-d'))->unique()->flip();
        $calendario = [
            'titulo' => ucfirst($mes->locale('es')->translatedFormat('F Y')),
            'anterior' => $mes->subMonth()->format('Y-m'),
            'siguiente' => $mes->addMonth()->format('Y-m'),
            'vacios' => $mes->startOfMonth()->dayOfWeekIso - 1,
            'dias' => $mes->daysInMonth,
            'mes' => $mes,
            'conCitas' => $diasConCitas,
        ];

        // ---- Avisos recientes (solo administración y recepción) ----
        $avisos = [];
        if ($verTodo) {
            $porConfirmar = Cita::with('paciente')->where('estado', Cita::PROGRAMADA)->where('fecha', '>=', $hoy)->orderBy('fecha')->orderBy('hora_inicio')->get();
            if ($porConfirmar->isNotEmpty()) {
                $nombres = $porConfirmar->take(2)->map(fn ($c) => $c->paciente?->nombre_completo)->filter()->implode(' y ');
                $avisos[] = ['tipo' => 'alerta', 'titulo' => $porConfirmar->count() . ($porConfirmar->count() === 1 ? ' cita por confirmar' : ' citas por confirmar'), 'detalle' => $nombres . ($porConfirmar->count() > 2 ? ' y otras' : ''), 'cuando' => null];
            }
            $cancelada = Cita::with(['paciente', 'especialidad'])->where('estado', Cita::CANCELADA)->latest('updated_at')->first();
            if ($cancelada) {
                $avisos[] = ['tipo' => 'cancelada', 'titulo' => 'Última cita cancelada', 'detalle' => ($cancelada->paciente?->nombre_completo ?? 'Paciente') . ' — ' . ($cancelada->especialidad?->nombre ?? 'Cita') . ', ' . $cancelada->fecha->format('d/m') . ' ' . $cancelada->inicio, 'cuando' => $cancelada->updated_at?->locale('es')->diffForHumans()];
            }
            $nuevo = Paciente::latest()->first();
            if ($nuevo) {
                $avisos[] = ['tipo' => 'paciente', 'titulo' => 'Último paciente registrado', 'detalle' => $nuevo->nombre_completo . ' · ' . $nuevo->codigo, 'cuando' => $nuevo->created_at?->locale('es')->diffForHumans()];
            }
        }
        $resumenHoy = null;
        if ($verTodo || $esMedico) {
            $resumenHoy = ['atendidas' => $citas()->where('fecha', $hoy)->where('estado', Cita::ATENDIDA)->count(), 'total' => $citas()->ocupan()->where('fecha', $hoy)->count()];
        }

        // Saludo según la hora de La Paz.
        $hora = (int) $ahora->format('H');
        $saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');

        // Accesos a módulos: solo para médico y paciente (el resto ya los tiene en el menú lateral).
        $accesos = array_values(array_filter(Menu::para($usuario), fn (array $item) => $item['ruta'] !== 'dashboard'));

        return view('dashboard', compact(
            'usuario', 'resumen', 'accesos', 'lineaTiempo', 'proximas', 'rango', 'calendario', 'avisos', 'resumenHoy', 'saludo', 'puedeVerAgenda', 'hoy', 'verTodo'
        ));
    }
}
