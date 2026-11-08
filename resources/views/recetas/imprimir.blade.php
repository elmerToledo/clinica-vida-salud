<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Receta médica {{ $receta->codigo }} · Clínica Santa Aurora</title>
    <style>
        *{box-sizing:border-box}
        body{margin:0;font-family:Arial,Helvetica,sans-serif;color:#1f2937;background:#f3f4f6;font-size:15px;line-height:1.5}
        .hoja{max-width:800px;margin:24px auto;background:#fff;padding:36px 40px;border:1px solid #d1d5db;border-radius:8px}
        .cab{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid #1f3864;padding-bottom:12px;margin-bottom:18px}
        h1{margin:0;font-size:22px;color:#1f3864}
        .clinica{color:#6b7280;font-size:13px}
        .cod{text-align:right;font-size:13px;color:#4b5563}
        .datos{display:grid;grid-template-columns:1fr 1fr;gap:8px 24px;margin-bottom:18px;font-size:14px}
        .datos b{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#6b7280}
        h2{font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:#1f3864;margin:18px 0 4px}
        .texto{white-space:pre-line;word-break:break-word;border:1px solid #e5e7eb;border-radius:6px;padding:10px 12px;min-height:40px}
        .firma{margin-top:56px;text-align:center;font-size:13px}
        .firma div{border-top:1px solid #374151;width:260px;margin:0 auto 4px}
        .pie{margin-top:24px;color:#6b7280;font-size:11px;text-align:center}
        .acciones{max-width:800px;margin:16px auto 0;display:flex;gap:10px}
        button,.btn{padding:8px 16px;border:1px solid #1f3864;background:#1f3864;color:#fff;border-radius:4px;cursor:pointer;font-size:14px;text-decoration:none}
        .btn.sec{background:#fff;color:#1f3864}
        @media print{body{background:#fff}.hoja{border:0;margin:0;padding:0;max-width:none}.acciones{display:none}}
    </style>
</head>
<body>
<div class="acciones">
    <button type="button" onclick="window.print()">Imprimir receta</button>
    <a class="btn sec" href="{{ route('citas.show', $cita) }}">Volver a la cita</a>
</div>
<div class="hoja">
    <div class="cab">
        <div>
            <h1>Receta médica</h1>
            <div class="clinica">Clínica Santa Aurora</div>
        </div>
        <div class="cod">
            <strong>{{ $receta->codigo }}</strong><br>
            Cita {{ $cita->codigo }}<br>
            {{ $cita->fecha->format('d/m/Y') }}
        </div>
    </div>

    <div class="datos">
        <div><b>Paciente</b>{{ $cita->paciente->apellidos }}, {{ $cita->paciente->nombres }}</div>
        <div><b>Médico</b>{{ $cita->medico->apellidos }}, {{ $cita->medico->nombres }}</div>
        <div><b>Especialidad</b>{{ $cita->especialidad->nombre }}</div>
        <div><b>Estado</b>{{ $receta->publicada ? 'Publicada ' . $receta->publicada_at->timezone('America/La_Paz')->format('d/m/Y H:i') : 'Borrador (aún no publicada)' }}</div>
    </div>

    <h2>Diagnóstico</h2>
    <div class="texto">{{ $receta->diagnostico }}</div>

    <h2>Medicamentos y dosis</h2>
    <div class="texto">{{ $receta->medicamentos }}</div>

    @if ($receta->indicaciones)
        <h2>Indicaciones</h2>
        <div class="texto">{{ $receta->indicaciones }}</div>
    @endif

    <div class="firma">
        <div></div>
        Dr(a). {{ $cita->medico->nombres }} {{ $cita->medico->apellidos }}
    </div>
    <p class="pie">Documento generado por el sistema de Clínica Santa Aurora.</p>
</div>
</body>
</html>
