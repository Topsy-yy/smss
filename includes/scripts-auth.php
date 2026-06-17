<?php
$assetPrefix = isset($assetPrefix) ? $assetPrefix : '';
?>
    <script src="<?php echo $assetPrefix; ?>js/jquery-1.10.2.js"></script>
    <script src="<?php echo $assetPrefix; ?>js/bootstrap.js"></script>
    <script src="<?php echo $assetPrefix; ?>js/pwa-register.js"></script>
    <script>
      (function () {
        var bootTime = Date.now();
        var minVisibleMs = 1200;
        var forceHideAfterMs = 5000;

        function hideAuthPreloader() {
          var preloader = document.getElementById('preloader');
          if (!preloader) {
            return;
          }
          if (preloader.getAttribute('data-hidden') === '1') {
            return;
          }
          preloader.setAttribute('data-hidden', '1');
          preloader.style.transition = 'opacity 220ms ease';
          preloader.style.opacity = '0';
          preloader.style.pointerEvents = 'none';
          window.setTimeout(function () {
            preloader.style.display = 'none';
          }, 230);
        }

        function scheduleHideAfterMinimum() {
          var elapsed = Date.now() - bootTime;
          var wait = Math.max(0, minVisibleMs - elapsed);
          window.setTimeout(hideAuthPreloader, wait);
        }

        if (document.readyState === 'complete') {
          scheduleHideAfterMinimum();
        } else {
          window.addEventListener('load', scheduleHideAfterMinimum);
        }

        window.setTimeout(hideAuthPreloader, forceHideAfterMs);
      })();
    </script>
  </body>
</html>
