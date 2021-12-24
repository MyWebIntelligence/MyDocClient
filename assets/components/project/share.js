window.addEventListener('load', () => {
    const sendInviteForm = document.getElementById('sendInviteForm')
    const sharedPermissions = document.getElementById('sharedPermissions')
    const sharedEmpty = document.getElementById('sharedEmpty')
    const shareMessage = document.getElementById('shareMessage')
    const roleSelectors = document.querySelectorAll('.select-user-role')
    const deleteButtons = document.querySelectorAll('.delete-user-role')

    const updateRequest = (event) => {
        const selector = event.target
        fetch(selector.dataset.updateUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                email: selector.dataset.userEmail,
                role: selector.value,
            })
        }).then(res => res.json())
            .then(data => {
                if (data.res) {
                    const validateClass = 'was-validated'
                    selector.parentNode.classList.add(validateClass)
                    window.setTimeout(() => {
                        selector.parentNode.classList.remove(validateClass)
                    }, 3000)
                }
            })
    }

    const deleteRequest = (event) => {
        const element = event.currentTarget
        fetch(element.dataset.deleteUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({email: element.dataset.userEmail})
        }).then(res => res.json())
            .then(data => {
                if (data.res) {
                    element.closest('.permission-item').remove()
                }
            })
    }

    roleSelectors.forEach((item) => {
        item.addEventListener('change', updateRequest)
    })

    deleteButtons.forEach((item) => {
        item.addEventListener('click', deleteRequest, true)
    })

    sendInviteForm && sendInviteForm.addEventListener('submit', (event) => {
        event.preventDefault();
        const url = event.target.getAttribute('action')
        const formData = new FormData(event.target)
        const element = document.createElement('div')
        element.innerHTML = sharedPermissions.dataset.prototype.replace(/__email__/g, formData.get('email'))
        element.querySelector('.select-user-role').addEventListener('change', updateRequest)
        element.querySelector('.delete-user-role').addEventListener('click', deleteRequest, true)

        fetch(url,{
            method: 'POST',
            body: formData
        }).then(res => res.json())
            .then(data => {
                if (data.res) {
                    sharedEmpty && sharedEmpty.remove()
                    sharedPermissions.appendChild(element)
                    element.querySelector(`option[value=${formData.get('permission')}]`).selected = true
                }
                shareMessage.innerHTML = data.message
                event.target.reset()
            })
    })
})