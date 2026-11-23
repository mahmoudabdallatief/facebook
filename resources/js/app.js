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

window.editComment = function editComment(commentId) {
  document.getElementById('comment-' + commentId)?.classList.add('hidden');
  document.getElementById('edit-form-' + commentId)?.classList.remove('hidden');
};

window.cancelEditComment = function cancelEditComment(commentId) {
  document.getElementById('comment-' + commentId)?.classList.remove('hidden');
  document.getElementById('edit-form-' + commentId)?.classList.add('hidden');
};

window.submitEditComment = async function submitEditComment(event, commentId, updateUrl) {
  event.preventDefault();
  const form = event.target.closest('form');
  updateUrl = updateUrl || form?.action;
  if (!updateUrl) {
    console.error('Unable to update comment: missing update URL.');
    return;
  }
  const body = form.querySelector('textarea[name="body"]').value.trim();
  if (!body) return;

  const btn = form.querySelector('button[type="submit"]');
  const originalLabel = btn?.textContent;
  btn.disabled = true;
  btn.textContent = '…';

  try {
    const response = await fetch(updateUrl, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ body }),
    });

    if (!response.ok) {
      const error = await response.json().catch(() => ({}));
      throw new Error(error.message || 'Unable to update comment.');
    }

    window.location.reload();
  } catch (error) {
    if (btn) {
      btn.disabled = false;
      btn.textContent = originalLabel;
    }
    if (typeof window.showToast === 'function') window.showToast(error.message, 'error');
    else console.error(error);
  }
};

window.deleteComment = function deleteComment(commentId, postId) {
  document.getElementById('delete-comment-modal-' + commentId)?.classList.remove('hidden');
  window.currentDeleteComment = { id: commentId, postId: postId };
};

window.cancelDeleteComment = function cancelDeleteComment(commentId) {
  document.getElementById('delete-comment-modal-' + commentId)?.classList.add('hidden');
};

window.confirmDeleteComment = function confirmDeleteComment(commentId, postId) {
  const modal = document.getElementById('delete-comment-modal-' + commentId);
  const deleteUrl = modal?.dataset.deleteUrl;

  if (!deleteUrl) {
    showToast('Unable to delete comment.', 'error');
    return;
  }

  fetch(deleteUrl, {
    method: 'DELETE',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
      'Accept': 'application/json',
    },
  }).then(async (res) => {
    if (!res.ok) {
      const error = await res.json().catch(() => ({}));
      throw new Error(error.message || 'Unable to delete comment.');
    }
    const result = await res.json();

    const el = document.getElementById('comment-' + commentId);
    if (el) el.remove();
    cancelDeleteComment(commentId);

    // Use the outer list, which carries the translated empty state. Group posts
    // also have an inner comments-container, so preferring it loses that text.
    const list = document.getElementById('comments-list-' + postId)
      || document.getElementById('comments-container-' + postId);
    showToast(list?.dataset.deletedMessage || 'Comment deleted', 'success');
    if (list && result.remaining_comments === 0) {
      const emptyMessage = document.createElement('p');
      emptyMessage.className = 'text-xs text-center text-fb-muted dark:text-zinc-500 py-4';
      emptyMessage.textContent = list.dataset.emptyMessage || '';
      list.replaceChildren(emptyMessage);
    }
  }).catch(err => {
    showToast(err.message || 'Something went wrong', 'error');
    cancelDeleteComment(commentId);
  });
};

window.showToast = function showToast(message, type = 'error') {
  const toast = document.createElement('div');
  const bgColor = type === 'success' ? 'bg-green-500' : (type === 'error' ? 'bg-red-500' : 'bg-blue-500');
  toast.className = `fixed bottom-4 right-4 ${bgColor} text-white px-4 py-2 rounded-lg shadow-lg z-50`;
  toast.textContent = message;
  document.body.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transition = 'opacity 0.3s';
    setTimeout(() => toast.remove(), 300);
  }, 3000);
};
