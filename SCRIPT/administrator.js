const items = document.querySelectorAll(".aside-list li");

items.forEach((li) => {
    li.addEventListener("click", () => {

        items.forEach((item) => {
            item.classList.remove("ativo")
        })

        li.classList.add("ativo");
    })
})