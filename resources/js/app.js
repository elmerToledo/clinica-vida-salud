import Alpine from 'alpinejs';

window.Alpine = Alpine;

/* Menú lateral: abierto en celular y colapsado en escritorio (se recuerda la elección). */
Alpine.data('panelLateral', () => ({
    abierto: false,
    colapsado: (() => { try { return localStorage.getItem('menu') === 'colapsado'; } catch (e) { return false; } })(),
    alternarColapso() {
        this.colapsado = !this.colapsado;
        try { localStorage.setItem('menu', this.colapsado ? 'colapsado' : 'abierto'); } catch (e) {}
    },
}));

/* Tema claro / oscuro (se recuerda la elección). */
Alpine.store('tema', {
    oscuro: document.documentElement.classList.contains('dark'),
    alternar() {
        this.oscuro = !this.oscuro;
        document.documentElement.classList.toggle('dark', this.oscuro);
        try { localStorage.setItem('tema', this.oscuro ? 'oscuro' : 'claro'); } catch (e) {}
    },
});

/* Color de acento: verde azulado (por defecto), azul, verde o vino. */
Alpine.store('acento', {
    actual: document.documentElement.getAttribute('data-acento') || 'verde-azulado',
    elegir(nombre) {
        this.actual = nombre;
        if (nombre === 'verde-azulado') document.documentElement.removeAttribute('data-acento');
        else document.documentElement.setAttribute('data-acento', nombre);
        try {
            if (nombre === 'verde-azulado') localStorage.removeItem('acento');
            else localStorage.setItem('acento', nombre);
        } catch (e) {}
    },
});

Alpine.start();
