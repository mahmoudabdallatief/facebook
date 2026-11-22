const CHUNK_SIZE = 1024 * 1024;
const MAX_FILE_SIZE = 2 * 1024 * 1024 * 1024;
const MAX_FILES = 10;
const CHUNK_CONCURRENCY = 3;

const routes = window.postUploadRoutes || {
    initiate: '/uploads/initiate',
    chunk: '/uploads/chunk',
    status: '/uploads/status',
    cancel: '/uploads/cancel',
};

const messages = window.POST_UPLOAD_TEXT || {
    maxFiles: 'A post can contain up to 10 photos or videos.',
    maxSize: 'Each file can be up to 2 GB.',
    invalidType: 'Choose a supported photo or video.',
    wait: 'Wait for uploads to finish before publishing.',
    failed: 'Remove files that failed to upload, then try again.',
    waiting: 'Waiting to upload',
    uploading: 'Uploading :percent%',
    complete: 'Upload complete',
    remove: 'Remove file',
};

class ChunkedUploader {
    constructor(onProgress) {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        this.onProgress = onProgress;
    }

    headers(json = false) {
        return {
            ...(json ? { 'Content-Type': 'application/json' } : {}),
            'X-CSRF-TOKEN': this.csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        };
    }

    async request(url, options) {
        const response = await fetch(url, options);
        const result = await response.json().catch(() => ({}));
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Upload failed. Please try again.');
        }
        return result;
    }

    async upload(file, preview) {
        const mimeType = mimeForFile(file);
        const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
        const init = await this.request(routes.initiate, {
            method: 'POST',
            headers: this.headers(true),
            body: JSON.stringify({
                filename: file.name,
                file_size: file.size,
                mime_type: mimeType,
                total_chunks: totalChunks,
                chunk_size: CHUNK_SIZE,
            }),
        });

        let completed = 0;
        const workers = Array.from({ length: Math.min(CHUNK_CONCURRENCY, totalChunks) }, async (_, worker) => {
            for (let index = worker; index < totalChunks; index += CHUNK_CONCURRENCY) {
                const data = new FormData();
                data.append('upload_id', init.upload_id);
                data.append('chunk_index', index);
                data.append('chunk', file.slice(index * CHUNK_SIZE, Math.min((index + 1) * CHUNK_SIZE, file.size)), `${file.name}.part`);
                await this.request(routes.chunk, { method: 'POST', headers: this.headers(), body: data });
                completed++;
                this.onProgress(preview, completed / totalChunks * 100);
            }
        });

        try {
            await Promise.all(workers);
            let status;
            do {
                status = await this.request(`${routes.status}?upload_id=${encodeURIComponent(init.upload_id)}`, { headers: this.headers() });
                if (status.status === 'failed') throw new Error(status.media?.metadata?.error || 'Upload processing failed.');
                if (status.status !== 'completed') await new Promise(resolve => setTimeout(resolve, 700));
            } while (status.status !== 'completed');

            preview.querySelector('.upload-status').textContent = messages.complete;
            return { mediaId: status.media.id, uploadId: init.upload_id };
        } catch (error) {
            await fetch(routes.cancel, {
                method: 'DELETE',
                headers: this.headers(true),
                body: JSON.stringify({ upload_id: init.upload_id }),
            }).catch(() => {});
            throw error;
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('media');
    const form = input?.closest('form');
    if (!input || !form) return;

    let previews = form.querySelector('#media-previews');
    if (!previews) {
        previews = document.createElement('div');
        previews.id = 'media-previews';
        previews.className = 'mt-3 space-y-2';
        form.appendChild(previews);
    }

    let uploadedIds = form.querySelector('#uploaded-media-ids');
    if (!uploadedIds) {
        uploadedIds = document.createElement('div');
        uploadedIds.id = 'uploaded-media-ids';
        form.appendChild(uploadedIds);
    }

    let uploading = false;
    input.addEventListener('change', async () => {
        const selected = Array.from(input.files || []);
        input.value = '';
        if (!selected.length) return;

        const keptFiles = form.querySelectorAll('input[name="keep_media[]"]:checked').length;
        const currentCount = uploadedIds.querySelectorAll('input[name="media_ids[]"]').length + keptFiles;
        if (currentCount + selected.length > MAX_FILES) {
            alert(messages.maxFiles);
            return;
        }

        input.disabled = true;
        uploading = true;
        const uploader = new ChunkedUploader((preview, progress) => {
            preview.querySelector('.progress-bar').style.width = `${progress}%`;
            preview.querySelector('.upload-status').textContent = messages.uploading.replace(':percent', Math.round(progress));
        });

        for (const file of selected) {
            const preview = makePreview(file);
            previews.appendChild(preview);

            if (!mimeForFile(file)) {
                setFailed(preview, messages.invalidType);
                addRemoveButton(preview);
                continue;
            }
            if (file.size > MAX_FILE_SIZE) {
                setFailed(preview, messages.maxSize);
                addRemoveButton(preview);
                continue;
            }

            try {
                const uploaded = await uploader.upload(file, preview);
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'media_ids[]';
                hidden.value = uploaded.mediaId;
                hidden.dataset.uploadId = uploaded.uploadId;
                uploadedIds.appendChild(hidden);
                addRemoveButton(preview, hidden, uploaded.uploadId);
            } catch (error) {
                setFailed(preview, error.message);
                addRemoveButton(preview);
            }
        }

        uploading = false;
        input.disabled = false;
    });

    form.addEventListener('submit', event => {
        const keptFiles = form.querySelectorAll('input[name="keep_media[]"]:checked').length;
        const newFiles = uploadedIds.querySelectorAll('input[name="media_ids[]"]').length;
        if (uploading || previews.querySelector('[data-failed="true"]')) {
            event.preventDefault();
            alert(uploading ? messages.wait : messages.failed);
        } else if (keptFiles + newFiles > MAX_FILES) {
            event.preventDefault();
            alert(messages.maxFiles);
        }
    });
});

function mimeForFile(file) {
    const allowed = new Set([
        'image/jpeg', 'image/png', 'image/webp', 'image/gif',
        'video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska',
    ]);
    if (allowed.has(file.type)) return file.type;
    const extension = file.name.split('.').pop()?.toLowerCase();
    const fallback = ({
        jpg: 'image/jpeg', jpeg: 'image/jpeg', png: 'image/png', webp: 'image/webp', gif: 'image/gif',
        mp4: 'video/mp4', webm: 'video/webm', mov: 'video/quicktime', avi: 'video/x-msvideo', mkv: 'video/x-matroska',
    })[extension] || '';
    return allowed.has(fallback) ? fallback : '';
}

function makePreview(file) {
    const item = document.createElement('div');
    item.className = 'rounded-lg border border-fb-border p-3 dark:border-zinc-700';
    const name = document.createElement('p');
    name.className = 'text-xs text-fb-muted dark:text-zinc-400';
    name.textContent = `${file.name} (${formatBytes(file.size)})`;
    item.appendChild(name);

    const barWrap = document.createElement('div');
    barWrap.className = 'mt-2 h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700';
    const bar = document.createElement('div');
    bar.className = 'progress-bar h-full bg-fb-blue transition-all';
    bar.style.width = '0%';
    barWrap.appendChild(bar);
    item.appendChild(barWrap);

    const status = document.createElement('p');
    status.className = 'upload-status mt-1 text-xs text-fb-muted';
    status.textContent = messages.waiting;
    item.appendChild(status);
    return item;
}

function setFailed(preview, message) {
    preview.dataset.failed = 'true';
    preview.querySelector('.upload-status').textContent = message;
}

function addRemoveButton(preview, hiddenInput = null, uploadId = null) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'mt-2 text-xs font-medium text-red-600 hover:underline';
    button.textContent = messages.remove;
    button.addEventListener('click', async () => {
        button.disabled = true;
        if (uploadId) {
            const response = await fetch(routes.cancel, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ upload_id: uploadId }),
            }).catch(() => null);
            if (!response?.ok) {
                button.disabled = false;
                return;
            }
        }
        hiddenInput?.remove();
        preview.remove();
    });
    preview.appendChild(button);
}

function formatBytes(bytes) {
    if (!bytes) return '0 Bytes';
    const units = ['Bytes', 'KB', 'MB', 'GB'];
    const unit = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    return `${(bytes / (1024 ** unit)).toFixed(2)} ${units[unit]}`;
}
