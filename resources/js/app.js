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
