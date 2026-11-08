<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Constancia de correos enviados · Clínica Santa Aurora</title>
    <style>
        *{box-sizing:border-box}
        body{margin:0;font-family:Arial,Helvetica,sans-serif;color:#1f2937;background:#f3f4f6;font-size:14px;line-height:1.45}
        .hoja{max-width:1000px;margin:24px auto;background:#fff;padding:28px 32px;border:1px solid #d1d5db;border-radius:8px}
        h1{margin:0 0 4px;font-size:22px}
        .sub{color:#6b7280;margin:0 0 16px}
        .modo{padding:10px 14px;border-radius:6px;margin:0 0 16px;font-weight:bold}
        .modo.sim{background:#fef3c7;color:#92400e;border:1px solid #fcd34d}
        .modo.real{background:#dcfce7;color:#166534;border:1px solid #86efac}
        form{display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;margin:0 0 18px}
        label{display:flex;flex-direction:column;font-size:12px;color:#4b5563;gap:3px}
        input,select{padding:6px 8px;border:1px solid #9ca3af;border-radius:4px;font-size:14px}
        button,.btn{padding:7px 14px;border:1px solid #374151;background:#374151;color:#fff;border-radius:4px;cursor:pointer;font-size:14px;text-decoration:none}
        .btn.sec{background:#fff;color:#374151}
        .correo{border:1px solid #d1d5db;border-radius:6px;margin:0 0 14px;page-break-inside:avoid}
        .cab{display:flex;flex-wrap:wrap;justify-content:space-between;gap:8px;background:#f9fafb;padding:10px 14px;border-bottom:1px solid #e5e7eb}
        .cab b{display:block}
        .estado{padding:2px 10px;border-radius:999px;font-weight:bold;font-size:12px;align-self:flex-start}
        .e-enviado{background:#dcfce7;color:#166534}
        .e-simulado{background:#fef3c7;color:#92400e}
        .e-fallido{background:#fee2e2;color:#991b1b}
        .cuerpo{padding:12px 14px;white-space:pre-wrap;word-break:break-word}
        .err{padding:8px 14px;background:#fef2f2;color:#991b1b;border-top:1px solid #fecaca}
        .vacio{padding:24px;text-align:center;color:#6b7280;border:1px dashed #9ca3af;border-radius:6px}
        .pie{color:#6b7280;font-size:12px;margin-top:18px}
        @media print{body{background:#fff}.hoja{border:0;margin:0;padding:0;max-width:none}form,.noprint{display:none}}
    </style>
</head>
<body>
<div class="hoja">
    <h1>Constancia de correos enviados</h1>
    <p class="sub">Clínica Santa Aurora · Registro de los correos que genera el sistema (p. ej. recordatorios de citas).</p>

    <div class="modo {{ $simulado ? 'sim' : 'real' }}">
        Modo de correo actual: {{ $modo }}
        @if ($simulado)
            — los correos se registran aquí pero no salen a una bandeja real.
        @else
            — los correos salen por el servidor SMTP configurado.
        @endif
    </div>

    <form method="GET" action="{{ route('constancia.correos') }}" class="noprint">
        <label>Desde <input type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}"></label>
        <label>Hasta <input type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}"></label>
        <label>Estado
            <select name="estado">
                <option value="">Todos</option>
                @foreach (['enviado' => 'Enviado', 'simulado' => 'Simulado', 'fallido' => 'Fallido'] as $valor => $texto)
                    <option value="{{ $valor }}" @selected(($filtros['estado'] ?? '') === $valor)>{{ $texto }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit">Filtrar</button>
        <a class="btn sec" href="{{ route('constancia.correos') }}">Limpiar</a>
        <button type="button" class="sec" onclick="window.print()">Imprimir</button>
    </form>

    <p><b>{{ $correos->count() }}</b> correo(s) · Horas en zona {{ $zona }}</p>

    @forelse ($correos as $correo)
        <article class="correo">
            <div class="cab">
                <div>
                    <b>{{ $correo->asunto }}</b>
                    Para: {{ $correo->destinatario }}
                </div>
                <div style="text-align:right">
                    <span class="estado e-{{ $correo->estado }}">{{ ucfirst($correo->estado) }}</span><br>
                    {{ $correo->created_at->copy()->setTimezone($zona)->format('d/m/Y H:i:s') }}
                </div>
            </div>
            <div class="cuerpo">{{ $correo->contenido }}</div>
            @if ($correo->estado === 'fallido')
                <div class="err"><b>Motivo del fallo:</b> {{ $correo->error }}</div>
            @endif
        </article>
    @empty
        <div class="vacio">No hay correos registrados con esos criterios.</div>
    @endforelse

    <p class="pie">Generado el {{ $generado->format('d/m/Y H:i') }}. Estados: <b>Enviado</b> = salió por SMTP real; <b>Simulado</b> = generado por el sistema y guardado sin entrega externa; <b>Fallido</b> = el envío produjo un error.</p>
</div>
</body>
</html>
