import {marked} from 'marked'

window.addEventListener('load', (event) => {
    const documentContent = document.getElementById('document_content')
    const initialContent = documentContent.value;
    const changesUnsaved = document.getElementById('changesUnsaved');
    const initMetaBtn = document.getElementById('init_meta_btn')
    const preview = document.getElementById('markdownPreview')
    const links = document.querySelectorAll('.og-preview')

    const fetchMeta = () => {
        fetch(`/user/document/meta/${initMetaBtn.dataset.id}`)
            .then(response => response.json())
            .then(data => {
                documentContent.value = data.formatted
            })
    }

    const extractOg = (response) => {
        const parser = new DOMParser()
        const html = parser.parseFromString(response, 'text/html')
        const metas = html.querySelectorAll('meta')

        metas.forEach(meta => {
            const property = meta.getAttribute("property")
            const content = meta.getAttribute("content")

            if (property !== null && property.startsWith('og:')) {
                const type =  property.replace("og:", "")
                console.log(type, content)
            }
        })
    }

    // Set Markdown preview
    preview.innerHTML = marked.parse(preview.innerHTML)

    // Init document meta-data
    document.getElementById('confirmInitMeta').addEventListener('click', () => {
        fetchMeta()
    })


    const watchUnsaved = (event) => {
        if (event.target.value !== initialContent) {
            changesUnsaved.classList.remove('invisible')
        } else {
            changesUnsaved.classList.add('invisible')
        }
    }

    documentContent.addEventListener('change', watchUnsaved)
    documentContent.addEventListener('keyup', watchUnsaved)

    // Add Open Graph preview for links
    /*
    links.forEach((item) => {
        item.addEventListener('mouseover', (event) => {
            const url = event.target.getAttribute('href')
            fetch(url, {
                mode: 'no-cors',
            }).then(res => res.text())
                .then(data => extractOg(data))
        })
    })
    */
})