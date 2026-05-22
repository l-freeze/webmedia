document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-confirm]').forEach((element) => {
    element.addEventListener('click', (event) => {
      const message = element.getAttribute('data-confirm') || '実行しますか？';
      if (!window.confirm(message)) {
        event.preventDefault();
      }
    });
  });
});
