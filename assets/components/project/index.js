import './new-tag'
import './delete-tag'
import './rename-tag'
import './share'
import './tag-tree'

window.addEventListener('load', () => {
    const cbCheckAll = document.getElementById('cb_document_all')
    const cbChecks = document.querySelectorAll('.cb_document')
    const selectionMenu = document.getElementById('selection_menu')
    const deleteDocuments = document.getElementById('delete_documents')

    function getSelectedDelete(item) {
        return deleteDocuments.querySelector(`option[value="${item.dataset.id}"]`)
    }

    function setSelectionMenu(state) {
        state ? selectionMenu.removeAttribute('disabled')
              : selectionMenu.setAttribute('disabled', 'disabled')
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