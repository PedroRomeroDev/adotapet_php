(() => {
    'use strict';

    const grid = document.getElementById('pets-grid');
    const input = document.getElementById('busca-animal');
    const vazio = document.getElementById('pets-vazio');
    const modal = document.getElementById('modal');
    const corpo = document.getElementById('modal-corpo');

    if (!grid || !modal || !corpo) return;

    const cards = Array.from(grid.querySelectorAll('.pet-card'));
    const reduzMovimento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const normalizar = (texto) =>
        (texto || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();

    const indice = cards.map((card) => {
        const nome = card.querySelector('.pet-name')?.textContent || '';
        const ong = card.querySelector('.pet-ong span:last-child')?.textContent || '';
        return normalizar(`${nome} ${ong}`);
    });

    function filtrar() {
        const termo = normalizar(input.value);
        let visiveis = 0;

        cards.forEach((card, i) => {
            const mostrar = termo === '' || indice[i].includes(termo);
            card.style.display = mostrar ? '' : 'none';
            if (mostrar) visiveis++;
        });

        if (vazio) {
            vazio.textContent = 'Nenhum animal encontrado para essa busca.';
            vazio.hidden = visiveis > 0;
        }
    }

    if (input) {
        input.addEventListener('input', filtrar);
        input.closest('form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            filtrar();
        });
    }

    // Banco sem nenhum pet cadastrado
    if (cards.length === 0 && vazio) {
        vazio.textContent = 'Nenhum animal disponível para adoção no momento.';
        vazio.hidden = false;
    }

    const caixa = modal.querySelector('.modal');
    const btnFechar = modal.querySelector('.modal-close');
    let ultimoGatilho = null;

    const modalAberto = () => modal.classList.contains('active');

    function abrirModal(card, gatilho) {
        const modelo = card.querySelector('template.pet-detalhes');
        if (!modelo) return;

        ultimoGatilho = gatilho;
        corpo.replaceChildren(modelo.content.cloneNode(true));
        corpo.scrollTop = 0;

        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        btnFechar.focus();
    }

    function fecharModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        corpo.replaceChildren();
        if (ultimoGatilho) ultimoGatilho.focus();
    }

    grid.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-ver-mais');
        if (!btn) return;
        const card = btn.closest('.pet-card');
        if (card) abrirModal(card, btn);
    });

    // Fechar: botão, clique fora, Esc
    btnFechar.addEventListener('click', fecharModal);

    modal.addEventListener('click', (e) => {
        if (e.target === modal) fecharModal();
    });

    document.addEventListener('keydown', (e) => {
        if (!modalAberto()) return;

        if (e.key === 'Escape') {
            fecharModal();
            return;
        }

        // Mantém o foco do teclado dentro do modal
        if (e.key === 'Tab') {
            const focaveis = caixa.querySelectorAll('a[href], button:not([disabled])');
            if (!focaveis.length) return;
            const primeiro = focaveis[0];
            const ultimo = focaveis[focaveis.length - 1];

            if (e.shiftKey && document.activeElement === primeiro) {
                e.preventDefault();
                ultimo.focus();
            } else if (!e.shiftKey && document.activeElement === ultimo) {
                e.preventDefault();
                primeiro.focus();
            }
        }
    });

    function animarContador(el, duracao = 1200) {
        const alvo = parseInt(el.textContent.replace(/\D/g, ''), 10);
        if (isNaN(alvo) || reduzMovimento) return;

        const inicio = performance.now();

        const passo = (agora) => {
            const progresso = Math.min((agora - inicio) / duracao, 1);
            const suave = 1 - Math.pow(1 - progresso, 3);
            el.textContent = Math.round(alvo * suave).toLocaleString('pt-BR');
            if (progresso < 1) requestAnimationFrame(passo);
        };

        requestAnimationFrame(passo);
    }

    ['today-counter', 'adoptions-counter'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) animarContador(el);
    });
})();