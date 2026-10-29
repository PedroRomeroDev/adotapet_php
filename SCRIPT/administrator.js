const items = document.querySelectorAll(".aside-list li");
const sections = document.querySelectorAll(".content");

function showSection(id) {
    // Esconde TODAS as seções (o bug era remover "show" dos <li>, não das seções)
    sections.forEach((section) => section.classList.remove("show"));

    const target = document.getElementById(id);
    if (target) target.classList.add("show");
}

function activate(li) {
    items.forEach((item) => item.classList.remove("ativo"));
    li.classList.add("ativo");
    showSection(li.dataset.content);
}

items.forEach((li) => {
    // Acessibilidade: permite navegar com Tab e ativar com Enter/Espaço
    li.setAttribute("tabindex", "0");
    li.setAttribute("role", "button");

    li.addEventListener("click", () => activate(li));
    li.addEventListener("keydown", (e) => {
        if (e.key === "Enter" || e.key === " ") {
            e.preventDefault();
            activate(li);
        }
    });
});

// Abre a primeira aba por padrão
if (items.length) activate(items[0]);