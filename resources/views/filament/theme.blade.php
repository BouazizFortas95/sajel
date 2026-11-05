@vite('resources/css/filament.css')
<style>
  html.dark, .dark { color-scheme: dark !important; }
  .dark select, .dark select:focus { background-color: #111827 !important; color: #f9fafb !important; border-color: #374151 !important; }
  .dark select option, .dark select optgroup { background-color: #111827 !important; color: #f9fafb !important; }
  .dark input[type="text"], .dark input[type="search"], .dark input[type="number"], .dark input[type="date"], .dark textarea { background-color: #111827 !important; color: #f9fafb !important; border-color: #374151 !important; }
  .dark .bg-white:not([class*="dark:bg-white"]) { background-color: #1f2937 !important; }
  .dark .bg-gray-50:not([class*="dark:bg-white"]), .dark .bg-gray-50\/50, .dark .bg-gray-50\/60, .dark .bg-gray-50\/70, .dark .bg-gray-50\/80 { background-color: #111827 !important; }
  .dark .bg-gray-100:not([class*="dark:bg-white"]), .dark .bg-gray-100\/80 { background-color: #111827 !important; }
  .dark table:not(.fi-ta-table) thead th { background-color: #111827 !important; color: #e5e7eb !important; border-color: #374151 !important; }
  .dark table:not(.fi-ta-table) tfoot tr, .dark table:not(.fi-ta-table) tfoot td { background-color: #111827 !important; color: #f9fafb !important; border-color: #374151 !important; }
  .dark .bg-primary-100, .dark .bg-primary-200, .dark .bg-amber-100 { background-color: rgba(245, 158, 11, 0.22) !important; color: #fef3c7 !important; }
  .dark .bg-amber-50, .dark .bg-primary-50 { background-color: rgba(245, 158, 11, 0.15) !important; color: #fef3c7 !important; }
  .dark .text-amber-950, .dark .text-amber-900, .dark .text-amber-800 { color: #fef3c7 !important; }
  .dark .text-gray-500:not([class*="dark:text-"]) { color: #9ca3af !important; }
  .dark .text-gray-600:not([class*="dark:text-"]), .dark .text-gray-700:not([class*="dark:text-"]) { color: #d1d5db !important; }
  .dark .text-gray-800:not([class*="dark:text-"]), .dark .text-gray-900:not([class*="dark:text-"]) { color: #f9fafb !important; }

  /* Tailwind v4 CSS variable mapping for Filament grid & column layout */
  .col-\[--col-span-default\] { grid-column: var(--col-span-default) !important; }
  .sm\:col-\[--col-span-sm\] { grid-column: var(--col-span-sm) !important; }
  .md\:col-\[--col-span-md\] { grid-column: var(--col-span-md) !important; }
  .lg\:col-\[--col-span-lg\] { grid-column: var(--col-span-lg) !important; }
  .xl\:col-\[--col-span-xl\] { grid-column: var(--col-span-xl) !important; }
  .\32xl\:col-\[--col-span-2xl\] { grid-column: var(--col-span-2xl) !important; }

  .col-start-\[--col-start-default\] { grid-column-start: var(--col-start-default) !important; }
  .sm\:col-start-\[--col-start-sm\] { grid-column-start: var(--col-start-sm) !important; }
  .md\:col-start-\[--col-start-md\] { grid-column-start: var(--col-start-md) !important; }
  .lg\:col-start-\[--col-start-lg\] { grid-column-start: var(--col-start-lg) !important; }
  .xl\:col-start-\[--col-start-xl\] { grid-column-start: var(--col-start-xl) !important; }
  .\32xl\:col-start-\[--col-start-2xl\] { grid-column-start: var(--col-start-2xl) !important; }

  .grid-cols-\[--cols-default\] { grid-template-columns: var(--cols-default) !important; }
  .sm\:grid-cols-\[--cols-sm\] { grid-template-columns: var(--cols-sm) !important; }
  .md\:grid-cols-\[--cols-md\] { grid-template-columns: var(--cols-md) !important; }
  .lg\:grid-cols-\[--cols-lg\] { grid-template-columns: var(--cols-lg) !important; }
  .xl\:grid-cols-\[--cols-xl\] { grid-template-columns: var(--cols-xl) !important; }
  .\32xl\:grid-cols-\[--cols-2xl\] { grid-template-columns: var(--cols-2xl) !important; }

  .columns-\[--cols-default\] { columns: var(--cols-default) !important; }
  .sm\:columns-\[--cols-sm\] { columns: var(--cols-sm) !important; }
  .md\:columns-\[--cols-md\] { columns: var(--cols-md) !important; }
  .lg\:columns-\[--cols-lg\] { columns: var(--cols-lg) !important; }
  .xl\:columns-\[--cols-xl\] { columns: var(--cols-xl) !important; }
  .\32xl\:columns-\[--cols-2xl\] { columns: var(--cols-2xl) !important; }
  .fi-dropdown-menu, [role="menu"].open, .dropdown-menu.open { display: none !important; }
  /* Full-screen Data Show hides Filament shell */
  body.fi-data-show-mode .fi-header, body.fi-data-show-mode .fi-sidebar, body.fi-data-show-mode .fi-topbar { display: none !important; }
  body.fi-data-show-mode .fi-main { padding: 0 !important; margin: 0 !important; }
</style>
<script>
  (function(){
    setTimeout(function(){
      if(window.location.pathname.includes('data-show')) document.body.classList.add('fi-data-show-mode');
      document.querySelectorAll('.fi-dropdown-menu, [role="menu"], .dropdown-menu').forEach(function(el){ el.style.display='none'; el.removeAttribute('open'); if(el._x_dataStack) el._x_dataStack[0].open=false; });
    }, 100);
  })();
</script>
<script>
  /*
   * Filament keeps the sidebar open by default. Below `lg` it renders as an
   * overlay that covers the page until dismissed, so close it before Alpine
   * initialises the layout. Desktop keeps Filament's persisted behaviour.
   */
  (function () {
    function closeMobileSidebar() {
      if (!window.matchMedia('(min-width: 1024px)').matches) {
        window.Alpine.store('sidebar').isOpen = false;
      }
    }

    if (window.Alpine && window.Alpine.store('sidebar')) {
      closeMobileSidebar();
    } else {
      document.addEventListener('DOMContentLoaded', function () {
        document.addEventListener('alpine:init', function () {
          closeMobileSidebar();
        });
      });
    }
  })();
</script>
@include('components.close-dropdowns')
