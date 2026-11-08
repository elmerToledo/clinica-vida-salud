<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo') · Clínica Santa Aurora</title>
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#1f2937;padding:16px}
        .tarjeta{max-width:480px;width:100%;background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:36px 28px;text-align:center;box-shadow:0 4px 18px rgba(0,0,0,.06)}
        .codigo{font-size:56px;font-weight:bold;color:#0f766e;margin:0}
        h1{font-size:22px;margin:6px 0 10px}
        p{color:#4b5563;line-height:1.5;margin:0 0 22px}
        .acciones{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
        a,button{padding:10px 18px;border-radius:10px;font-size:15px;text-decoration:none;cursor:pointer;border:1px solid #0f766e}
        .principal{background:#0f766e;color:#fff}
        .secundario{background:#fff;color:#0f766e}
    </style>
</head>
<body>
<main class="tarjeta">
    <p class="codigo">@yield('codigo')</p>
    <h1>@yield('titulo')</h1>
    <p>@yield('mensaje')</p>
    <div class="acciones">
        <a class="principal" href="{{ url('/') }}">Ir al inicio</a>
        <button type="button" class="secundario" onclick="history.length > 1 ? history.back() : location.assign('{{ url('/') }}')">Volver atrás</button>
    </div>
</main>
</body>
</html>
