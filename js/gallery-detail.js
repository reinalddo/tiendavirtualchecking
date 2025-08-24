document.addEventListener('DOMContentLoaded', function() {
    const galleryContainer = document.querySelector('.detail-gallery');
    if (!galleryContainer) return;

    const mainGalleryDiv = document.getElementById('detail-gallery-main');
    const backgroundDiv = document.getElementById('detail-gallery-background');
    const thumbnails = document.querySelectorAll('.detail-thumbnail');
    const galleryItems = Array.from(thumbnails);
    const modalElement = document.getElementById('gallery-modal');
    const galleryModal = new bootstrap.Modal(modalElement);
    const modalContentHost = document.getElementById('modal-content-host');
    let currentImageIndex = 0;

    const updateMainView = (index) => {
        currentImageIndex = index;
        const item = galleryItems[index];
        if (!item) return;
        const tipo = item.dataset.tipo;
        const url = item.dataset.url;
        let newContent = '';
        
        if (tipo === 'imagen') {
            galleryContainer.classList.remove('video-active');
            const imageUrl = `${BASE_URL}uploads/${url}`;
            newContent = `<img src="${imageUrl}" alt="Vista principal" class="clickable-gallery-image" data-index="${index}">`;
            if (backgroundDiv) backgroundDiv.style.backgroundImage = `url(${imageUrl})`;
        } else if (tipo === 'youtube') {
            galleryContainer.classList.add('video-active');
            newContent = `<iframe src="https://www.youtube.com/embed/${url}" frameborder="0" allowfullscreen class="clickable-gallery-image" data-index="${index}"></iframe>`;
            if (backgroundDiv) backgroundDiv.style.backgroundImage = 'none';
        }
        
        mainGalleryDiv.innerHTML = newContent;
        thumbnails.forEach(t => t.classList.remove('active'));
        item.classList.add('active');
    };

    const openModal = (index) => {
        currentImageIndex = index;
        const item = galleryItems[index];
        if (!item) return;
        const tipo = item.dataset.tipo;
        const url = item.dataset.url;
        let modalHTML = '';

        if (tipo === 'imagen') {
            modalHTML = `<img src="${BASE_URL}uploads/${url}">`;
        } else if (tipo === 'youtube') {
            modalHTML = `<iframe src="https://www.youtube.com/embed/${url}?autoplay=1&rel=0" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>`;
        }
        
        modalContentHost.innerHTML = modalHTML;
        galleryModal.show();
    };

    const changeImage = (direction) => {
        let newIndex = currentImageIndex + direction;
        if (newIndex >= galleryItems.length) newIndex = 0;
        else if (newIndex < 0) newIndex = galleryItems.length - 1;
        openModal(newIndex);
    };

    mainGalleryDiv.addEventListener('click', (e) => {
        if (e.target.classList.contains('clickable-gallery-image')) {
            openModal(parseInt(e.target.dataset.index));
        }
    });

    thumbnails.forEach(thumb => {
        thumb.addEventListener('click', (e) => {
            updateMainView(parseInt(e.currentTarget.dataset.index));
        });
    });
    
    modalElement.querySelector('.modal-prev').addEventListener('click', () => changeImage(-1));
    modalElement.querySelector('.modal-next').addEventListener('click', () => changeImage(1));
    
    modalElement.addEventListener('hidden.bs.modal', function () {
        modalContentHost.innerHTML = '';
    });

    if (galleryItems.length > 0) {
        updateMainView(0);
    }
});