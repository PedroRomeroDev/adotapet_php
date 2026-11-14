(() => {
    let escuro = false;
    try { escuro = localStorage.getItem('modoEscuro') === 'true'; } catch (_) {}
    function aplicar(valor) {
        escuro = valor;
        document.documentElement.dataset.theme = valor ? 'dark' : 'light';
        document.body?.classList.toggle('dark', valor);
        document.querySelectorAll('[data-toggle-theme]').forEach(botao => {
            botao.setAttribute('aria-pressed', String(valor));
            botao.textContent = valor ? 'Modo claro' : 'Modo escuro';
        });
        const controle = document.getElementById('toggle-modoEscuro');
        if (controle) { controle.checked = valor; controle.setAttribute('aria-label', 'Modo escuro'); }
    }
    aplicar(escuro);
    document.addEventListener('DOMContentLoaded', () => {
        aplicar(escuro);
        function salvar(valor) {
            aplicar(valor);
            try { localStorage.setItem('modoEscuro', String(valor)); } catch (_) {}
        }
        document.querySelectorAll('[data-toggle-theme]').forEach(botao => botao.addEventListener('click', () => salvar(!escuro)));
        document.getElementById('toggle-modoEscuro')?.addEventListener('change', evento => salvar(evento.target.checked));
    });
    window.addEventListener('storage', evento => { if (evento.key === 'modoEscuro') aplicar(evento.newValue === 'true'); });
})();
