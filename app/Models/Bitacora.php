<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Bitácora del sistema: quién hizo qué y cuándo. Solo se escribe y se consulta;
 * en PostgreSQL un disparador impide modificar o eliminar registros.
 * Nunca se guardan contraseñas ni valores de campos, solo los nombres de los campos cambiados.
 */
class Bitacora extends Model
{
    protected $table = 'bitacora';

    public $timestamps = false;

    protected $fillable = ['user_id', 'usuario', 'accion', 'modulo', 'registro_id', 'detalle', 'ip', 'fecha_hora'];

    public const ACCIONES = [
        'ingreso' => 'Ingreso',
        'salida' => 'Salida',
        'ingreso_fallido' => 'Ingreso fallido',
        'crear' => 'Registro',
        'modificar' => 'Modificación',
        'activar' => 'Activación',
        'desactivar' => 'Desactivación',
        'reprogramar' => 'Reprogramación',
        'cancelar' => 'Cancelación',
        'confirmar' => 'Confirmación',
        'asistencia' => 'Asistencia',
        'inasistencia' => 'Inasistencia',
        'atender' => 'Atención',
    ];

    public const MODULOS = [
        'sesion' => 'Sesión',
        'usuarios' => 'Usuarios',
        'pacientes' => 'Pacientes',
        'medicos' => 'Médicos',
        'especialidades' => 'Especialidades',
        'horarios' => 'Horarios',
        'citas' => 'Citas',
    ];

    protected function casts(): array
    {
        return ['fecha_hora' => 'datetime'];
    }

    public function usuarioAutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Anota un evento. Nunca interrumpe la operación del usuario si algo falla.
     * Las tareas de consola (migraciones, seeders) no se registran; las pruebas sí.
     */
    public static function registrar(
        string $accion,
        string $modulo,
        ?int $registroId = null,
        ?string $detalle = null,
        ?User $usuario = null,
        ?string $nombreIntentado = null,
    ): void {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        try {
            $usuario ??= auth()->user();

            static::create([
                'user_id' => $usuario?->id,
                'usuario' => Str::limit((string) ($usuario?->usuario ?: $usuario?->email ?: $nombreIntentado), 50, ''),
                'accion' => $accion,
                'modulo' => $modulo,
                'registro_id' => $registroId,
                'detalle' => $detalle !== null ? Str::limit($detalle, 500) : null,
                'ip' => request()->ip(),
                'fecha_hora' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Filtros de la pantalla de consulta. */
    public function scopeFiltrar(Builder $query, array $f): Builder
    {
        return $query
            ->when($f['q'] ?? null, function ($q, $texto) {
                $like = '%' . mb_strtolower($texto) . '%';
                $q->where(fn ($w) => $w->whereRaw('LOWER(usuario) LIKE ?', [$like])->orWhereRaw('LOWER(detalle) LIKE ?', [$like]));
            })
            ->when(array_key_exists($f['modulo'] ?? '', self::MODULOS), fn ($q) => $q->where('modulo', $f['modulo']))
            ->when(array_key_exists($f['accion'] ?? '', self::ACCIONES), fn ($q) => $q->where('accion', $f['accion']))
            ->when($f['desde'] ?? null, fn ($q, $d) => $q->where('fecha_hora', '>=', \Illuminate\Support\Carbon::parse($d, 'America/La_Paz')->startOfDay()->utc()))
            ->when($f['hasta'] ?? null, fn ($q, $d) => $q->where('fecha_hora', '<=', \Illuminate\Support\Carbon::parse($d, 'America/La_Paz')->endOfDay()->utc()));
    }
}
