import JsonSubmit from '../json-submit';
import Modal from 'bootstrap/js/dist/modal';
import Sortable from "sortablejs";

const newTagModal = document.getElementById('newTagModal');
const renameTagModal = document.getElementById('renameTagModal');
const deleteTagModal = document.getElementById('deleteTagModal');
const tagModalForms = document.querySelectorAll('.tag-modal form');

const initTagTree = () => {
    const tagRoot = document.getElementById('tagRoot')
    const tagTree = document.querySelectorAll('.tag-tree')
    const tagLabels = document.querySelectorAll('.tag-label')

    // Init tag sortable
    tagTree.forEach((element) => {
        if (element.classList.contains('sortable')) {
            const options = {
                group: 'nested',
                fallbackOnBody: true,
                swapThreshold: 0.65,
                ghostClass: 'bg-light',
                handle: '.bi-arrows-move',
                animation: 150,
                easing: 'cubic-bezier(1, 0, 0, 1)',
                onEnd: () => {
                    const nestedQuery = '.tag-tree'
                    const dataId = 'id'
                    const root = tagRoot.children[0]
                    const saveUrl = tagRoot.dataset.saveUrl

                    const serialize = (sortable) => {
                        let serialized = []
                        let children = [].slice.call(sortable.children)

                        for (let i in children) {
                            let nested = children[i].querySelector(nestedQuery)
                            serialized.push({
                                id: children[i].dataset[dataId],
                                children: nested ? serialize(nested) : []
                            })
                        }

                        return serialized
                    }

                    const data = new FormData()
                    data.append('updatedTree', JSON.stringify(serialize(root)))

                    fetch(saveUrl, {
                        method: 'POST',
                        body: data,
                    }).then(res => res.json())
                        .then(data => data)
                }
            }

            Sortable.create(element, options)
        }
    })

    // Display tag toolbar on hover
    tagLabels.forEach((tag) => {
        tag.addEventListener('mouseover', (event) => {
            event.stopImmediatePropagation()
            event.currentTarget.querySelector('.tag-tools').classList.remove('visually-hidden')
        })
        tag.addEventListener('mouseout', (event) => {
            event.stopImmediatePropagation()
            event.currentTarget.querySelector('.tag-tools').classList.add('visually-hidden')
        })
    })
}

// Set tag properties on new tag event
newTagModal && newTagModal.addEventListener('show.bs.modal', (event) => {
    const eParentId = newTagModal.querySelector('#newTag_parentId');
    const eParentName = newTagModal.querySelector('#newTag_parentName');
    const parentId = event.relatedTarget.dataset.parentId;
    const parentName = event.relatedTarget.dataset.name;

    eParentId.value = parentId;

    if (parentName) {
        eParentName.innerHTML = `sous ${parentName}`;
    }
});

newTagModal && newTagModal.addEventListener('shown.bs.modal', () => {
    const eName = newTagModal.querySelector('#newTag_name');
    eName.focus();
});

renameTagModal && renameTagModal.addEventListener('show.bs.modal', (event) => {
    const eId = renameTagModal.querySelector('#renameTag_id');

    const eName = renameTagModal.querySelector('#renameTag_name');
    const eNewName = renameTagModal.querySelector('#renameTag_newName');
    const eNewDescription = renameTagModal.querySelector('#renameTag_newDescription');

    const id = event.relatedTarget.dataset.id;
    const name = event.relatedTarget.dataset.name;
    const description = event.relatedTarget.dataset.description;

    eId.value = id;
    eNewName.value = name;
    eNewDescription.value = description;

    if (name) {
        eName.innerHTML = `${name}`;
    }
});

renameTagModal && renameTagModal.addEventListener('shown.bs.modal', () => {
    const eNewName = renameTagModal.querySelector('#renameTag_newName');
    const eNewDescription = renameTagModal.querySelector('#renameTag_newDescription');
    eNewName.select();
});

// Set tag properties on delete tag event
deleteTagModal && deleteTagModal.addEventListener('show.bs.modal', (event) => {
    const eId = deleteTagModal.querySelector('#deleteTag_id');
    const eName = deleteTagModal.querySelector('#deleteTag_name');
    const id = event.relatedTarget.dataset.id;
    const name = event.relatedTarget.dataset.name;
    eId.value = id;
    if (name) {
        eName.innerHTML = `${name}`;
    }
});

tagModalForms.forEach(form => {
    form.addEventListener('submit', event => {
        JsonSubmit(event).then(data => {
            fetch(`/tags/async-tag-tree/${projectId}`).then(res => res.text()).then(data => {
                const container = document.querySelector('#propertiesPanel_tags_content .accordion-body');
                if (container) {
                    container.innerHTML = data;
                    initTagTree();

                    [newTagModal, renameTagModal, deleteTagModal].forEach(modal => {
                        const bsModal = Modal.getOrCreateInstance(modal);
                        bsModal.hide();
                    })
                }
            });
        });
    });
});

window.addEventListener('load', event => {
    initTagTree();
})

