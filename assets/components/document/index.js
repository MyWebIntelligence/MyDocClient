import '../project/new-tag';
import '../project/delete-tag';
import '../project/rename-tag';
import '../project/tag-tree';
import Modal from 'bootstrap/js/dist/modal';
import {marked} from 'marked';

window.addEventListener('load', () => {
    const documentContent = document.getElementById('document_content');
    const initialContent = documentContent ? documentContent.value : '';
    const changesUnsaved = document.getElementById('changesUnsaved');
    const initMetaBtn = document.getElementById('init_meta_btn');
    const annotationSelection = document.getElementById('annotationSelection');
    const preview = document.getElementById('markdownPreview');
    const toolsModal = Modal.getOrCreateInstance(document.getElementById('selectionToolsModal'));
    const links = document.querySelectorAll('.og-preview');

    const extractOg = (response) => {
        const parser = new DOMParser();
        const html = parser.parseFromString(response, 'text/html');
        const metas = html.querySelectorAll('meta');

        metas.forEach(meta => {
            const property = meta.getAttribute("property");
            const content = meta.getAttribute("content");

            if (property !== null && property.startsWith('og:')) {
                const type =  property.replace("og:", "");
            }
        })
    }

    const watchUnsaved = (event) => {
        if (event.target.value !== initialContent) {
            changesUnsaved.classList.remove('visually-hidden');
        } else {
            changesUnsaved.classList.add('visually-hidden');
        }
    }

    const calculateOffset = (child, relativeOffset) => {
        let parent = child.parentElement;
        const children = [];

        if (parent.tagName !== 'P') {
            parent = parent.closest('p');
            child = child.parentElement;
        }

        if (parent) {
            for (let c of parent.childNodes) {
                if (c === child) break;
                children.push(c);
            }
        }

        return relativeOffset + children.reduce((a, c) => a + c.textContent.length, 0);
    }

    const selectText = (event) => {
        let textSelection;

        if (event.target.value) {
            textSelection = event.target.value.substring(event.target.selectionStart, event.target.selectionEnd);
        } else {
            const text = event.currentTarget.innerText;
            const selection = window.getSelection();
            const start = selection.anchorOffset;
            const end = selection.extentOffset;
            const anchorNode = selection.anchorNode;
            const extentNode = selection.extentNode;
            textSelection = text.substring(calculateOffset(anchorNode, start), calculateOffset(extentNode, end));
        }

        if (textSelection !== '') {
            annotationSelection.value = textSelection;
            toolsModal.show();
        } else {
            annotationSelection.value = '';
        }
    }

    // Set Markdown preview
    preview.innerHTML = marked.parse(preview.innerHTML);

    if (documentContent) {
        const fetchMeta = () => {
            fetch(`/user/document/meta/${initMetaBtn.dataset.id}`)
                .then(response => response.json())
                .then(data => {
                    documentContent.value = data.formatted;
                    changesUnsaved.classList.remove('invisible');
                });
        };

        documentContent.addEventListener('mouseup', selectText);

        document.getElementById('confirmInitMeta').addEventListener('click', () => {
            fetchMeta();
        });

        // Init document meta-data
        documentContent.addEventListener('change', watchUnsaved);
        documentContent.addEventListener('keyup', watchUnsaved);
    }

    // if (preview) {
    //     preview.addEventListener('mouseup', selectText);
    // }

    // Add Open Graph preview for links
    // links.forEach((item) => {
    //     item.addEventListener('mouseover', (event) => {
    //         const url = event.target.getAttribute('href');
    //         fetch(url, {
    //             mode: 'no-cors',
    //         }).then(res => res.text())
    //             .then(data => extractOg(data))
    //     });
    // });
});