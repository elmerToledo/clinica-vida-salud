{{-- Número que sube desde 0 hasta el valor (sin movimiento si el usuario pidió menos animación). --}}
@props(['valor'])

<span x-data="{ n: 0, meta: {{ (int) $valor }}, init() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || this.meta === 0) { this.n = this.meta; return; }
        const ini = performance.now();
        const paso = (t) => { const p = Math.min(1, (t - ini) / 1200); this.n = Math.round(this.meta * (1 - Math.pow(1 - p, 3))); if (p < 1) requestAnimationFrame(paso); };
        requestAnimationFrame(paso);
    } }" x-text="n">{{ (int) $valor }}</span>
