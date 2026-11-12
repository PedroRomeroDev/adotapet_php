const campoTelefone = document.getElementById('telefone-cadastro');

    function mascaraTelefone(valor) {

        let numeros = valor.replace(/\D/g, '').slice(0, 11);

        if (numeros.length > 10) {
            // celular: (00) 00000-0000
            return numeros.replace(/^(\d{2})(\d{5})(\d{0,4}).*/, '($1) $2-$3');
        } else if (numeros.length > 6) {
            // fixo: (00) 0000-0000
            return numeros.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
        } else if (numeros.length > 2) {
            return numeros.replace(/^(\d{2})(\d{0,5}).*/, '($1) $2');
        } else if (numeros.length > 0) {
            return numeros.replace(/^(\d{0,2}).*/, '($1');
        }
        return '';
    }

    campoTelefone.addEventListener('input', function () {
        this.value = mascaraTelefone(this.value);
    });

    campoTelefone.value = mascaraTelefone(campoTelefone.value);