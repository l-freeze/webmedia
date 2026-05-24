document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('form').forEach((form) => {
    form.addEventListener('submit', () => {
      const submitter = form.querySelector('button[type="submit"], button:not([type]), input[type="submit"]');
      if (!submitter) {
        return;
      }

      const label = submitter instanceof HTMLInputElement ? submitter.value : submitter.textContent.trim();
      if (!label.includes('保存')) {
        return;
      }

      submitter.classList.add('is-submitting');
      submitter.setAttribute('aria-busy', 'true');
      submitter.disabled = true;
      if (submitter instanceof HTMLInputElement) {
        submitter.value = '保存中...';
      } else {
        submitter.textContent = '保存中...';
      }
    });
  });

  document.querySelectorAll('.toast').forEach((toast) => {
    window.setTimeout(() => {
      toast.classList.add('is-hiding');
    }, 2800);
    window.setTimeout(() => {
      toast.remove();
    }, 3400);
  });

  document.querySelectorAll('[data-confirm]').forEach((element) => {
    element.addEventListener('click', (event) => {
      const message = element.getAttribute('data-confirm') || '実行しますか？';
      if (!window.confirm(message)) {
        event.preventDefault();
      }
    });
  });
});
