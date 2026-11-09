@props(['id', 'title', 'size' => 'md'])

<div id="{{ $id }}" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title" style="display: none;">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50 transition-opacity" aria-hidden="true" onclick="closeModal('{{ $id }}')"></div>
        
        <div class="relative w-full max-w-{{ $size === 'sm' ? 'md' : ($size === 'lg' ? '2xl' : 'xl') }} transform overflow-hidden rounded-xl bg-fb-surface text-fb-text dark:bg-zinc-900 dark:text-zinc-100 shadow-xl transition-all">
            <div class="flex items-center justify-between border-b border-fb-border p-4 dark:border-zinc-800">
                <h2 id="{{ $id }}-title" class="text-lg font-semibold">{{ $title }}</h2>
                <button type="button" onclick="closeModal('{{ $id }}')" class="rounded-lg p-1 text-fb-muted hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="Close">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-4">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>

<script>
    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    };
    
    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    };
    
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('[id$="-modal"]').forEach(modal => {
                if (!modal.classList.contains('hidden')) {
                    closeModal(modal.id);
                }
            });
        }
    });
    
    // Ensure modal is hidden on page load
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('{{ $id }}');
        if (modal) {
            modal.classList.add('hidden');
            modal.style.display = 'none';
        }
    });
</script>