(function () {
    'use strict';

    var ongs = Object.values(JSON.parse(document.getElementById('dados-ongs').textContent || '{}'));

    function acharOng(id) {
        return ongs.find(function (o) { return o.id === Number(id); });
    }

    function esc(v) {
        var d = document.createElement('div');
        d.textContent = v == null ? '' : String(v);
        return d.innerHTML.replace(/"/g, '&quot;');
    }

    function norm(t) {
        return String(t).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    var inputBusca = document.getElementById('busca-ong');
    var selEstado = document.getElementById('filtro-estado');
    var selEspecie = document.getElementById('filtro-especie');
    var cards = Array.prototype.slice.call(document.querySelectorAll('.ong-card'));
    var vazio = document.getElementById('ongs-vazio');
    var contagem = document.getElementById('ongs-contagem');

    function filtrarOngs() {
        var termo = norm(inputBusca.value.trim());
        var uf = selEstado.value;
        var esp = selEspecie.value;
        var visiveis = 0;

        cards.forEach(function (c) {
            var okBusca = !termo || norm(c.dataset.busca).indexOf(termo) !== -1;
            var okUf = !uf || c.dataset.uf === uf;
            var okEsp = !esp || c.dataset.especies.split('|').indexOf(esp) !== -1;
            var mostrar = okBusca && okUf && okEsp;
            c.hidden = !mostrar;
            if (mostrar) visiveis++;
        });

        if (cards.length) {
            vazio.hidden = visiveis > 0;
            vazio.textContent = 'Nenhuma ONG encontrada com esses filtros.';
            contagem.textContent = visiveis + (visiveis === 1 ? ' ONG encontrada' : ' ONGs encontradas');
        }
    }

    inputBusca.addEventListener('input', filtrarOngs);
    selEstado.addEventListener('change', filtrarOngs);
    selEspecie.addEventListener('change', filtrarOngs);
    filtrarOngs();

    var overlay = document.getElementById('modal');
    var corpo = document.getElementById('modal-corpo');
    var btnFechar = overlay.querySelector('.modal-close');
    var ultimoFoco = null;
    var ongAtual = null;
    var filtroEsp = '';
    var filtroSexo = '';

    function avatar(o) {
        return '<span class="ong-avatar" data-ini="' + esc(o.ini) + '">' +
            (o.img ? '<img src="' + esc(o.img) + '" alt="" data-fallback>' : '') +
            '</span>';
    }

    function cardAnimal(p) {
        var sexo = p.sexo === 'F'
            ? '<span class="balao"><i class="fa-solid fa-venus"></i> Fêmea</span>'
            : p.sexo === 'M'
                ? '<span class="balao"><i class="fa-solid fa-mars"></i> Macho</span>'
                : '';

        return '<article class="animal-card">' +
            '<div class="animal-foto">' +
            '<img src="' + esc(p.img) + '" alt="Foto do ' + esc(p.nome) + '" loading="lazy">' +
            '<h4>' + esc(p.nome) + '</h4>' +
            '</div>' +
            '<div class="animal-info">' + sexo +
            '<span class="balao"><i class="fa-solid fa-hourglass-half"></i> ' + esc(p.idade) + '</span>' +
            '</div>' +
            '<p class="animal-raca">' + esc(p.especie) + (p.raca ? ' · ' + esc(p.raca) : '') + '</p>' +
            '<a class="btn-adotar" href="formulario_adocao.php?cd_pet=' + p.id + '">' +
            '<i class="fa-solid fa-heart"></i> Quero adotar!</a>' +
            '</article>';
    }

    function chip(rotulo, valor, grupo, ativo) {
        return '<button type="button" class="chip" data-grupo="' + grupo + '" data-valor="' +
            esc(valor) + '" aria-pressed="' + (ativo ? 'true' : 'false') + '">' + esc(rotulo) + '</button>';
    }

    function montarModal(o) {
        var especies = o.especies || [];
        var contatos = '';

        if (o.endereco) contatos += '<span class="contato"><i class="fa-solid fa-map-pin"></i>' + esc(o.endereco) + '</span>';
        if (o.telefone) contatos += '<a class="contato" href="tel:' + esc(o.telefone.replace(/[^\d+]/g, '')) + '"><i class="fa-solid fa-phone"></i>' + esc(o.telefone) + '</a>';
        if (o.email) contatos += '<a class="contato" href="mailto:' + esc(o.email) + '"><i class="fa-solid fa-envelope"></i>' + esc(o.email) + '</a>';

        var local = [o.cidade, o.uf].filter(Boolean).join(' - ');
        var sub = [];
        if (local) sub.push(esc(local));
        if (o.desde) sub.push('Parceira desde ' + esc(o.desde));

        var filtros = '';
        if (o.pets.length) {
            filtros = '<div class="chips" id="chips">' +
                chip('Todos', '', 'especie', true) +
                especies.map(function (e) { return chip(e, e, 'especie', false); }).join('') +
                '<span class="chips-sep"></span>' +
                chip('Machos', 'M', 'sexo', false) +
                chip('Fêmeas', 'F', 'sexo', false) +
                '</div>';
        }

        corpo.innerHTML =
            '<div class="modal-topo">' + avatar(o) +
            '<div><h2 id="modal-titulo">' + esc(o.nome) + '</h2>' +
            (sub.length ? '<p>' + sub.join(' · ') + '</p>' : '') + '</div></div>' +
            (contatos ? '<div class="modal-contatos">' + contatos + '</div>' : '') +
            '<div class="modal-animais">' +
            '<div class="modal-animais-topo"><h3><i class="fa-solid fa-paw"></i> Animais para adoção</h3>' +
            '<span class="balao" id="modal-qt"></span></div>' +
            filtros +
            '<div class="animais-grid" id="animais-grid"></div>' +
            '<p class="animais-vazio" id="animais-vazio" hidden></p>' +
            '</div>';

        corpo.querySelectorAll('img[data-fallback]').forEach(function (img) {
            img.addEventListener('error', function () { img.remove(); });
        });

        var chipsEl = document.getElementById('chips');
        if (chipsEl) {
            chipsEl.addEventListener('click', function (e) {
                var b = e.target.closest('.chip');
                if (!b) return;
                var grupo = b.dataset.grupo;
                var valor = b.dataset.valor;
                var jaAtivo = b.getAttribute('aria-pressed') === 'true';

                if (grupo === 'sexo') {
                    filtroSexo = jaAtivo ? '' : valor;
                } else {
                    filtroEsp = valor;
                }

                chipsEl.querySelectorAll('.chip').forEach(function (c) {
                    var v = c.dataset.valor;
                    c.setAttribute('aria-pressed',
                        c.dataset.grupo === 'sexo' ? String(v === filtroSexo && filtroSexo !== '')
                                                   : String(v === filtroEsp));
                });
                renderAnimais();
            });
        }

        renderAnimais();
    }

    function renderAnimais() {
        var grid = document.getElementById('animais-grid');
        var vazioEl = document.getElementById('animais-vazio');
        var qt = document.getElementById('modal-qt');

        var lista = ongAtual.pets.filter(function (p) {
            return (!filtroEsp || p.especie === filtroEsp) && (!filtroSexo || p.sexo === filtroSexo);
        });

        grid.innerHTML = lista.map(cardAnimal).join('');
        qt.textContent = lista.length + (lista.length === 1 ? ' animal' : ' animais');

        if (lista.length === 0) {
            vazioEl.hidden = false;
            vazioEl.textContent = ongAtual.pets.length === 0
                ? 'Esta ONG ainda não cadastrou animais para adoção.'
                : 'Nenhum animal encontrado com esses filtros.';
        } else {
            vazioEl.hidden = true;
        }
    }

    function abrir(id, atualizarUrl) {
        var o = acharOng(id);
        if (!o) return;

        ongAtual = o;
        filtroEsp = '';
        filtroSexo = '';
        ultimoFoco = document.activeElement;

        montarModal(o);
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        corpo.scrollTop = 0;
        btnFechar.focus();

        if (atualizarUrl !== false) {
            var url = new URL(window.location.href);
            url.searchParams.set('ong', o.id);
            history.replaceState(null, '', url);
        }
    }

    function fechar() {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
        corpo.innerHTML = '';
        ongAtual = null;

        var url = new URL(window.location.href);
        url.searchParams.delete('ong');
        history.replaceState(null, '', url);

        if (ultimoFoco && ultimoFoco.focus) ultimoFoco.focus();
    }

    document.getElementById('ongs-grid').addEventListener('click', function (e) {
        var b = e.target.closest('.btn-ver-mais');
        if (b) abrir(b.dataset.ong);
    });

    btnFechar.addEventListener('click', fechar);

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) fechar();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('active')) fechar();
    });

    /* Link direto: ongs.php?ong=3 abre o modal da ONG */
    var idUrl = new URLSearchParams(window.location.search).get('ong');
    if (idUrl) abrir(idUrl, false);
})();