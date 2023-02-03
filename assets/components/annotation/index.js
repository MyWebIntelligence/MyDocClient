import Modal from "bootstrap/js/dist/modal";

const confirmDeleteAnnotationModal = Modal.getOrCreateInstance(document.getElementById('confirmDeleteAnnotationModal'));
const confirmDeleteAnnotationButton = document.getElementById('confirmDeleteAnnotationButton');
const editAnnotationModal = Modal.getOrCreateInstance(document.getElementById('editAnnotationModal'));
const editAnnotationContainer = document.getElementById('editAnnotationContainer');

const confirmDeletion = () => {
    if (deleteCallback) {
        deleteCallback();
        deleteCallback = null;
    }
};

let deleteCallback;

window.addEventListener('click', (event) => {
    // Delete annotation
    const button = event.target.closest('a, button');

    if (button) {
        if (button.classList.contains('delete-annotation')) {
            deleteCallback = () => {
                event.preventDefault();
                const block = document.getElementById(button.dataset.blockId);

                fetch(button.getAttribute('href'))
                    .then(res => res.text())
                    .then(data => {
                        if (JSON.parse(data)) {
                            block.remove();
                        }
                    });

                confirmDeleteAnnotationModal.hide();
            }
        } else if (button.classList.contains('edit-annotation')) {
            event.preventDefault();
            const block = document.getElementById(button.dataset.blockId);
            fetch(`/annotations/edit-form/${button.dataset.annotationId}`)
                .then(res => res.text())
                .then(data => {
                    editAnnotationContainer.innerHTML = data;
                    editAnnotationModal.show();
                });
        }
    }
});

if (confirmDeleteAnnotationButton) {
    console.log('delete');
    confirmDeleteAnnotationButton.addEventListener('click', confirmDeletion);
}
