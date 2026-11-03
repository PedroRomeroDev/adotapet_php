document.documentElement.classList.add("js-ready");

const panels = document.querySelectorAll("[data-panel]");
const menuItems = document.querySelectorAll(".menu-item");
const defaultSection = "informacoes";

function showSection(id) {
    const exists = Array.from(panels).some((panel) => panel.id === id);
    const target = exists ? id : defaultSection;

    panels.forEach((panel) => {
        panel.classList.toggle("active", panel.id === target);
    });

    menuItems.forEach((item) => {
        item.classList.toggle("active", item.dataset.section === target);
    });
}

menuItems.forEach((item) => {
    item.addEventListener("click", (event) => {
        event.preventDefault();
        const id = item.dataset.section;
        history.replaceState(null, "", "#" + id);
        showSection(id);
    });
});

showSection(window.location.hash.replace("#", ""));

window.addEventListener("hashchange", () => {
    showSection(window.location.hash.replace("#", ""));
});

const inputFoto = document.getElementById("foto-perfil");

if (inputFoto) {
    inputFoto.addEventListener("change", () => {
        if (inputFoto.files.length > 0) {
            document.getElementById("form-foto").submit();
        }
    });
}

const campoTelefone = document.getElementById("telefone_usuario");

function formatarTelefone(valor) {
    const numeros = valor.replace(/\D/g, "").slice(0, 13);

    if (numeros.length <= 2) {
        return numeros;
    }

    if (numeros.length <= 6) {
        return "(" + numeros.slice(0, 2) + ") " + numeros.slice(2);
    }

    if (numeros.length <= 10) {
        return "(" + numeros.slice(0, 2) + ") " + numeros.slice(2, 6) + "-" + numeros.slice(6);
    }

    return "(" + numeros.slice(0, 2) + ") " + numeros.slice(2, 7) + "-" + numeros.slice(7);
}

if (campoTelefone) {
    campoTelefone.value = formatarTelefone(campoTelefone.value);

    campoTelefone.addEventListener("input", () => {
        campoTelefone.value = formatarTelefone(campoTelefone.value);
    });
}

document.querySelectorAll(".toggle-password").forEach((botao) => {
    botao.addEventListener("click", () => {
        const campo = botao.parentElement.querySelector("input");
        const icone = botao.querySelector("i");
        const mostrando = campo.type === "text";

        campo.type = mostrando ? "password" : "text";
        icone.className = mostrando ? "bi bi-eye" : "bi bi-eye-slash";
        botao.setAttribute("aria-label", mostrando ? "Mostrar senha" : "Ocultar senha");
    });
});

const campoAvaliacao = document.getElementById("inputAvaliation");
const contador = document.getElementById("contador-avaliacao");

if (campoAvaliacao && contador) {
    campoAvaliacao.addEventListener("input", () => {
        contador.textContent = campoAvaliacao.value.length;
    });
}

const campoNovaSenha = document.getElementById("nova_senha");
const campoConfirmar = document.getElementById("confirmar_senha");

if (campoNovaSenha && campoConfirmar) {
    const validarConfirmacao = () => {
        const diferente = campoConfirmar.value !== "" && campoConfirmar.value !== campoNovaSenha.value;
        campoConfirmar.setCustomValidity(diferente ? "As senhas não coincidem." : "");
    };

    campoNovaSenha.addEventListener("input", validarConfirmacao);
    campoConfirmar.addEventListener("input", validarConfirmacao);
}

const alerta = document.querySelector(".alert");

if (alerta) {
    alerta.querySelector(".alert-close").addEventListener("click", () => alerta.remove());

    if (alerta.classList.contains("alert-sucesso")) {
        setTimeout(() => alerta.remove(), 5000);
    }
}

const modal = document.getElementById("modal-excluir");
const botaoExcluir = document.getElementById("delete-account");

function abrirModal() {
    modal.hidden = false;
    modal.querySelector("input").focus();
}

function fecharModal() {
    modal.hidden = true;
    modal.querySelector("form").reset();
    botaoExcluir.focus();
}

if (modal && botaoExcluir) {
    botaoExcluir.addEventListener("click", abrirModal);

    modal.querySelectorAll("[data-close]").forEach((elemento) => {
        elemento.addEventListener("click", fecharModal);
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && !modal.hidden) {
            fecharModal();
        }
    });
}