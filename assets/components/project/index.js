import './tag'
import './share'

window.addEventListener('load', () => {
    const cbCheckAll = document.getElementById('cb_document_all')
    const cbChecks = document.querySelectorAll('.cb_document')
    const deleteBtn = document.getElementById('deleteSelected')
    const deleteDocuments = document.getElementById('delete_documents')

    function getSelectedDelete(item) {
        return deleteDocuments.querySelector(`option[value="${item.dataset.id}"]`)
    }

    function setSelectionMenu(state) {
        if (state === true) {
            deleteBtn.removeAttribute('disabled')
            deleteBtn.classList.remove('d-none')
            deleteBtn.classList.add('d-inline')
        } else {
            deleteBtn.setAttribute('disabled', 'disabled')
            deleteBtn.classList.add('d-none')
            deleteBtn.classList.remove('d-inline')
        }
    }


    cbCheckAll && cbCheckAll.addEventListener('click', (event) => {
        cbChecks.forEach((item) => {
            const selectedDelete = getSelectedDelete(item)
            item.checked = event.target.checked
            selectedDelete.selected = event.target.checked
        })
        setSelectionMenu(event.target.checked)
    })

    cbChecks.forEach((item) => {
        item.addEventListener('click', (event) => {
            const hasAnyChecked = Array.from(cbChecks).some((item) => item.checked)
            const selectedDelete = getSelectedDelete(item)
            cbCheckAll.checked = hasAnyChecked
            selectedDelete.selected = event.target.checked
            setSelectionMenu(event.target.checked || hasAnyChecked)
        })
    })

})