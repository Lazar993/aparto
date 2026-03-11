import Alpine from 'alpinejs'

window.Alpine = Alpine

Alpine.start()

document.addEventListener('submit', async function (event) {
    const form = event.target.closest('.aparto-wishlist-form');

    if (!form) {
        return;
    }

    event.preventDefault();

    const button = form.querySelector('[data-wishlist-button]');
    const icon = button ? button.querySelector('.aparto-wishlist-icon') : null;
    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : '';

    if (!button || !icon || !csrfToken) {
        form.submit();
        return;
    }

    if (button.dataset.loading === '1') {
        return;
    }

    button.dataset.loading = '1';
    button.disabled = true;

    try {
        const formData = new FormData(form);
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: formData,
        });

        if (!response.ok) {
            throw new Error('Wishlist request failed');
        }

        const data = await response.json();
        const isWishlisted = !!data.wishlisted;

        button.dataset.wishlisted = isWishlisted ? '1' : '0';
        button.classList.toggle('is-active', isWishlisted);

        const nextLabel = isWishlisted
            ? (button.dataset.labelRemove || '')
            : (button.dataset.labelAdd || '');

        if (nextLabel) {
            button.setAttribute('aria-label', nextLabel);
            button.setAttribute('title', nextLabel);
        }

        const nextIcon = isWishlisted
            ? (button.dataset.iconFull || '')
            : (button.dataset.iconEmpty || '');

        if (nextIcon) {
            icon.setAttribute('src', nextIcon);
        }
    } catch (error) {
        form.submit();
    } finally {
        button.disabled = false;
        button.dataset.loading = '0';
    }
});
