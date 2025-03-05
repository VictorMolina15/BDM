function showPage(pageNumber) {
    document.querySelectorAll('.page-content').forEach(function(page) {
        page.classList.add('d-none')
    })
    document.getElementById('page-' + pageNumber).classList.remove('d-none')
}
