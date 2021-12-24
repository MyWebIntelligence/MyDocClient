import JsonSubmit from '../json-submit'

window.addEventListener('load', () => {
    const newTagModal = document.getElementById('newTagModal')
    const newTagForm = document.getElementById('newTagForm')

    // Set tag properties on new tag event
    newTagModal && newTagModal.addEventListener('show.bs.modal', (event) => {
        const eParentId = newTagModal.querySelector('#newTag_parentId')
        const eParentName = newTagModal.querySelector('#newTag_parentName')
        const parentId = event.relatedTarget.dataset.parentId
        const parentName = event.relatedTarget.dataset.name

        eParentId.value = parentId

        if (parentName) {
            eParentName.innerHTML = `sous ${parentName}`;
        }
    })

    newTagModal && newTagModal.addEventListener('shown.bs.modal', () => {
        const eName = newTagModal.querySelector('#newTag_name')
        eName.focus();
    })

    newTagForm && newTagForm.addEventListener('submit', (event) => {
        JsonSubmit(event).then(() => window.location.reload())
    })
})