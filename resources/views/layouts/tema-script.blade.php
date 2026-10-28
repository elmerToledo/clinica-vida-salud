{{-- Aplica el tema y el color de acento guardados (claro y verde azulado por defecto) antes de pintar la página. --}}
<script>
    try {
        if (localStorage.getItem('tema') === 'oscuro') document.documentElement.classList.add('dark');
        var a = localStorage.getItem('acento');
        if (a) document.documentElement.setAttribute('data-acento', a);
    } catch (e) {}
</script>
