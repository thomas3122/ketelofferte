/* ============================================================
   Ketelcheck — fullscreen wizard
   ============================================================ */
const overlay = document.getElementById('ketelcheckOverlay');
const form = document.getElementById('ketelForm');
const body = document.getElementById('ketelcheckBody');
const steps = Array.from(document.querySelectorAll('.ko-cc__step'));
const nextBtn = document.getElementById('nextBtn');
const prevBtn = document.getElementById('prevBtn');
const errorMessage = document.getElementById('errorMessage');
const progressBar = document.getElementById('progressBar');
const progressLabel = document.getElementById('progressLabel');
const progressPercent = document.getElementById('progressPercent');

let currentStep = 1;
const totalSteps = steps.length;
let lastTrigger = null;
let advanceTimer = null;

/* ---- Lucide icons ---- */
function renderIcons() {
  if (window.lucide && typeof window.lucide.createIcons === 'function') {
    window.lucide.createIcons();
  }
}

/* ---- Open / close overlay ---- */
function openOverlay(trigger) {
  if (!overlay) return;

  lastTrigger = trigger || null;
  overlay.hidden = false;
  overlay.setAttribute('aria-hidden', 'false');
  document.body.classList.add('ko-cc-open');
  renderIcons();
  updateStep();

  const focusTarget = getCurrentStepElement()?.querySelector('.ko-opt, .ko-input') || prevBtn;
  if (focusTarget) {
    window.setTimeout(() => focusTarget.focus({ preventScroll: true }), 40);
  }
}

function closeOverlay() {
  if (!overlay) return;

  window.clearTimeout(advanceTimer);
  overlay.hidden = true;
  overlay.setAttribute('aria-hidden', 'true');
  document.body.classList.remove('ko-cc-open');

  if (lastTrigger && typeof lastTrigger.focus === 'function') {
    lastTrigger.focus({ preventScroll: true });
  }
}

/* Any "start" CTA opens the wizard; keep #ketelcheck anchors working too. */
document.querySelectorAll('[data-ketelcheck-open], a[href="#ketelcheck"], a[href$="#ketelcheck"]').forEach((el) => {
  el.addEventListener('click', (event) => {
    if (!overlay) return;
    event.preventDefault();
    openOverlay(el);
  });
});

document.querySelectorAll('[data-ketelcheck-close]').forEach((el) => {
  el.addEventListener('click', closeOverlay);
});

/* ---- Option selection (single-choice + auto advance) ---- */
document.querySelectorAll('[data-option-group]').forEach((group) => {
  const groupName = group.dataset.optionGroup;
  const buttons = Array.from(group.querySelectorAll('.ko-opt'));
  const hiddenInput = document.getElementById(`input_${groupName}`);

  buttons.forEach((button) => {
    button.addEventListener('click', () => {
      buttons.forEach((btn) => btn.setAttribute('aria-pressed', 'false'));
      button.setAttribute('aria-pressed', 'true');

      if (hiddenInput) {
        hiddenInput.value = button.dataset.value;
      }

      errorMessage.textContent = '';

      window.clearTimeout(advanceTimer);
      if (currentStep < totalSteps) {
        advanceTimer = window.setTimeout(() => {
          currentStep += 1;
          updateStep();
        }, 300);
      }
    });
  });
});

/* ---- Photo file list ---- */
const photoInput = document.getElementById('photos');
const photoList = document.getElementById('photoList');

if (photoInput && photoList) {
  photoInput.addEventListener('change', () => {
    photoList.innerHTML = '';
    Array.from(photoInput.files).forEach((file) => {
      const row = document.createElement('div');
      row.className = 'ko-cc__file';
      row.innerHTML = '<i data-lucide="image"></i><span></span>';
      row.querySelector('span').textContent = file.name;
      photoList.appendChild(row);
    });
    renderIcons();
  });
}

/* ---- Step navigation + progress ---- */
function getCurrentStepElement() {
  return steps.find((step) => Number(step.dataset.step) === currentStep);
}

function updateStep() {
  steps.forEach((step) => {
    step.classList.toggle('active', Number(step.dataset.step) === currentStep);
  });

  const percentage = Math.round((currentStep / totalSteps) * 100);

  if (progressBar) progressBar.style.width = `${percentage}%`;
  if (progressLabel) progressLabel.textContent = `Stap ${currentStep} van ${totalSteps}`;
  if (progressPercent) progressPercent.textContent = `${percentage}%`;

  const prevLabel = prevBtn.querySelector('span');
  if (prevLabel) prevLabel.textContent = currentStep === 1 ? 'Sluiten' : 'Vorige';

  const nextLabel = nextBtn.querySelector('span');
  if (nextLabel) nextLabel.textContent = currentStep === totalSteps ? 'Verstuur ketelcheck' : 'Volgende';

  errorMessage.textContent = '';

  if (body) body.scrollTop = 0;
}

function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function getInputLabel(field) {
  const label = field.closest('.ko-field')?.querySelector('.ko-field__label');
  if (!label) return 'dit veld';
  return label.textContent.replace('*', '').trim().toLowerCase();
}

function validateCurrentStep() {
  const step = getCurrentStepElement();

  if (!step) {
    return 'Er ging iets mis. Probeer het opnieuw.';
  }

  const optionGroup = step.querySelector('[data-option-group]');

  if (optionGroup) {
    const hiddenInput = document.getElementById(`input_${optionGroup.dataset.optionGroup}`);
    if (!hiddenInput || hiddenInput.value.trim() === '') {
      return 'Maak eerst een keuze.';
    }
  }

  const requiredFields = Array.from(step.querySelectorAll('input[required], textarea[required]'));

  for (const field of requiredFields) {
    if (field.type === 'file') {
      if (!field.files || field.files.length === 0) {
        return `Voeg ${getInputLabel(field)} toe.`;
      }
      continue;
    }

    const value = field.value.trim();

    if (value === '') {
      return `Vul ${getInputLabel(field)} in.`;
    }

    if (field.type === 'email' && !isValidEmail(value)) {
      return 'Vul een geldig e-mailadres in.';
    }
  }

  return '';
}

nextBtn.addEventListener('click', () => {
  window.clearTimeout(advanceTimer);

  const validationError = validateCurrentStep();

  if (validationError) {
    errorMessage.textContent = validationError;
    return;
  }

  if (currentStep < totalSteps) {
    currentStep += 1;
    updateStep();
    return;
  }

  if (typeof window.gtag === 'function') {
    window.gtag('event', 'generate_lead', {
      event_category: 'lead',
      event_label: 'ketelcheck_submit'
    });
  }

  form.submit();
});

prevBtn.addEventListener('click', () => {
  window.clearTimeout(advanceTimer);

  if (currentStep === 1) {
    closeOverlay();
    return;
  }

  currentStep -= 1;
  updateStep();
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && overlay && !overlay.hidden) {
    closeOverlay();
  }
});

/* On load: render icons; auto-open when the server returned an error. */
renderIcons();

if (overlay) {
  updateStep();
  if (overlay.dataset.autostart === '1') {
    openOverlay();
  }
}

/* ============================================================
   Spoed-modal
   ============================================================ */
const emergencyModal = document.getElementById('emergencyModal');
const openEmergencyModal = document.getElementById('openEmergencyModal');
const closeEmergencyModal = document.getElementById('closeEmergencyModal');
const modalStartCheck = document.getElementById('modalStartCheck');

function showEmergencyModal() {
  if (!emergencyModal) return;
  emergencyModal.classList.add('active');
  emergencyModal.setAttribute('aria-hidden', 'false');
  document.body.classList.add('modal-open');
}

function hideEmergencyModal() {
  if (!emergencyModal) return;
  emergencyModal.classList.remove('active');
  emergencyModal.setAttribute('aria-hidden', 'true');
  document.body.classList.remove('modal-open');
}

if (openEmergencyModal) {
  openEmergencyModal.addEventListener('click', showEmergencyModal);
}

if (closeEmergencyModal) {
  closeEmergencyModal.addEventListener('click', hideEmergencyModal);
}

if (modalStartCheck) {
  modalStartCheck.addEventListener('click', () => {
    hideEmergencyModal();
    openOverlay(modalStartCheck);
  });
}

if (emergencyModal) {
  emergencyModal.addEventListener('click', (event) => {
    if (event.target === emergencyModal) {
      hideEmergencyModal();
    }
  });
}

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') {
    hideEmergencyModal();
  }
});
