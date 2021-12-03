window.addEventListener('load', (event) => {
    const cbCheckAll = document.getElementById('cb_document_all')
    const cbChecks = document.querySelectorAll('.cb_document')
    const selectionMenu = document.getElementById('selection_menu')
    const deleteDocuments = document.getElementById('delete_documents')
    const sendInviteForm = document.getElementById('sendInviteForm')
    const sharedPermissions = document.getElementById('sharedPermissions')
    const shareMessage = document.getElementById('shareMessage')

    function getSelectedDelete(item) {
        return deleteDocuments.querySelector(`option[value="${item.dataset.id}"]`)
    }

    function setSelectionMenu(state) {
        state ? selectionMenu.removeAttribute('disabled')
              : selectionMenu.setAttribute('disabled', 'disabled')
    }

    cbCheckAll.addEventListener('click', (event) => {
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

    sendInviteForm.addEventListener('submit', (event) => {
        event.preventDefault();
        const url = event.target.getAttribute('action')
        const formData = new FormData(event.target)
        const element = document.createElement('div')
        element.innerHTML = sharedPermissions.dataset.prototype.replace('__email__', formData.get('email'))

        fetch(url,{
            method: 'POST',
            body: formData
        }).then(res => res.json())
            .then(data => {
                if (data.res) {
                    sharedPermissions.appendChild(element)
                    element.querySelector(`option[value=${formData.get('permission')}]`).selected = true
                }
                shareMessage.innerHTML = data.message
                event.target.reset()
            })
    })
})