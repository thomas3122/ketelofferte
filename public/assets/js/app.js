const form = document.getElementById('ketelForm');
const steps = Array.from(document.querySelectorAll('.form-step'));
const nextBtn = document.getElementById('nextBtn');
const prevBtn = document.getElementById('prevBtn');
const errorMessage = document.getElementById('errorMessage');
const progressBar = document.getElementById('progressBar');
const progressLabel = document.getElementById('progressLabel');
const progressPercent = document.getElementById('progressPercent');
const ketelcheckSection = document.getElementById('ketelcheck');

let currentStep = 1;
const totalSteps = steps.length;

if (ketelcheckSection && 'IntersectionObserver' in window) {
  const checkObserver = new IntersectionObserver((entries) => {
    const isVisible = entries.some((entry) => entry.isIntersecting);
    document.body.classList.toggle('is-check-visible', isVisible);
  }, {
    rootMargin: '-18% 0px -36% 0px',
    threshold: 0.01
  });

  checkObserver.observe(ketelcheckSection);
}

if (form) {
  form.addEventListener('focusin', () => {
    document.body.classList.add('is-form-focused');
  });

  form.addEventListener('focusout', () => {
    window.setTimeout(() => {
      if (!form.contains(document.activeElement)) {
        document.body.classList.remove('is-form-focused');
      }
    }, 80);
  });
}

document.querySelectorAll('[data-option-group]').forEach((group) => {
  const groupName = group.dataset.optionGroup;
  const buttons = Array.from(group.querySelectorAll('.option-card'));
  const hiddenInput = document.getElementById(`input_${groupName}`);

  buttons.forEach((button) => {
    button.addEventListener('click', () => {
      buttons.forEach((btn) => btn.classList.remove('selected'));

      button.classList.add('selected');

      if (hiddenInput) {
        hiddenInput.value = button.dataset.value;
      }

      errorMessage.textContent = '';
    });
  });
});

function updateStep() {
  steps.forEach((step) => {
    step.classList.toggle('active', Number(step.dataset.step) === currentStep);
  });

  const percentage = Math.round((currentStep / totalSteps) * 100);

  progressBar.style.width = `${percentage}%`;
  progressLabel.textContent = `Stap ${currentStep} van ${totalSteps}`;
  progressPercent.textContent = `${percentage}%`;

  prevBtn.style.visibility = currentStep === 1 ? 'hidden' : 'visible';
  nextBtn.textContent = currentStep === totalSteps ? 'Ontvang advies/offerte' : 'Volgende';

  errorMessage.textContent = '';
}

function getCurrentStepElement() {
  return steps.find((step) => Number(step.dataset.step) === currentStep);
}

function scrollToCurrentQuestion() {
  const isDesktop = window.innerWidth >= 900;

  if (isDesktop) {
    document.getElementById('ketelcheck').scrollIntoView({
      behavior: 'smooth',
      block: 'start'
    });

    return;
  }

  const activeStep = getCurrentStepElement();

  if (!activeStep) {
    return;
  }

  const stepTitle = activeStep.querySelector('.step-title');

  if (!stepTitle) {
    activeStep.scrollIntoView({
      behavior: 'smooth',
      block: 'start'
    });
    return;
  }

  const offset = 88;
  const titlePosition = stepTitle.getBoundingClientRect().top + window.scrollY - offset;

  window.scrollTo({
    top: titlePosition,
    behavior: 'smooth'
  });
}

function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function validateCurrentStep() {
  const step = getCurrentStepElement();

  if (!step) {
    return 'Er ging iets mis. Probeer het opnieuw.';
  }

  const optionGroup = step.querySelector('[data-option-group]');

  if (optionGroup) {
    const groupName = optionGroup.dataset.optionGroup;
    const hiddenInput = document.getElementById(`input_${groupName}`);

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

function getInputLabel(field) {
  const label = field.closest('.field')?.querySelector('label');

  if (!label) {
    return 'dit veld';
  }

  return label.textContent.toLowerCase();
}

nextBtn.addEventListener('click', () => {
  const validationError = validateCurrentStep();

  if (validationError) {
    errorMessage.textContent = validationError;
    return;
  }

  if (currentStep < totalSteps) {
    currentStep += 1;
    updateStep();
    scrollToCurrentQuestion();

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
  if (currentStep > 1) {
    currentStep -= 1;
    updateStep();
    scrollToCurrentQuestion();
  }
});

updateStep();

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
  modalStartCheck.addEventListener('click', hideEmergencyModal);
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
