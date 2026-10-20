const contCadastro = document.getElementById('cadastro')
const contLogin = document.getElementById('login')
const btMobileCadastrar = document.getElementById('btMobileCadastrar')
const btMobileEntrar = document.getElementById('btMobileEntrar')
const formCadastro = document.getElementById('form-cadastro');
const formLogin = document.getElementById('form-login');
const ehPaginaOng = document.getElementById('cnpj-ong') !== null;

btMobileEntrar.classList.add('hidden')
contCadastro.classList.add('hidden')

function esconderCadastro () {
    formCadastro.reset()
    contCadastro.classList.add('hidden')
    contLogin.classList.remove('hidden')
}

function esconderCadastroMobile () {
    formCadastro.reset()
    contCadastro.classList.add('hidden')
    contLogin.classList.remove('hidden')
    btMobileEntrar.classList.add('hidden')
    btMobileCadastrar.classList.remove('hidden')
}

function esconderLogin () {
    formLogin.reset()
    contLogin.classList.add('hidden')
    contCadastro.classList.remove('hidden')
}

function esconderLoginMobile () {
    formLogin.reset()
    contLogin.classList.add('hidden')
    contCadastro.classList.remove('hidden')
    
    btMobileCadastrar.classList.add('hidden')
    btMobileEntrar.classList.remove('hidden')
}

function validarEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

formCadastro.addEventListener('submit', function(event) {
    event.preventDefault(); 

    if (ehPaginaOng) {
        const nome = document.getElementById('nome-ong').value.trim();
        const cnpj = document.getElementById('cnpj-ong').value.trim();
        const responsavel = document.getElementById('resp-ong').value.trim();
        const telefone = document.getElementById('tel-ong').value.trim();
        const email = document.getElementById('email-cadastro').value.trim().toLowerCase();
        const senha = document.getElementById('senha-cadastro').value.trim();

        if (nome.length < 3) { alert("O nome da ONG deve ter pelo menos 3 caracteres."); return; }
        if (cnpj.length < 14) { alert("Por favor, insira um CNPJ válido."); return; }
        if (responsavel.length < 3) { alert("O nome do responsável deve ter pelo menos 3 caracteres."); return; }
        if (telefone.length < 8) { alert("Por favor, insira um telefone válido."); return; }
        if (!validarEmail(email)) { alert("Por favor, insira um e-mail válido."); return; }
        if (senha.length < 6) { alert("A senha deve ter pelo menos 6 caracteres."); return; }

        const ong = { nome: nome, cnpj: cnpj, responsavel: responsavel, telefone: telefone, email: email, senha: senha };
        localStorage.setItem('ongAdotaPet', JSON.stringify(ong));
        
        alert("Cadastro da ONG realizado com sucesso! Faça o login para acessar o painel.");

    } else {
        const nome = document.getElementById('nome-cadastro').value.trim();
        const email = document.getElementById('email-cadastro').value.trim().toLowerCase();
        const senha = document.getElementById('senha-cadastro').value.trim();

        if (nome.length < 3)
            { 
                alert("O nome deve ter pelo menos 3 caracteres.");
               return; 
            }
        if (!validarEmail(email))
            {
                alert("Por favor, insira um e-mail válido."); 
                return; 
            }
        if (senha.length < 6)
            {
                alert("A senha deve ter pelo menos 6 caracteres.");
                return;
            }

        const usuario = {
            nome: nome, 
            email: email,
            senha: senha };
        localStorage.setItem('usuarioAdotaPet', JSON.stringify(usuario));
        alert("Cadastro de Usuário realizado com sucesso! Faça o login para entrar.");
    }
    
    esconderCadastro();
});

formLogin.addEventListener('submit', function(event) {
    event.preventDefault();

    const emailDigitado = document.getElementById('email-login').value.trim().toLowerCase();
    const senhaDigitada = document.getElementById('senha-login').value.trim();

    if (!validarEmail(emailDigitado)) { alert("Por favor, insira um e-mail válido."); return; }
    if (senhaDigitada.length === 0) { alert("Por favor, insira sua senha."); return; }

    if (ehPaginaOng) {
        const dadosSalvos = localStorage.getItem('ongAdotaPet');
        if (!dadosSalvos) {
            alert("Nenhuma ONG encontrada. Por favor, faça o cadastro primeiro.");
            return;
        }
        const ongSalva = JSON.parse(dadosSalvos);
        if (emailDigitado === ongSalva.email && senhaDigitada === ongSalva.senha) {
            window.location.href = "/homepage.html";
        }
        else {
            alert("E-mail ou senha incorretos.");
        }

    }
    else {
        const dadosSalvos = localStorage.getItem('usuarioAdotaPet');

        if (!dadosSalvos) { alert("Nenhuma conta encontrada. Por favor, faça o cadastro primeiro."); return; }

        const usuarioSalvo = JSON.parse(dadosSalvos);

        if (emailDigitado === usuarioSalvo.email && senhaDigitada === usuarioSalvo.senha) {
            window.location.href = "/homepage.html";
        } else {
            alert("E-mail ou senha incorretos.");
        }
    }
});