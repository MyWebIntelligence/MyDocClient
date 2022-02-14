import Modal from "bootstrap/js/dist/modal";

const annotationContainer = document.getElementById('annotationContainer');
const confirmDeleteModal = Modal.getOrCreateInstance(document.getElementById('confirmDeleteModal'));
const confirmDeleteButton = document.getElementById('confirmDeleteButton');

const confirmDeletion = () => {
    if (deleteCallback) {
        deleteCallback();
        deleteCallback = null;
    }
};

let deleteButton;
let deleteCallback;

window.addEventListener('click', (event) => {
    // Filter annotations
    if (event.target.id === 'annotationFilterBtn') {
        event.preventDefault();
        const form = event.target.closest('form');
        const data = new URLSearchParams(new FormData(form));
        fetch(`${form.getAttribute('action')}?${data}`)
            .then(res => res.text())
            .then(data => annotationContainer.innerHTML = data);
    }

    // Delete annotation
    deleteButton = event.target.closest('a');

    if (deleteButton && deleteButton.classList.contains('delete-annotation')) {
        deleteCallback = () => {
            event.preventDefault();
            const block = document.getElementById(deleteButton.dataset.blockId);

            fetch(deleteButton.getAttribute('href'))
                .then(res => res.text())
                .then(data => {
                    if (JSON.parse(data)) {
                        block.remove();
                    }
                });

            confirmDeleteModal.hide();
        }
    }
});

if (confirmDeleteButton) {
    confirmDeleteButton.addEventListener('click', confirmDeletion);
}
