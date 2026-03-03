import './bootstrap';

// import bundle com Popper e exporta classes Bootstrap
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

// Carrega Fabric.js apenas nas telas que têm o canvas de certificado
const loadFabricIfNeeded = () => {
  const hasCanvas =
    document.getElementById('canvas-frente') ||
    document.getElementById('canvas-verso');

  if (!hasCanvas || window.fabric) return;

  import('fabric').then((mod) => {
    const fabric = mod.fabric || mod.default || mod;
    window.fabric = fabric;
    document.dispatchEvent(new Event('fabric:ready'));
  });
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', loadFabricIfNeeded, { once: true });
} else {
  loadFabricIfNeeded();
}

let confirmModalInstance;
let confirmMessageEl;
let confirmAcceptBtn;
let pendingForm = null;

const submitWithConfirmation = () => {
  if (!pendingForm) {
    return;
  }

  pendingForm.dataset.confirmed = 'true';

  const formToSubmit = pendingForm;
  pendingForm = null;

  if (typeof formToSubmit.requestSubmit === 'function') {
    formToSubmit.requestSubmit();
  } else {
    formToSubmit.submit();
  }
};

const ensureModalSetup = () => {
  if (confirmModalInstance || !bootstrap?.Modal) {
    return;
  }

  const modalEl = document.getElementById('confirmModal');
  if (!modalEl) {
    return;
  }

  confirmModalInstance = new bootstrap.Modal(modalEl);
  confirmMessageEl = modalEl.querySelector('.js-confirm-message');
  confirmAcceptBtn = modalEl.querySelector('.js-confirm-accept');

  confirmAcceptBtn?.addEventListener('click', () => {
    confirmModalInstance?.hide();
    submitWithConfirmation();
  });
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', ensureModalSetup, { once: true });
} else {
  ensureModalSetup();
}

document.addEventListener('submit', (event) => {
  const form = event.target;

  if (!(form instanceof HTMLFormElement)) {
    return;
  }

  const confirmMessage = form.dataset.confirm;

  if (!confirmMessage) {
    return;
  }

  if (form.dataset.confirmed === 'true') {
    delete form.dataset.confirmed;
    return;
  }

  event.preventDefault();
  pendingForm = form;

  ensureModalSetup();

  if (confirmMessageEl) {
    confirmMessageEl.textContent = confirmMessage;
  }

  if (confirmModalInstance) {
    confirmModalInstance.show();
  } else if (window.confirm(confirmMessage)) {
    submitWithConfirmation();
  } else {
    pendingForm = null;
  }
});
