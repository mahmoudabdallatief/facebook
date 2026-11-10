const CHUNK_SIZE = 5 * 1024 * 1024;
const MAX_VIDEO_SIZE = 2 * 1024 * 1024 * 1024;
const CHUNK_CONCURRENCY = 3;

const routes = {
    initiate: '/uploads/initiate',
    chunk: '/uploads/chunk',
    status: '/uploads/status',
    cancel: '/uploads/cancel',
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
            throw new Error(result.message || 'تعذر رفع الملف. حاول مرة أخرى.');
        }
        return result;
    }

    async upload(file, preview) {
        let chunkSize = CHUNK_SIZE;
        let totalChunks = Math.ceil(file.size / chunkSize);
        const init = await this.request(routes.initiate, {
            method: 'POST', headers: this.headers(true),
            body: JSON.stringify({
                filename: file.name, file_size: file.size, mime_type: file.type,
                total_chunks: totalChunks, chunk_size: chunkSize,
            }),
        });
        chunkSize = init.chunk_size;
        totalChunks = Math.ceil(file.size / chunkSize);

        let completed = 0;
        const workers = Array.from({ length: Math.min(CHUNK_CONCURRENCY, totalChunks) }, async (_, worker) => {
            for (let index = worker; index < totalChunks; index += CHUNK_CONCURRENCY) {
                const data = new FormData();
                data.append('upload_id', init.upload_id);
                data.append('chunk_index', index);
                data.append('chunk', file.slice(index * chunkSize, Math.min((index + 1) * chunkSize, file.size)), `${file.name}.part`);
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
                if (status.status === 'failed') throw new Error('فشل تجهيز الفيديو بعد رفعه.');
                if (status.status !== 'completed') await new Promise(resolve => setTimeout(resolve, 700));
            } while (status.status !== 'completed');
            preview.dataset.mediaId = status.media.id;
            preview.querySelector('.upload-status').textContent = 'اكتمل رفع الفيديو';
            return status.media.id;
        } catch (error) {
            await fetch(routes.cancel, {
                method: 'DELETE', headers: this.headers(true),
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

    const previews = document.createElement('div');
    previews.id = 'media-previews';
    previews.className = 'mt-3 space-y-2';
    input.parentNode.appendChild(previews);

    const uploadedIds = document.createElement('div');
    uploadedIds.id = 'uploaded-media-ids';
    form.appendChild(uploadedIds);
    let uploading = false;

    input.addEventListener('change', async () => {
        previews.replaceChildren();
        uploadedIds.replaceChildren();
        const selected = Array.from(input.files || []);
        const videos = selected.filter(file => file.type.startsWith('video/'));
        const images = selected.filter(file => file.type.startsWith('image/'));

        // Keep images in the regular post request; video bytes use chunked requests.
        const imageFiles = new DataTransfer();
        images.forEach(file => imageFiles.items.add(file));
        input.files = imageFiles.files;
        uploading = videos.length > 0;

        if (selected.length > 10) {
            showError(previews, 'يمكنك إضافة 10 ملفات كحد أقصى للبوست.');
            input.value = '';
            uploading = false;
            return;
        }

        const uploader = new ChunkedUploader((preview, progress) => {
            preview.querySelector('.progress-bar').style.width = `${progress}%`;
            preview.querySelector('.upload-status').textContent = `جاري الرفع ${Math.round(progress)}%`;
        });

        for (const file of videos) {
            const preview = makePreview(file);
            previews.appendChild(preview);
            if (file.size > MAX_VIDEO_SIZE) {
                preview.querySelector('.upload-status').textContent = 'الحد الأقصى للفيديو 2 جيجابايت';
                preview.dataset.failed = 'true';
                continue;
            }
            try {
                const mediaId = await uploader.upload(file, preview);
                const hidden = document.createElement('input');
                hidden.type = 'hidden'; hidden.name = 'media_ids[]'; hidden.value = mediaId;
                uploadedIds.appendChild(hidden);
            } catch (error) {
                preview.querySelector('.upload-status').textContent = error.message;
                preview.dataset.failed = 'true';
            }
        }
        uploading = false;

        images.forEach(file => {
            const preview = makePreview(file, false);
            previews.appendChild(preview);
        });
    });

    form.addEventListener('submit', event => {
        if (uploading || previews.querySelector('[data-failed="true"]')) {
            event.preventDefault();
            alert(uploading ? 'انتظر حتى يكتمل رفع الفيديو.' : 'أصلح مشكلة رفع الفيديو أو احذفه من الاختيار قبل نشر البوست.');
        }
    });
});

function makePreview(file, progress = true) {
    const item = document.createElement('div');
    item.className = 'rounded-lg border border-fb-border p-3 dark:border-zinc-700';
    const name = document.createElement('p');
    name.className = 'text-xs text-fb-muted dark:text-zinc-400';
    name.textContent = `${file.name} (${formatBytes(file.size)})`;
    item.appendChild(name);
    if (progress) {
        const barWrap = document.createElement('div');
        barWrap.className = 'mt-2 h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700';
        const bar = document.createElement('div');
        bar.className = 'progress-bar h-full bg-fb-blue transition-all';
        bar.style.width = '0%'; barWrap.appendChild(bar); item.appendChild(barWrap);
        const status = document.createElement('p');
        status.className = 'upload-status mt-1 text-xs text-fb-muted';
        status.textContent = 'بانتظار الرفع'; item.appendChild(status);
    }
    return item;
}

function showError(container, message) {
    const item = document.createElement('p');
    item.className = 'text-sm text-red-600'; item.textContent = message;
    container.appendChild(item);
}

function formatBytes(bytes) {
    if (!bytes) return '0 Bytes';
    const units = ['Bytes', 'KB', 'MB', 'GB'];
    const unit = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    return `${(bytes / (1024 ** unit)).toFixed(2)} ${units[unit]}`;
}