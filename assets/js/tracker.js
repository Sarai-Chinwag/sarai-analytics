(function () {
  if (navigator.doNotTrack === '1' || window.doNotTrack === '1' || navigator.msDoNotTrack === '1') {
    return;
  }

  if (!window.SaraiAnalytics || !window.SaraiAnalytics.endpoint) {
    return;
  }

  var endpoint = window.SaraiAnalytics.endpoint;

  function sendEvent(eventType, eventData) {
    var payload = {
      event_type: eventType,
      event_data: eventData || {},
      page_url: window.location.href,
      referrer: document.referrer || ''
    };

    fetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    }).catch(function () {
      return null;
    });
  }

  function hasEvent(eventType) {
    if (!window.SaraiAnalytics.events) {
      return false;
    }

    return window.SaraiAnalytics.events.indexOf(eventType) !== -1;
  }

  function trackPageView() {
    if (hasEvent('page_view')) {
      sendEvent('page_view', {});
    }
  }

  function trackImageMode() {
    // Match /images/, /category/X/images/, /tag/X/images/
    if (window.location.pathname.indexOf('/images') !== -1 && hasEvent('image_mode')) {
      sendEvent('image_mode', { path: window.location.pathname });
    }
  }

  function trackRandomClick() {
    document.addEventListener('click', function (event) {
      var target = event.target;
      if (!target) {
        return;
      }

      // Check if clicked element or parent is the random link
      var randomLink = target.closest('#random-icon-link, .surprise-me');
      if (randomLink && hasEvent('random_click')) {
        sendEvent('random_click', { from: window.location.pathname });
      }
    });
  }

  function trackSmiClick() {
    document.addEventListener('click', function (event) {
      var target = event.target;
      if (!target) {
        return;
      }

      var button = target.closest ? target.closest('.smi-get-button, .download-hires, [data-sarai-smi]') : null;
      if (button && hasEvent('smi_click')) {
        sendEvent('smi_click', { label: button.textContent || '' });
      }
    });
  }

  function trackSearch() {
    document.addEventListener('submit', function (event) {
      var form = event.target;
      if (!form || form.tagName !== 'FORM') {
        return;
      }

      var input = form.querySelector('input[type="search"], input[name="s"]');
      if (input && hasEvent('search')) {
        sendEvent('search', { query: input.value || '' });
      }
    });
  }

  function trackNavClick() {
    if (!hasEvent('nav_click')) {
      return;
    }

    document.addEventListener('click', function (event) {
      var target = event.target;
      if (!target) {
        return;
      }

      // Find closest anchor element
      var link = target.closest ? target.closest('a') : null;
      if (!link) {
        return;
      }

      // Check if link is within a navigation area
      var navSelectors = [
        '.site-navigation',
        '#site-navigation',
        '.main-navigation',
        'nav'
      ];

      var isNavLink = false;
      for (var i = 0; i < navSelectors.length; i++) {
        if (link.closest(navSelectors[i])) {
          isNavLink = true;
          break;
        }
      }

      if (isNavLink) {
        sendEvent('nav_click', {
          text: (link.textContent || '').trim().substring(0, 100),
          href: link.href || ''
        });
      }
    });
  }

  trackPageView();
  trackImageMode();
  trackRandomClick();
  trackSmiClick();
  trackSearch();
  trackNavClick();
})();
