import '../project/new-tag';
import '../project/delete-tag';
import '../project/rename-tag';
import '../project/tag-tree';
import Modal from 'bootstrap/js/dist/modal';
import {marked} from 'marked';
import jsCookie from "js-cookie";

const documentContent = document.getElementById('document_content');
const initialContent = documentContent ? documentContent.value : '';
const changesUnsaved = document.getElementById('changesUnsaved');
const initMetaBtn = document.getElementById('init_meta_btn');
const annotateBtn = document.getElementById('annotate_btn');
const submitAnnotation = document.getElementById('submitAnnotation');
const annotationSelection = document.getElementById('annotationSelection');
const linkSelection = document.getElementById('linkSelection');
const preview = document.getElementById('markdownPreview');
const toolsModal = Modal.getOrCreateInstance(document.getElementById('selectionToolsModal'));
const annotationTools = document.getElementById('annotationTools');
const tabButtons = document.querySelectorAll('#documentRenderMode button[data-bs-toggle="tab"]');
const asyncSearchBtn = document.getElementById('asyncSearch');
const asyncDocuments = document.getElementById('asyncDocuments');
const searchBar = document.getElementById('document_search');
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
};

const watchUnsaved = (event) => {
    if (event.target.value !== initialContent) {
        changesUnsaved.classList.remove('visually-hidden');
    } else {
        changesUnsaved.classList.add('visually-hidden');
    }
};

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
};

const selectText = (event) => {
    let textSelection;

    annotationSelection.value = '';
    linkSelection.value = '';

    if (event.target.value) {
        textSelection = event.target.value.substring(event.target.selectionStart, event.target.selectionEnd);

        if (textSelection !== '') {
            annotateBtn.disabled = false;
            annotationSelection.value = textSelection;
            linkSelection.value = textSelection;
        } else {
            annotateBtn.disabled = true;
        }
    }
};

const searchDocuments = (event) => {
    event.preventDefault();
    const searchUrl = event.currentTarget.dataset.url;
    fetch(`${searchUrl}?q=${searchBar.value}`)
        .then(response => response.text())
        .then(data => asyncDocuments.innerHTML = data);
};


window.addEventListener('load', () => {
    // Save opened tab for preselection
    if (tabButtons) {
        tabButtons.forEach((item) => {
            item.addEventListener('shown.bs.tab', (event) => {
                jsCookie.set('activeDocumentTab', event.target.id);
            });
        });
    }

    // Set Markdown preview
    preview.innerHTML = marked.parse(preview.innerHTML);

    if (submitAnnotation) {
        submitAnnotation.addEventListener('click', (event) => {
            annotationTools.submit();
        });
    }

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

    if (asyncSearchBtn && searchBar) {
        asyncSearchBtn.addEventListener('click', searchDocuments);
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

window.addEventListener('click', (event) => {
    if (event.target.classList.contains('btn-link-document')) {
        event.preventDefault();
        const form = document.createElement('form');
        const selection = document.createElement('textarea');

        form.setAttribute('action', event.target.getAttribute('href'));
        form.setAttribute('method', 'POST');

        selection.setAttribute('name', 'selection');
        selection.value = linkSelection.value;

        form.appendChild(selection);
        document.body.appendChild(form);
        form.submit();
    }
});