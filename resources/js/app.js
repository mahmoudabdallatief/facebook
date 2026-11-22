import './bootstrap';

const root = document.documentElement;
const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
const theme = window.__initialTheme || localStorage.getItem('theme') || (prefersDark ? 'dark' : 'light');
root.classList.toggle('dark', theme === 'dark');
localStorage.setItem('theme', theme);

window.toggleTheme = async function toggleTheme() {
  const next = root.classList.contains('dark') ? 'light' : 'dark';
  root.classList.toggle('dark', next === 'dark');
  localStorage.setItem('theme', next);
  document.cookie = `theme=${next};path=/;max-age=31536000;SameSite=Lax`;

  try {
    await fetch('/theme', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
      },
      body: `theme=${encodeURIComponent(next)}`,
    });
  } catch (_) {
    // 忽略网络错误، السمة محفوظة محلياً بالفعل
  }
};

window.previewAvatar = function previewAvatar(input) {
  const preview = document.getElementById('avatar-preview');
  if (!preview || !input.files || !input.files[0]) return;
  const reader = new FileReader();
  reader.onload = (e) => {
    preview.src = e.target.result;
  };
  reader.readAsDataURL(input.files[0]);
};

window.toggleCommentForm = function toggleCommentForm(postId) {
  const wrapper = document.getElementById('comment-form-wrapper-' + postId);
  if (wrapper) wrapper.classList.toggle('hidden');
};

window.openShareModal = function openShareModal(postId) {
  document.getElementById('share-modal-' + postId)?.classList.remove('hidden');
};

window.closeShareModal = function closeShareModal(postId) {
  document.getElementById('share-modal-' + postId)?.classList.add('hidden');
};

window.toggleMenu = function toggleMenu(id) {
  document.getElementById(id)?.classList.toggle('hidden');
};

window.openDeleteModal = function openDeleteModal(postId) {
  document.getElementById('delete-modal-' + postId)?.classList.remove('hidden');
  document.getElementById('post-' + postId + '-menu')?.classList.add('hidden');
};

window.closeDeleteModal = function closeDeleteModal(postId) {
  document.getElementById('delete-modal-' + postId)?.classList.add('hidden');
};

window.submitCommentForm = async function submitCommentForm(event, postId) {
  event.preventDefault();
  const form = event.target.closest('form');
  const textarea = form?.querySelector('textarea[name="body"]');
  const body = textarea?.value.trim();
  if (!form || !body) return;

  const button = form.querySelector('button[type="submit"]');
  const originalLabel = button?.textContent;
  if (button) {
    button.disabled = true;
    button.textContent = '…';
  }

  try {
    const response = await fetch(form.action, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ post_id: postId, body }),
    });

    if (!response.ok) {
      const error = await response.json().catch(() => ({}));
      throw new Error(error.message || 'Unable to add comment.');
    }

    window.location.reload();
  } catch (error) {
    if (button) {
      button.disabled = false;
      button.textContent = originalLabel;
    }
    if (typeof window.showToast === 'function') window.showToast(error.message, 'error');
    else console.error(error);
  }
};

window.toggleReplyForm = function toggleReplyForm(commentId) {
  const form = document.getElementById('reply-form-' + commentId);
  if (form) form.classList.toggle('hidden');
};

window.submitReplyForm = async function submitReplyForm(event, commentId, postId) {
  event.preventDefault();
  const form = event.target.closest('form');
  const textarea = form?.querySelector('textarea[name="body"]');
  const body = textarea?.value.trim();
  if (!form || !body) return;

  const button = form.querySelector('button[type="submit"]');
  const originalLabel = button?.textContent;
  if (button) {
    button.disabled = true;
    button.textContent = '…';
  }

  try {
    const response = await fetch(form.action, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ post_id: postId, parent_id: commentId, body }),
    });

    if (!response.ok) {
      const error = await response.json().catch(() => ({}));
      throw new Error(error.message || 'Unable to add reply.');
    }

    window.location.reload();
  } catch (error) {
    if (button) {
      button.disabled = false;
      button.textContent = originalLabel;
    }
    if (typeof window.showToast === 'function') window.showToast(error.message, 'error');
    else console.error(error);
  }
};

// Reaction controls are rendered with inline onclick handlers in Blade views.
// Keep this action on window so it is available to those handlers on every page.
window.toggleReaction = async function toggleReaction(url, reactionType) {
  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        'Accept': 'application/json',
      },
      body: 'reaction_type=' + encodeURIComponent(reactionType),
    });

    if (response.ok) window.location.reload();
  } catch (error) {
    console.error('Unable to save reaction:', error);
  }
};
