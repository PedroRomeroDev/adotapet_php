
(function () {
  'use strict';

  var digitos = function (v) { return (v || '').replace(/\D/g, ''); };

  var mascaras = {
    cpf: function (v) {
      v = digitos(v).slice(0, 11);
      return v
        .replace(/^(\d{3})(\d)/, '$1.$2')
        .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/\.(\d{3})(\d{1,2})$/, '.$1-$2');
    },
    telefone: function (v) {
      v = digitos(v).slice(0, 11);
      if (v.length === 0) return '';
      if (v.length <= 2) return '(' + v;
      if (v.length <= 6) return v.replace(/^(\d{2})(\d+)/, '($1) $2');
      if (v.length <= 10) return v.replace(/^(\d{2})(\d{4})(\d+)/, '($1) $2-$3');
      return v.replace(/^(\d{2})(\d{5})(\d+)/, '($1) $2-$3');
    },
    cep: function (v) {
      v = digitos(v).slice(0, 8);
      return v.replace(/^(\d{5})(\d)/, '$1-$2');
    },
    data: function (v) {
      v = digitos(v).slice(0, 8);
      return v
        .replace(/^(\d{2})(\d)/, '$1/$2')
        .replace(/^(\d{2})\/(\d{2})(\d)/, '$1/$2/$3');
    },
    rg: function (v) {
      return (v || '').toUpperCase().replace(/[^0-9X.\-]/g, '').slice(0, 14);
    }
  };

  function cpfValido(cpf) {
    cpf = digitos(cpf);
    if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
    for (var t = 9; t < 11; t++) {
      var soma = 0;
      for (var i = 0; i < t; i++) soma += parseInt(cpf[i], 10) * (t + 1 - i);
      var dv = ((soma * 10) % 11) % 10;
      if (dv !== parseInt(cpf[t], 10)) return false;
    }
    return true;
  }

  var validadores = {
    cpf: function (el) {
      return cpfValido(el.value) ? '' : 'Informe um CPF válido.';
    },
    telefone: function (el) {
      var d = digitos(el.value);
      if (d.length < 10 || d.length > 11) return 'Informe o telefone com DDD.';
      if (parseInt(d.slice(0, 2), 10) < 11) return 'DDD inválido.';
      if (d.length === 11 && d[2] !== '9') return 'Celular deve começar com 9.';
      return '';
    },
    cep: function (el) {
      return digitos(el.value).length === 8 ? '' : 'CEP deve ter 8 dígitos.';
    },
    data: function (el) {
      var m = el.value.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
      if (!m) return 'Use o formato dd/mm/aaaa.';
      var d = +m[1], mes = +m[2], a = +m[3];
      var dt = new Date(a, mes - 1, d);
      if (dt.getFullYear() !== a || dt.getMonth() !== mes - 1 || dt.getDate() !== d)
        return 'Data inexistente.';
      var hoje = new Date();
      if (dt > hoje) return 'A data não pode ser no futuro.';
      var min = parseInt(el.getAttribute('data-idade-min'), 10);
      if (min) {
        var idade = hoje.getFullYear() - a;
        if (hoje < new Date(hoje.getFullYear(), mes - 1, d)) idade--;
        if (idade < min) return 'É necessário ter pelo menos ' + min + ' anos.';
      }
      return '';
    },
    rg: function (el) {
      return digitos(el.value).length >= 5 ? '' : 'RG inválido.';
    }
  };

  function validar(el) {
    var tipo = el.getAttribute('data-mask');
    if (!tipo || !validadores[tipo]) return;
    // Campo opcional e vazio: sem erro
    if (!el.required && el.value.trim() === '') { el.setCustomValidity(''); return; }
    el.setCustomValidity(el.value.trim() === '' ? '' : validadores[tipo](el));
  }

  function buscarCep(el) {
    var alvo = el.getAttribute('data-preenche');
    var cep = digitos(el.value);
    if (!alvo || cep.length !== 8) return;

    fetch('https://viacep.com.br/ws/' + cep + '/json/')
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d.erro) { el.setCustomValidity('CEP não encontrado.'); return; }
        el.setCustomValidity('');
        var mapa = { rua: d.logradouro, bairro: d.bairro, cidade: d.localidade, uf: d.uf };
        alvo.split(',').forEach(function (par) {
          var p = par.split(':');
          var campo = document.querySelector(p[1]);
          if (campo && mapa[p[0]] !== undefined) campo.value = mapa[p[0]];
        });
      })
      .catch(function () { /* sem internet: usuário preenche manualmente */ });
  }

  function iniciar() {
    document.querySelectorAll('[data-mask]').forEach(function (el) {
      var tipo = el.getAttribute('data-mask');
      if (!mascaras[tipo]) return;

      // Atributos que melhoram o teclado no celular
      if (tipo === 'cpf' || tipo === 'cep' || tipo === 'data') el.setAttribute('inputmode', 'numeric');
      if (tipo === 'telefone') el.setAttribute('inputmode', 'tel');
      el.setAttribute('autocomplete', el.getAttribute('autocomplete') || (tipo === 'telefone' ? 'tel-national' : tipo === 'cep' ? 'postal-code' : 'off'));
      var tamanhos = { cpf: 14, telefone: 15, cep: 9, data: 10, rg: 14 };
      el.setAttribute('maxlength', tamanhos[tipo]);

      el.addEventListener('input', function () {
        el.value = mascaras[tipo](el.value);
        validar(el);
        if (tipo === 'cep') buscarCep(el);
      });
      el.addEventListener('blur', function () {
        validar(el);
        if (el.value && !el.checkValidity()) el.reportValidity();
      });

      if (el.value) { el.value = mascaras[tipo](el.value); validar(el); }
    });

    // Evita envio duplicado
    document.querySelectorAll('form').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        form.querySelectorAll('[data-mask]').forEach(validar);
        if (!form.checkValidity()) return; // navegador mostra o erro
        var btn = form.querySelector('[type="submit"]');
        if (btn) {
          if (btn.dataset.enviando) { e.preventDefault(); return; }
          btn.dataset.enviando = '1';
          setTimeout(function () { btn.disabled = true; }, 0);
        }
      });
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar);
  else iniciar();
})();