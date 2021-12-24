export default function JsonSubmit(event) {
    event.preventDefault()
    const url = event.target.getAttribute('action')
    const formData = new FormData(event.target)

    return fetch(url, {method: 'POST', body: formData}).then(res => res.json())
}