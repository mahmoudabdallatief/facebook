import './bootstrap';

const root = document.documentElement;
const stored = localStorage.getItem('theme');
const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
const theme = stored || window.__initialTheme || (prefersDark ? 'dark' : 'light');
root.classList.toggle('dark', theme === 'dark');

window.toggleTheme = function toggleTheme() {
  const next = root.classList.contains('dark') ? 'light' : 'dark';
  root.classList.toggle('dark', next === 'dark');
  localStorage.setItem('theme', next);
  document.cookie = `theme=${next};path=/;max-age=31536000;SameSite=Lax`;
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
