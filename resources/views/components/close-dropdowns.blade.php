{!! '<script>
  document.addEventListener("click", (e) => {
    const dropdown = e.target.closest("[x-data*="dropdown"]");
    if (!dropdown) {
      document.querySelectorAll("[role=menu].active, [x-show*="open"]").forEach(el => {
        const closeEvent = new Event("close");
        el.dispatchEvent(closeEvent);
        if (el._x_dataStack && el._x_dataStack[0]) {
          el._x_dataStack[0].open = false;
        }
      });
    }
  });
</script>' !!}
