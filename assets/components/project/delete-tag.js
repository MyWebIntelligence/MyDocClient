import JsonSubmit from '../json-submit'

window.addEventListener('load', () => {
    const deleteTagModal = document.getElementById('deleteTagModal')
    const deleteTagForm = document.getElementById('deleteTagForm')

    // Set tag properties on delete tag event
    deleteTagModal && deleteTagModal.addEventListener('show.bs.modal', (event) => {
        const eId = deleteTagModal.querySelector('#deleteTag_id')
        const eName = deleteTagModal.querySelector('#deleteTag_name')
        const id = event.relatedTarget.dataset.id
        const name = event.relatedTarget.dataset.name
        eId.value = id
        if (name) {
            eName.innerHTML = `${name}`;
        }
    })

    deleteTagForm && deleteTagForm.addEventListener('submit', (event) => {
        JsonSubmit(event).then(() => window.location.reload())
    })
})