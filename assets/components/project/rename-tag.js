import JsonSubmit from '../json-submit'

window.addEventListener('load', () => {
    const renameTagModal = document.getElementById('renameTagModal')
    const renameTagForm = document.getElementById('renameTagForm')

    // Set tag properties on rename tag event
    renameTagModal && renameTagModal.addEventListener('show.bs.modal', (event) => {
        const eId = renameTagModal.querySelector('#renameTag_id')
        const eName = renameTagModal.querySelector('#renameTag_name')
        const eNewName = renameTagModal.querySelector('#renameTag_newName')
        const id = event.relatedTarget.dataset.id
        const name = event.relatedTarget.dataset.name

        eId.value = id
        eNewName.value = name

        if (name) {
            eName.innerHTML = `${name}`;
        }
    })

    renameTagModal && renameTagModal.addEventListener('shown.bs.modal', () => {
        const eNewName = renameTagModal.querySelector('#renameTag_newName')
        eNewName.select();
    })

    renameTagForm && renameTagForm.addEventListener('submit', (event) => {
        JsonSubmit(event).then(() => window.location.reload())
    })

})