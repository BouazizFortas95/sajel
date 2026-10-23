@vite('resources/css/filament.css')
<style>
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
@include('components.close-dropdowns')
