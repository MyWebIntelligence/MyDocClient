import Sortable from 'sortablejs'

window.addEventListener('load', () => {
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
                        .then(data => console.log(data))
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

})