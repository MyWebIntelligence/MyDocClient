const annotationContainer = document.getElementById('annotationContainer');
const annotationFilterBtn = document.getElementById('annotationFilterBtn');

window.addEventListener('click', (event) => {
    if (event.target.id === 'annotationFilterBtn') {
        event.preventDefault();
        const form = event.target.closest('form');
        const data = new URLSearchParams(new FormData(form));
        fetch(`${form.getAttribute('action')}?${data}`)
            .then(res => res.text())
            .then(data => annotationContainer.innerHTML = data);
    }
});