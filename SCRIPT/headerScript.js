document.getElementById('menuBtn').addEventListener('click', () => {
    let nav = document.getElementById('navBar')
    if(nav.classList.contains('active')){
        nav.classList.remove('active')
    } else {
        nav.classList.add('active')
    }
})