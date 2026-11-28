(window.__infiniteLoaders = window.__infiniteLoaders || {});

window.initInfiniteScroll = function initInfiniteScroll(sentinel) {
  const id = sentinel.id;
  const container = document.querySelector(sentinel.dataset.container || '');
  const url = sentinel.dataset.url || '';
  if (!container || !url || window.__infiniteLoaders[id]) return;

  const label = sentinel.dataset.label || 'تحميل المزيد';
  const loadingText = sentinel.dataset.loadingText || 'جاري التحميل...';
  let page = parseInt(sentinel.dataset.nextPage || '2', 10) || 2;
  let hasMore = sentinel.dataset.hasMore === 'true';
  let loading = false;

  const rootEl = sentinel.dataset.root ? document.querySelector(sentinel.dataset.root) : null;
  const btn = sentinel.dataset.loadMoreBtn ? document.querySelector(sentinel.dataset.loadMoreBtn) : null;

  async function loadMore() {
    if (loading || !hasMore) return;
    loading = true;
    if (btn) {
      btn.disabled = true;
      btn.textContent = loadingText;
    }
    try {
      const sep = url.includes('?') ? '&' : '?';
      const response = await fetch(`${url}${sep}page=${page}`, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
      });
      if (!response.ok) throw new Error('Failed to load more');
      const data = await response.json();
      if (data.html) {
        container.insertAdjacentHTML('beforeend', data.html);
      }
      page = data.next_page ?? (hasMore ? page + 1 : null);
      hasMore = data.has_more === true;
      sentinel.dataset.nextPage = page ?? '';
      sentinel.dataset.hasMore = hasMore ? 'true' : 'false';
      if (btn) {
        btn.disabled = false;
        btn.textContent = label;
        btn.style.display = hasMore ? '' : 'none';
      }
      if (!hasMore) {
        observer.unobserve(sentinel);
        sentinel.style.display = 'none';
        btn?.remove();
      }
    } catch (error) {
      console.error('Infinite scroll error:', error);
      if (btn) {
        btn.disabled = false;
        btn.textContent = label;
      }
    } finally {
      loading = false;
    }
  }

  const observer = new IntersectionObserver(
    (entries) => {
      if (!hasMore || loading) return;
      if (entries[0] && entries[0].isIntersecting) loadMore();
    },
    { root: rootEl, rootMargin: '250px' }
  );
  observer.observe(sentinel);

  window.__infiniteLoaders[id] = loadMore;
  sentinel._loadMore = loadMore;
};

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-infinite-sentinel]').forEach((el) => initInfiniteScroll(el));
});
