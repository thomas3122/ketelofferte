(function () {
  const storageKey = 'ko24_cookie_consent';
  const banner = document.getElementById('cookieBanner');
  const config = window.KO24_TRACKING || {};

  function getChoice() {
    try {
      return window.localStorage.getItem(storageKey);
    } catch (error) {
      return null;
    }
  }

  function setChoice(choice) {
    try {
      window.localStorage.setItem(storageKey, choice);
    } catch (error) {
      return;
    }
  }

  function loadScript(src) {
    if (!src || document.querySelector('script[src="' + src + '"]')) {
      return;
    }

    const script = document.createElement('script');
    script.async = true;
    script.src = src;
    document.head.appendChild(script);
  }

  function flushPendingEvents() {
    const pendingEvents = window.KO24_PENDING_EVENTS || [];

    pendingEvents.forEach((eventData) => {
      window.dataLayer.push(eventData);
    });

    window.KO24_PENDING_EVENTS = [];
  }

  function enableTracking() {
    if (window.KO24_TRACKING_ENABLED) {
      return;
    }

    window.KO24_TRACKING_ENABLED = true;
    window.dataLayer = window.dataLayer || [];
    window.gtag = window.gtag || function () {
      window.dataLayer.push(arguments);
    };

    if (config.googleAdsId) {
      window.gtag('js', new Date());
      window.gtag('config', config.googleAdsId);
      loadScript('https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(config.googleAdsId));
    }

    if (config.gtmId) {
      window.dataLayer.push({
        'gtm.start': new Date().getTime(),
        event: 'gtm.js'
      });
      loadScript('https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(config.gtmId));
    }

    flushPendingEvents();
  }

  function showBanner() {
    if (banner) {
      banner.hidden = false;
    }
  }

  function hideBanner() {
    if (banner) {
      banner.hidden = true;
    }
  }

  const existingChoice = getChoice();

  if (existingChoice === 'accepted') {
    enableTracking();
  } else if (!existingChoice) {
    showBanner();
  }

  document.addEventListener('click', (event) => {
    const choiceButton = event.target.closest('[data-cookie-choice]');
    const settingsButton = event.target.closest('[data-cookie-settings]');

    if (choiceButton) {
      const choice = choiceButton.getAttribute('data-cookie-choice');
      const previousChoice = getChoice();
      setChoice(choice);
      hideBanner();

      if (choice === 'accepted') {
        enableTracking();
      }

      if (choice === 'rejected' && previousChoice === 'accepted') {
        window.location.reload();
      }
    }

    if (settingsButton) {
      showBanner();
    }
  });
}());
