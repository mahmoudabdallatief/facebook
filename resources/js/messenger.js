const routes = {
    messageStore: (conversationId) => `/messenger/${conversationId}/messages`,
    messageList: (conversationId) => `/messenger/${conversationId}/messages`,
    upload: '/messenger/upload',
    initiate: '/uploads/initiate',
    chunk: '/uploads/chunk',
    status: (uploadId) => `/uploads/status?upload_id=${encodeURIComponent(uploadId)}`,
    cancel: (uploadId) => `/uploads/cancel`,
    markAsRead: (conversationId) => `/messenger/${conversationId}/read`,
    searchUsers: '/messenger/search',
    unreadCount: '/messenger/unread-count',
    startConversation: '/messenger/start',
    conversationItem: (conversationId) => `/messenger/conversation-item/${conversationId}`,
    typing: (conversationId) => `/messenger/${conversationId}/typing`,
    presence: (conversationId) => `/messenger/${conversationId}/presence`,
    leavePresence: (conversationId) => `/messenger/${conversationId}/presence/leave`,
};

let echo = null;
let conversationChannel = null;
let userChannel = null;
let currentConversationId = null;
let messageForm = null;
let messageInput = null;
let sendBtn = null;
let mediaInput = null;
let mediaPreviews = null;
let uploadedMediaIds = null;
let messagesContainer = null;
let conversationsList = null;
let newChatModal = null;
let newChatSearch = null;
let newChatResults = null;
let conversationSearch = null;
let welcomeScreen = null;
let conversationScreen = null;
let typingIndicator = null;
let uploadingCount = 0;
let typingTimer = null;
let messagesPage = 1;
let hasMoreMessages = true;
let loadingOlder = false;
let presenceTimer = null;

function __(key) {
    return key.split('.').reduce((value, part) => value?.[part], window.MESSENGER_TRANSLATIONS) || key;
}

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

async function apiRequest(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            ...(options.headers || {}),
        },
    });

    const result = await response.json().catch(() => ({}));

    if (!response.ok || result.success === false) {
        throw new Error(result.message || __('messenger.js.request_failed'));
    }

    return result;
}

function initEcho() {
    if (!window.Echo) {
        console.warn('Laravel Echo not loaded. Real-time features disabled.');
        return;
    }

    echo = window.Echo;

    userChannel = echo.private(`user.${window.LARAVEL_USER_ID}`);
    userChannel.listen('.message.sent', (e) => handleNewMessage(e));
    userChannel.listen('.message.read', (e) => handleMessageRead(e));
}

function subscribeToConversation(conversationId) {
    if (!echo || conversationChannel) return;

    conversationChannel = echo.private(`conversation.${conversationId}`);
    conversationChannel.listen('.message.sent', (e) => handleNewMessage(e));
    conversationChannel.listen('.message.read', (e) => handleMessageRead(e));
    conversationChannel.listen('.message.typing', (e) => handleTyping(e));
}

function unsubscribeFromConversation() {
    if (conversationChannel) {
        conversationChannel.unlisten('.message.sent');
        conversationChannel.unlisten('.message.read');
        conversationChannel.unlisten('.message.typing');
        echo.leave(`conversation.${currentConversationId}`);
        conversationChannel = null;
    }
    if (typingIndicator) typingIndicator.classList.add('hidden');
}

function handleNewMessage(e) {
    const isCurrentConversation = currentConversationId !== null
        && Number(e.message.conversation_id) === Number(currentConversationId);

    if (!isCurrentConversation) {
        updateConversationInList(e.message);
        updateUnreadBadge();
        return;
    }

    if (document.getElementById(`message-${e.message.id}`)) return;

    e.message.is_own = Number(e.message.sender_id) === Number(window.LARAVEL_USER_ID);
    appendMessage(e.message);
    scrollToBottom();

    if (!e.message.is_own) {
        apiRequest(routes.markAsRead(currentConversationId), { method: 'POST' })
            .then(() => {
                updateConversationInList(e.message);
                updateUnreadBadge();
            })
            .catch(error => console.error('Could not mark the new message as read:', error));
    }
}

function handleMessageRead(e) {
    const messageEl = document.getElementById(`message-${e.message_id}`);
    if (messageEl) {
        const checkIcons = messageEl.querySelectorAll('svg[data-status="delivered"]');
        checkIcons.forEach(icon => {
            icon.outerHTML = '<svg class="w-4 h-4 text-blue-100" fill="currentColor" viewBox="0 0 24 24" data-status="read"><path d="M18 7l-1.41-1.41-6.34 6.34 1.41 1.41L18 7zm4.24-1.41L11.66 16.17 7.48 12 6.07 13.41 11.66 19l12.66-12.66z"/></svg>';
        });
    }
    updateUnreadBadge();
}

function handleTyping(e) {
    if (e.conversation_id !== currentConversationId || e.user_id == window.LARAVEL_USER_ID) {
        return;
    }

    if (!typingIndicator) return;

    if (e.typing) {
        typingIndicator.querySelector('.typing-name').textContent = e.user_name || '';
        typingIndicator.classList.remove('hidden');
        scrollToBottom();
    } else {
        typingIndicator.classList.add('hidden');
    }
}

function updateUnreadBadge() {
    fetch(routes.unreadCount, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            const badge = document.getElementById('unread-badge');
            if (badge && data.count > 0) {
                badge.textContent = data.count > 9 ? '9+' : data.count;
                badge.classList.remove('hidden');
            } else if (badge) {
                badge.classList.add('hidden');
            }
        })
        .catch(() => {});
}

async function refreshPresence() {
    if (!currentConversationId) return;

    try {
        const presence = await apiRequest(routes.presence(currentConversationId), { method: 'POST' });
        const status = document.getElementById('user-status');
        const dot = document.getElementById('user-presence-dot');
        if (!status || !dot) return;

        if (presence.online) {
            status.textContent = status.dataset.onlineLabel;
            dot.classList.remove('bg-zinc-400');
            dot.classList.add('bg-green-500');
            dot.title = status.dataset.onlineLabel;
        } else {
            const seen = presence.last_seen_at
                ? new Date(presence.last_seen_at).toLocaleString(window.APP_LOCALE)
                : '';
            status.textContent = presence.last_seen_at
                ? status.dataset.lastSeenTemplate.replace(':time', seen)
                : status.dataset.offlineLabel;
            dot.classList.remove('bg-green-500');
            dot.classList.add('bg-zinc-400');
            dot.title = status.textContent;
        }
    } catch (error) {
        console.error('Could not refresh presence:', error);
    }
}

function updateConversationInList(message) {
    const list = document.getElementById('conversations-list');
    if (!list) return;

    fetch(routes.conversationItem(message.conversation_id), {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
    })
    .then(r => {
        if (!r.ok) throw new Error('Could not refresh the conversation list item.');
        return r.text();
    })
    .then(html => {
        const current = list.querySelector(`[data-conversation-id="${message.conversation_id}"]`);
        current?.remove();
        list.insertAdjacentHTML('afterbegin', html);
    })
    .catch(error => console.error(error));
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatTime(isoString) {
    return new Intl.DateTimeFormat(window.APP_LOCALE || 'en', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    }).format(new Date(isoString));
}

function localDateKey(isoString) {
    const date = new Date(isoString);
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

function createDateSeparator(dateKey, isoString) {
    const separator = document.createElement('div');
    separator.className = 'message-day-separator text-center text-xs text-fb-muted py-2';
    separator.dataset.date = dateKey;
    separator.textContent = new Intl.DateTimeFormat(window.APP_LOCALE || 'en', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(isoString));
    return separator;
}

function createMessageElement(message) {
    const isOwn = message.is_own;
    const alignClass = isOwn ? 'justify-end' : 'justify-start';
    const bubbleClass = isOwn ? 'bg-fb-blue text-white rounded-bl-none' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 rounded-br-none';
    const timeClass = isOwn ? 'text-blue-100' : 'text-fb-muted';

    let mediaHtml = '';
    if (message.media && message.media.length > 0) {
        mediaHtml = '<div class="grid gap-2 ' + (message.media.length > 1 ? 'grid-cols-2' : '') + ' mb-2">';
        message.media.forEach(m => {
            if (m.type === 'image') {
                mediaHtml += `<a href="${m.url}" target="_blank" rel="noopener noreferrer" class="block rounded-lg overflow-hidden"><img src="${m.url}" alt="" class="w-full h-auto max-h-64 object-cover"></a>`;
            } else if (m.type === 'video') {
                mediaHtml += `<button type="button" data-video-facade data-video-src="${escapeHtml(m.url)}" data-video-type="${escapeHtml(m.metadata?.mime_type || 'video/mp4')}" class="w-full max-h-64 aspect-video rounded-lg bg-black text-white" aria-label="${escapeHtml(__('messenger.js.play_video'))}">▶</button>`;
            }
        });
        mediaHtml += '</div>';
    }

    const contentHtml = message.content ? `<p class="whitespace-pre-wrap break-words">${escapeHtml(message.content)}</p>` : '';
    
    let readIcon = '';
    if (isOwn) {
        const status = message.read_at ? 'read' : 'delivered';
        const title = __(status === 'read' ? 'messenger.js.read' : 'messenger.js.delivered');
        const path = 'M18 7l-1.41-1.41-6.34 6.34 1.41 1.41L18 7zm4.24-1.41L11.66 16.17 7.48 12 6.07 13.41 11.66 19l12.66-12.66z';
        readIcon = `<svg class="w-4 h-4 ${timeClass}" fill="currentColor" viewBox="0 0 24 24" data-status="${status}" title="${title}"><path d="${path}"/></svg>`;
    }

    const time = formatTime(message.created_at);

    const div = document.createElement('div');
    div.className = `flex ${alignClass}`;
    div.id = `message-${message.id}`;
    div.dataset.messageId = message.id;
    div.innerHTML = `
        <div class="max-w-[70%] ${bubbleClass} rounded-2xl px-4 py-2 shadow-sm">
            ${mediaHtml}
            ${contentHtml}
            <div class="flex items-center justify-end gap-1.5 mt-1.5">
                <time class="text-[10px] ${timeClass}" datetime="${escapeHtml(message.created_at)}">${time}</time>
                ${readIcon}
            </div>
        </div>
    `;

    return div;
}

function appendMessage(message) {
    if (!messagesContainer) return;
    const dateKey = localDateKey(message.created_at);
    const lastMessage = messagesContainer.querySelector('[data-message-id]:last-of-type');
    if (!lastMessage || localDateKey(lastMessage.querySelector('time')?.dateTime || message.created_at) !== dateKey) {
        messagesContainer.appendChild(createDateSeparator(dateKey, message.created_at));
    }
    const element = createMessageElement(message);
    messagesContainer.appendChild(element);
}

function prependMessages(messages) {
    if (!messagesContainer) return;
    messages.reverse().forEach(message => {
        const element = createMessageElement(message);
        messagesContainer.insertBefore(element, messagesContainer.firstChild);
    });
}

function scrollToBottom() {
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
}

async function loadOlderMessages() {
    if (loadingOlder || !hasMoreMessages || !currentConversationId || !messagesContainer) return;

    loadingOlder = true;
    messagesPage++;
    const previousScrollHeight = messagesContainer.scrollHeight;

    try {
        const response = await fetch(routes.messageList(currentConversationId) + `?page=${messagesPage}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        });
        const data = await response.json();

        if (!data.success) throw new Error(data.message);

        hasMoreMessages = messagesPage < data.pagination.last_page;

        const chronologicalMessages = data.messages.reverse();
        const fragment = document.createDocumentFragment();
        const firstExistingMessage = messagesContainer.querySelector('[data-message-id]');
        const firstExistingDate = firstExistingMessage
            ? localDateKey(firstExistingMessage.querySelector('time')?.dateTime)
            : null;
        let previousDate = null;
        chronologicalMessages.forEach(message => {
            const dateKey = localDateKey(message.created_at);
            if (dateKey !== previousDate && !(previousDate === null && dateKey === firstExistingDate)) {
                fragment.appendChild(createDateSeparator(dateKey, message.created_at));
            }
            fragment.appendChild(createMessageElement(message));
            previousDate = dateKey;
        });
        messagesContainer.insertBefore(fragment, messagesContainer.firstChild);
        messagesContainer.scrollTop += messagesContainer.scrollHeight - previousScrollHeight;

        if (!hasMoreMessages) {
            const notice = document.createElement('div');
            notice.className = 'text-center text-[10px] text-fb-muted/60 py-2';
            notice.textContent = __('messenger.no_more_messages');
            messagesContainer.insertBefore(notice, messagesContainer.firstChild);
        }
    } catch (error) {
        console.error('Failed to load older messages:', error);
        messagesPage--;
    } finally {
        loadingOlder = false;
    }
}

function allMediaReady() {
    if (!mediaPreviews) return true;
    const items = mediaPreviews.querySelectorAll('.media-item');
    for (const item of items) {
        if (!item.dataset.mediaId || item.dataset.failed === 'true') return false;
    }
    return uploadingCount === 0 && items.length > 0 || items.length === 0;
}

function readyMediaIds() {
    if (!uploadedMediaIds) return [];
    return Array.from(uploadedMediaIds.querySelectorAll('input')).map(i => i.value);
}

function sendTyping(typing) {
    if (!currentConversationId || !echo) return;

    clearTimeout(typingTimer);

    if (typing) {
        apiRequest(routes.typing(currentConversationId), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ typing: true }),
        }).catch(() => {});
        typingTimer = setTimeout(() => sendTyping(false), 3000);
    } else {
        apiRequest(routes.typing(currentConversationId), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ typing: false }),
        }).catch(() => {});
    }
}

async function sendMessage(event) {
    event.preventDefault();

    const content = messageInput.value.trim();
    const mediaIds = readyMediaIds();

    if (!content && mediaIds.length === 0) return;
    if (!allMediaReady()) {
        alert(__('messenger.js.wait_upload'));
        return;
    }

    sendBtn.disabled = true;
    const originalHtml = sendBtn.innerHTML;
    sendBtn.innerHTML = '<svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

    try {
        const response = await apiRequest(routes.messageStore(currentConversationId), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ content, media_ids: mediaIds }),
        });

        if (!document.getElementById(`message-${response.message.id}`)) {
            appendMessage(response.message);
            scrollToBottom();
        }
        updateConversationInList(response.message);
        updateUnreadBadge();

        messageInput.value = '';
        messageInput.style.height = 'auto';
        mediaPreviews.innerHTML = '';
        uploadedMediaIds.innerHTML = '';
        uploadingCount = 0;
    } catch (error) {
        alert(error.message);
    } finally {
        sendBtn.disabled = false;
        sendBtn.innerHTML = originalHtml;
    }
}

function handleMediaSelect(event) {
    const files = Array.from(event.target.files || []).slice(0, 10);
    if (files.length === 0) return;
    if (event.target.files.length > 10) alert('You can attach up to 10 files.');

    mediaPreviews.innerHTML = '';
    uploadedMediaIds.innerHTML = '';

    const queue = [];
    for (const file of files) {
        const maxSize = file.type.startsWith('video/') ? 2 * 1024 * 1024 * 1024 : 50 * 1024 * 1024;
        const preview = makeMediaPreview(file);
        mediaPreviews.appendChild(preview);

        if (file.size > maxSize) {
            preview.querySelector('.upload-status').textContent = file.type.startsWith('video/')
                ? __('messenger.js.video_max')
                : __('messenger.js.image_max');
            preview.dataset.failed = 'true';
            continue;
        }

        queue.push({ file, preview });
    }
    uploadingCount = queue.length;
    let next = 0;
    const worker = async () => {
        while (next < queue.length) {
            const { file, preview } = queue[next++];
            try { await uploadFile(file, preview); }
            catch (error) {
                preview.querySelector('.upload-status').textContent = error.message;
                preview.dataset.failed = 'true';
            } finally { uploadingCount--; }
        }
    };
    Promise.all(Array.from({ length: Math.min(2, queue.length) }, worker));

    event.target.value = '';
}

async function uploadFile(file, preview) {
    const type = file.type.startsWith('video/') ? 'video' : 'image';
    preview.querySelector('.upload-status').textContent = __('messenger.js.upload_initializing');
    const headers = { 'X-CSRF-TOKEN': getCsrfToken(), 'X-Requested-With': 'XMLHttpRequest' };
    let result;
    if (type === 'video') {
        const chunkSize = 5 * 1024 * 1024;
        result = await apiRequest(routes.initiate, {
            method: 'POST',
            headers: { ...headers, 'Content-Type': 'application/json' },
            body: JSON.stringify({
                filename: file.name,
                file_size: file.size,
                mime_type: file.type,
                total_chunks: Math.ceil(file.size / chunkSize),
                chunk_size: chunkSize,
                target: 'message',
            }),
        });
    } else {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('type', type);
        result = await apiRequest(routes.upload, { method: 'POST', headers, body: formData });
    }

    const { upload_id, media_id, chunk_size } = result;
    const totalChunks = Math.ceil(file.size / chunk_size);

    preview.querySelector('.upload-status').textContent = __('messenger.js.uploading_chunks');

    const concurrency = Math.min(2, totalChunks);
    let completed = 0;
    const workers = Array.from({ length: concurrency }, async (_, worker) => {
        for (let index = worker; index < totalChunks; index += concurrency) {
            const data = new FormData();
            data.append('upload_id', upload_id);
            data.append('chunk_index', index);
            data.append('target', 'message');
            data.append('chunk', file.slice(index * chunk_size, Math.min((index + 1) * chunk_size, file.size)), `${file.name}.part`);
            await apiRequest(routes.chunk, {
                method: 'POST',
                headers,
                body: data,
            });
            completed++;
            const pct = Math.round(completed / totalChunks * 100);
            preview.querySelector('.progress-bar').style.width = `${pct}%`;
            preview.querySelector('.upload-status').textContent = __('messenger.js.upload_progress').replace(':percent', pct);
        }
    });

    await Promise.all(workers);

        let status;
        do {
            const res = await apiRequest(`${routes.status(upload_id)}&target=message`);
            status = res.status;
        if (status === 'failed') {
            throw new Error(__('messenger.js.assembly_failed'));
        }
        if (status !== 'completed') {
            await new Promise(resolve => setTimeout(resolve, 700));
        }
    } while (status !== 'completed');

    preview.dataset.mediaId = media_id;
    preview.querySelector('.progress-bar').style.width = '100%';
    preview.querySelector('.upload-status').textContent = __('messenger.js.upload_complete');

    const hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'media_ids[]';
    hidden.value = media_id;
    uploadedMediaIds.appendChild(hidden);

    return media_id;
}

function makeMediaPreview(file) {
    const item = document.createElement('div');
    item.className = 'media-item relative rounded-lg border border-fb-border p-2 dark:border-zinc-700 w-24 h-24 flex-shrink-0';

    const isVideo = file.type.startsWith('video/');
    const mediaEl = isVideo ? document.createElement('video') : document.createElement('img');
    mediaEl.className = 'w-full h-20 object-cover rounded';
    mediaEl.src = URL.createObjectURL(file);
    if (isVideo) mediaEl.muted = true;

    const barWrap = document.createElement('div');
    barWrap.className = 'absolute bottom-0 left-0 right-0 h-1.5 bg-zinc-200 dark:bg-zinc-700';
    const bar = document.createElement('div');
    bar.className = 'progress-bar h-full bg-fb-blue transition-all';
    bar.style.width = '0%';
    barWrap.appendChild(bar);

    const status = document.createElement('div');
    status.className = 'upload-status absolute inset-0 flex items-center justify-center text-xs text-white bg-black/50 rounded-lg';
    status.textContent = __('messenger.js.upload_waiting');

    item.appendChild(mediaEl);
    item.appendChild(barWrap);
    item.appendChild(status);

    return item;
}

async function loadConversation(conversationId) {
    if (currentConversationId === conversationId) return;

    currentConversationId = conversationId;
    unsubscribeFromConversation();
    subscribeToConversation(conversationId);

    messagesPage = 1;
    hasMoreMessages = true;
    loadingOlder = false;

    welcomeScreen?.classList.add('hidden');
    conversationScreen?.classList.remove('hidden');

    try {
        const response = await fetch(routes.messageList(conversationId), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        });
        const data = await response.json();

        if (!data.success) throw new Error(data.message);

        messagesContainer.innerHTML = '';
        data.messages.reverse().forEach(message => {
            appendMessage(message);
        });

        hasMoreMessages = messagesPage < data.pagination.last_page;

        history.pushState({ conversationId }, '', `/messenger/${conversationId}`);
        initConversationUI();
        scrollToBottom();
    } catch (error) {
        console.error('Failed to load conversation:', error);
        alert(__('messenger.js.load_failed') + ' ' + error.message);
    }
}

function initConversationUI() {
    messageForm = document.getElementById('message-form');
    messageInput = document.getElementById('message-input');
    sendBtn = document.getElementById('send-btn');
    mediaInput = document.getElementById('media-input');
    mediaPreviews = document.getElementById('media-previews');
    uploadedMediaIds = document.getElementById('uploaded-media-ids');
    messagesContainer = document.getElementById('messages-container');
    typingIndicator = document.getElementById('typing-indicator');

    if (messageForm) messageForm.addEventListener('submit', sendMessage);
    if (mediaInput) mediaInput.addEventListener('change', handleMediaSelect);
    if (messageInput) {
        messageInput.addEventListener('input', () => {
            messageInput.style.height = 'auto';
            messageInput.style.height = Math.min(messageInput.scrollHeight, 120) + 'px';
            sendTyping(true);
        });
        messageInput.addEventListener('blur', () => sendTyping(false));
    }

    scrollToBottom();

    if (messagesContainer) {
        messagesContainer.addEventListener('scroll', () => {
            if (messagesContainer.scrollTop <= 60 && !loadingOlder) {
                loadOlderMessages();
            }
        });
    }
}

function goBackToList() {
    if (window.innerWidth >= 1024) return;

    leaveConversationPresence(currentConversationId);
    welcomeScreen?.classList.remove('hidden');
    conversationScreen?.classList.add('hidden');
    currentConversationId = null;
    if (presenceTimer) clearInterval(presenceTimer);
    presenceTimer = null;
    unsubscribeFromConversation();
    history.pushState({}, '', '/messenger');
}

function leaveConversationPresence(conversationId) {
    if (!conversationId) return;

    apiRequest(routes.leavePresence(conversationId), {
        method: 'POST',
        keepalive: true,
    }).catch(error => console.error('Could not clear conversation presence:', error));
}

function openNewChatModal() {
    newChatModal?.classList.remove('hidden');
    newChatModal?.classList.add('flex');
    newChatSearch?.focus();
    searchUsers('');
}

function closeNewChatModal() {
    if (newChatModal) {
        newChatModal.classList.add('hidden');
        newChatModal.classList.remove('flex');
    }
    if (newChatSearch) newChatSearch.value = '';
    if (newChatResults) newChatResults.innerHTML = '';
}

async function searchUsers(query) {
    if (!newChatResults) return;

    if (!query || query.length < 2) {
        newChatResults.innerHTML = '';
        return;
    }

    try {
        const response = await fetch(`${routes.searchUsers}?q=${encodeURIComponent(query)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await response.json();

        newChatResults.innerHTML = '';
        data.users.forEach(user => {
            const div = document.createElement('div');
            div.className = 'flex items-center gap-3 p-2 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 cursor-pointer';
            div.onclick = () => startConversation(user.id);
            div.innerHTML = `
                <div class="w-10 h-10 rounded-full ${user.avatar ? 'bg-cover bg-center' : 'bg-fb-blue flex items-center justify-center text-white font-semibold'}" style="${user.avatar ? `background-image: url(${user.avatar})` : ''}">
                    ${!user.avatar ? user.name.charAt(0) : ''}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium truncate">${escapeHtml(user.name)}</p>
                    <p class="text-xs text-fb-muted truncate">${escapeHtml(user.email)}</p>
                </div>
            `;
            newChatResults.appendChild(div);
        });
    } catch (error) {
        console.error('Search failed:', error);
    }
}

async function startConversation(userId) {
    try {
        const response = await apiRequest(routes.startConversation, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId }),
        });
        if (response.success) {
            closeNewChatModal();
            window.location.assign(response.redirect);
        }
    } catch (error) {
        console.error('Failed to start conversation:', error);
        alert(__('messenger.js.start_failed') + ' ' + error.message);
    }
}

async function loadMoreConversations(event) {
    const btn = event?.currentTarget;
    if (!btn) return;
    const page = parseInt(btn.dataset.page || '2');
    btn.disabled = true;
    const originalLabel = btn.textContent;
    btn.textContent = __('messenger.js.loading');

    try {
        const response = await fetch(`/messenger?page=${page}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
        });
        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newItems = doc.querySelectorAll('[data-conversation-id]');
        newItems.forEach(item => conversationsList?.appendChild(item));

        const newBtn = doc.querySelector('[onclick="loadMoreConversations(event)"]');
        if (newBtn) {
            btn.dataset.page = page + 1;
            btn.disabled = false;
            btn.textContent = newBtn.textContent;
        } else {
            btn.remove();
        }
    } catch (error) {
        console.error('Failed to load more:', error);
        btn.disabled = false;
        btn.textContent = originalLabel;
    }
}

function setupConversationSearch() {
    if (!conversationSearch) return;

    let debounceTimer;
    conversationSearch.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const query = e.target.value.toLowerCase();
            document.querySelectorAll('#conversations-list [data-conversation-id]').forEach(item => {
                const name = item.querySelector('h4')?.textContent?.toLowerCase() || '';
                const lastMsg = item.querySelector('p.text-sm')?.textContent?.toLowerCase() || '';
                item.style.display = (name.includes(query) || lastMsg.includes(query)) ? 'flex' : 'none';
            });
        }, 200);
    });
}

function setupNewChatSearch() {
    if (!newChatSearch) return;

    let debounceTimer;
    newChatSearch.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => searchUsers(e.target.value), 300);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    conversationsList = document.getElementById('conversations-list');
    welcomeScreen = document.getElementById('welcome-screen');
    conversationScreen = document.getElementById('conversation-screen');
    newChatModal = document.getElementById('new-chat-modal');
    newChatSearch = document.getElementById('new-chat-search');
    newChatResults = document.getElementById('new-chat-results');
    conversationSearch = document.getElementById('conversation-search');

    const pathParts = window.location.pathname.split('/');
    if (pathParts.includes('messenger') && pathParts.length > 2) {
        const convId = parseInt(pathParts[pathParts.length - 1]);
        if (!isNaN(convId)) {
            currentConversationId = convId;
            welcomeScreen?.classList.add('hidden');
            conversationScreen?.classList.remove('hidden');
        }
    }

    initEcho();

    if (currentConversationId) {
        subscribeToConversation(currentConversationId);
        initConversationUI();
        refreshPresence();
        presenceTimer = setInterval(refreshPresence, 10000);
    }

    window.addEventListener('pagehide', () => leaveConversationPresence(currentConversationId));

    updateUnreadBadge();
    setInterval(updateUnreadBadge, 30000);

    setupConversationSearch();
    setupNewChatSearch();

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeNewChatModal();
            if (window.innerWidth < 1024 && currentConversationId) {
                goBackToList();
            }
        }
    });

    window.addEventListener('popstate', (e) => {
        if (e.state?.conversationId) {
            loadConversation(e.state.conversationId);
        } else if (currentConversationId) {
            goBackToList();
        }
    });
});

window.loadConversation = loadConversation;
window.goBackToList = goBackToList;
window.openNewChatModal = openNewChatModal;
window.closeNewChatModal = closeNewChatModal;
window.loadMoreConversations = loadMoreConversations;
