/*
 * Inline post editing. Loaded ONCE from the layout (not from the post card),
 * so it works for posts added later by AJAX and never registers twice.
 * Uses the layout's globals: HEADERS and toast().
 */
(() => {
    const MAX_BYTES = 4 * 1024 * 1024;
    const MAX_CHARS = 5000;

    const q = (root, selector) => root.querySelector(selector);
    const parts = (el) => {
        const card = el.closest('[data-post]');
        return { card, content: q(card, '[data-post-content]'), form: q(card, '[data-post-edit-form]') };
    };

    const grow = (ta) => { ta.style.height = 'auto'; ta.style.height = Math.min(ta.scrollHeight, 480) + 'px'; };
    const count = (form) => { q(form, '[data-edit-count]').textContent = q(form, 'textarea').value.length + ' / ' + MAX_CHARS; };

    function showPreview(form, url) {
        const wrap = q(form, '[data-edit-preview]');
        const img = q(form, '[data-edit-preview-img]');
        if (url) { img.src = url; wrap.classList.remove('hidden'); }
        else { img.removeAttribute('src'); wrap.classList.add('hidden'); }
        q(form, '[data-edit-file-label]').textContent = url ? 'Change photo' : 'Add photo';
    }

    function showError(form, message) {
        const el = q(form, '[data-post-edit-error]');
        el.textContent = message || '';
        el.classList.toggle('hidden', !message);
    }

    // Put the editor back to what is saved on the server
    function resetEditor(form) {
        form.reset();
        q(form, '[data-remove-media]').value = '0';
        q(form, '[data-edit-file]').value = '';
        showPreview(form, form.dataset.originalMedia || '');
        showError(form, '');
    }

    function openEditor(card, content, form) {
        content.classList.add('hidden');
        form.classList.remove('hidden');
        const ta = q(form, 'textarea');
        grow(ta); count(form);
        ta.focus();
        ta.setSelectionRange(ta.value.length, ta.value.length);
    }

    function closeEditor(content, form) {
        form.classList.add('hidden');
        content.classList.remove('hidden');
    }

    // Update the visible post after the server confirms the change
    function applyUpdate(card, form, d) {
        const body = q(card, '[data-post-body]');
        body.textContent = d.body || '';
        body.classList.toggle('hidden', !d.body);

        let media = q(card, '[data-post-media]');
        if (d.media_url) {
            if (!media) {
                media = document.createElement('a');
                media.setAttribute('data-post-media', '');
                media.target = '_blank'; media.rel = 'noopener';
                media.className = 'mx-5 mt-4 block overflow-hidden rounded-xl border border-ink-700';
                const img = document.createElement('img');
                img.alt = 'Photo from a post'; img.loading = 'lazy';
                img.className = 'max-h-[460px] w-full object-cover';
                media.append(img);
                q(card, '[data-post-content]').append(media);
            }
            media.href = d.media_url;
            q(media, 'img').src = d.media_url;
        } else if (media) {
            media.remove();
        }

        const badge = q(card, '[data-visibility-badge]');
        badge.textContent = d.visibility === 'public' ? 'Public' : 'Only me';
        badge.classList.toggle('hidden', d.visibility === 'friends');
        q(card, '[data-edited]').classList.remove('hidden');

        // Make the saved values the new "defaults" of the editor
        q(form, 'textarea').defaultValue = d.body || '';
        form.querySelectorAll('select[name="visibility"] option').forEach((o) => { o.defaultSelected = o.value === d.visibility; });
        form.dataset.originalMedia = d.media_url || '';
        q(form, '[data-remove-media]').value = '0';
        q(form, '[data-edit-file]').value = '';
        showPreview(form, d.media_url || '');
    }

    document.addEventListener('click', (e) => {
        const edit = e.target.closest('[data-post-edit]');
        if (edit) { const { card, content, form } = parts(edit); openEditor(card, content, form); return; }

        const cancel = e.target.closest('[data-post-edit-cancel]');
        if (cancel) { const { content, form } = parts(cancel); resetEditor(form); closeEditor(content, form); return; }

        const remove = e.target.closest('[data-edit-remove-photo]');
        if (remove) {
            const { form } = parts(remove);
            q(form, '[data-remove-media]').value = '1';
            q(form, '[data-edit-file]').value = '';
            showPreview(form, '');
        }
    });

    document.addEventListener('change', (e) => {
        const input = e.target.closest('[data-edit-file]');
        if (!input || !input.files[0]) return;
        const form = input.closest('form');
        const file = input.files[0];
        if (file.size > MAX_BYTES) { toast('Please choose a photo under 4 MB.'); input.value = ''; return; }
        q(form, '[data-remove-media]').value = '0';
        showPreview(form, URL.createObjectURL(file));
    });

    document.addEventListener('input', (e) => {
        if (e.target.matches('[data-post-edit-form] textarea')) { grow(e.target); count(e.target.closest('form')); }
    });

    document.addEventListener('keydown', (e) => {
        const form = e.target.closest && e.target.closest('[data-post-edit-form]');
        if (!form) return;
        if (e.key === 'Escape') { e.preventDefault(); q(form, '[data-post-edit-cancel]').click(); }
        if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); form.requestSubmit(); }
    });

    document.addEventListener('submit', async (e) => {
        const form = e.target;
        if (!form.matches('[data-post-edit-form]')) return;
        e.preventDefault();

        const { card, content } = parts(form);
        const save = q(form, '[data-post-edit-save]');
        showError(form, '');

        const hasText = q(form, 'textarea').value.trim() !== '';
        const hasPhoto = !q(form, '[data-edit-preview]').classList.contains('hidden');
        if (!hasText && !hasPhoto) return showError(form, 'A post needs some text or a photo.');

        const label = save.textContent;
        save.disabled = true; save.textContent = 'Saving…';

        try {
            const r = await fetch(form.dataset.url, { method: 'POST', headers: HEADERS, body: new FormData(form) });
            const d = await r.json().catch(() => ({}));
            if (!r.ok) {
                const first = d.errors ? Object.values(d.errors)[0][0] : d.message;
                throw new Error(first || 'Could not save your changes.');
            }
            applyUpdate(card, form, d);
            closeEditor(content, form);
            toast('Post updated.');
        } catch (err) {
            showError(form, err.message);
        }

        save.disabled = false; save.textContent = label;
    });
})();