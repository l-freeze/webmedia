document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('form').forEach((form) => {
    form.addEventListener('submit', (event) => {
      const submitter = event.submitter ?? form.querySelector('button[type="submit"], button:not([type]), input[type="submit"]');
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

  document.querySelectorAll('[data-auto-dismiss]').forEach((toast) => {
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

  const mediaModal = document.querySelector('[data-media-preview-modal]');
  if (mediaModal) {
    const previewImage = mediaModal.querySelector('[data-media-preview-image]');
    const previewTitle = mediaModal.querySelector('[data-media-preview-title]');
    const previewViewport = mediaModal.querySelector('[data-media-preview-viewport]');
    const closeButton = mediaModal.querySelector('[data-media-preview-close]');
    let lastTrigger = null;

    const setActualSize = (enabled) => {
      mediaModal.classList.toggle('is-actual-size', enabled);
      if (previewImage instanceof HTMLImageElement) {
        previewImage.classList.toggle('max-h-[72vh]', !enabled);
        previewImage.classList.toggle('max-w-full', !enabled);
        previewImage.classList.toggle('object-contain', !enabled);
        previewImage.classList.toggle('cursor-zoom-in', !enabled);
        previewImage.classList.toggle('max-h-none', enabled);
        previewImage.classList.toggle('max-w-none', enabled);
        previewImage.classList.toggle('cursor-zoom-out', enabled);
        previewImage.setAttribute(
          'aria-label',
          enabled ? '画像を元の表示サイズに戻す' : '画像を実寸表示に切り替え'
        );
      }
      if (previewViewport instanceof HTMLElement) {
        previewViewport.scrollTop = 0;
        previewViewport.scrollLeft = 0;
      }
    };

    const closePreview = () => {
      mediaModal.classList.remove('is-open');
      mediaModal.classList.remove('flex');
      mediaModal.classList.add('hidden');
      setActualSize(false);
      mediaModal.setAttribute('hidden', '');
      mediaModal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('has-media-preview');
      if (previewImage instanceof HTMLImageElement) {
        previewImage.removeAttribute('src');
        previewImage.alt = '';
      }
      if (lastTrigger instanceof HTMLElement) {
        lastTrigger.focus();
      }
    };

    document.querySelectorAll('[data-media-preview-src]').forEach((trigger) => {
      trigger.addEventListener('click', () => {
        const src = trigger.getAttribute('data-media-preview-src') || '';
        const title = trigger.getAttribute('data-media-preview-title') || '';
        const alt = trigger.getAttribute('data-media-preview-alt') || title;

        if (previewImage instanceof HTMLImageElement) {
          previewImage.src = src;
          previewImage.alt = alt;
        }
        if (previewTitle) {
          previewTitle.textContent = title;
        }

        lastTrigger = trigger;
        setActualSize(false);
        mediaModal.removeAttribute('hidden');
        mediaModal.setAttribute('aria-hidden', 'false');
        mediaModal.classList.remove('hidden');
        mediaModal.classList.add('flex');
        mediaModal.classList.add('is-open');
        document.body.classList.add('has-media-preview');
        closeButton?.focus();
      });
    });

    const toggleImageSize = () => {
      if (!mediaModal.classList.contains('is-open')) {
        return;
      }

      setActualSize(!mediaModal.classList.contains('is-actual-size'));
    };

    previewImage?.addEventListener('click', (event) => {
      event.stopPropagation();
      toggleImageSize();
    });

    previewImage?.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        toggleImageSize();
      }
    });

    closeButton?.addEventListener('click', closePreview);

    mediaModal.addEventListener('click', (event) => {
      if (event.target === mediaModal) {
        closePreview();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && mediaModal.classList.contains('is-open')) {
        closePreview();
      }
    });
  }
});
