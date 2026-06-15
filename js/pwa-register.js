(function() {
  if (!('serviceWorker' in navigator)) {
    return;
  }

  window.addEventListener('load', function() {
    navigator.serviceWorker.register('/sw.js').catch(function() {
      // Keep registration failures silent in development.
    });
  });
})();
