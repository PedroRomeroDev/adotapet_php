const toggleModoEscuro = document.getElementById('toggle-modoEscuro');
const toggleAltoContraste = document.getElementById('toggle-altoContraste');

let preferences = {
  modoEscuro: false,
  altoContraste: false
};

if (localStorage.getItem('preferences')) {
  preferences = JSON.parse(localStorage.getItem('preferences'));
} else {
  localStorage.setItem('preferences', JSON.stringify(preferences));
}

function salvarConfig(chave, valor) {
  preferences[chave] = valor;
  localStorage.setItem('preferences', JSON.stringify(preferences));
}

toggleModoEscuro.checked = preferences.modoEscuro;
toggleAltoContraste.checked = preferences.altoContraste;

toggleModoEscuro.addEventListener('change', () => {
  salvarConfig('modoEscuro', toggleModoEscuro.checked);
});

toggleAltoContraste.addEventListener('change', () => {
  salvarConfig('altoContraste', toggleAltoContraste.checked);
});

const deleteButton = document.getElementById("delete-account")

deleteButton.addEventListener("click", () => {
  let prosseguir = confirm("Essa ação excluirá sua conta permanentemente, deseja prosseguir?");

  if(prosseguir) {
    window.location.href = "excluir_conta.php"
  }
})

let input = document.getElementById("inputAvaliation")
input.addEventListener("keyup", () => {
  let escrita = input.value
  if(input.value.length == 100){
    alert("A avaliação deve conter no máximo 100 caracteres.")
  }
})
