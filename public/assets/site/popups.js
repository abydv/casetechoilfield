(function () {
  'use strict';

  function isMobile() {
    return window.matchMedia('(max-width: 860px)').matches;
  }

  function shouldShow(popup) {
    var freq = popup.dataset.frequency;
    var key = 'cms_popup_' + popup.dataset.popupId;

    if (isMobile() && popup.dataset.mobile !== '1') return false;
    if (!isMobile() && popup.dataset.desktop !== '1') return false;

    if (freq === 'always') return true;

    try {
      if (freq === 'once_per_session') {
        return !sessionStorage.getItem(key);
      }
      if (freq === 'once_per_day') {
        var last = localStorage.getItem(key);
        if (!last) return true;
        return (Date.now() - parseInt(last, 10)) > 24 * 60 * 60 * 1000;
      }
    } catch (e) {
      // Storage unavailable (private browsing, etc.) — fail open, show it once.
      return true;
    }

    return true;
  }

  function markShown(popup) {
    var freq = popup.dataset.frequency;
    var key = 'cms_popup_' + popup.dataset.popupId;
    try {
      if (freq === 'once_per_session') sessionStorage.setItem(key, '1');
      if (freq === 'once_per_day') localStorage.setItem(key, String(Date.now()));
    } catch (e) { /* ignore */ }
  }

  function close(popup) {
    popup.hidden = true;
  }

  document.querySelectorAll('.cms-popup').forEach(function (popup) {
    if (!shouldShow(popup)) return;

    var delay = parseInt(popup.dataset.delay, 10) || 0;
    setTimeout(function () {
      popup.hidden = false;
      markShown(popup);
    }, delay * 1000);

    popup.querySelector('.cms-popup-close').addEventListener('click', function () { close(popup); });
    popup.querySelector('.cms-popup-backdrop').addEventListener('click', function () { close(popup); });
  });
})();
