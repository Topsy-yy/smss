
<?php
/*Start a session*/
  session_start();
  require '../backend/security.php';
  require_login(2);

  $pageTitle = 'Admin Home';
  $assetPrefix = '../';
  $roleStyles = array('css/admin.css');
  $pageStyles = array('css/pages/admin.css');
  require __DIR__ . '/../includes/head-dashboard.php';
?>

  <body class = "index">
    <div id = "page-wrapper">

      <?php require __DIR__ . '/../includes/nav-admin.php'; ?>

        <!-- Banner -->
        <section id="test">
        <div class="slideshow-container">

        <div class="mySlides fade">
          <div class="numbertext">1 / 3</div>
          <img src="../images/refresh/hero-1.svg">
          <div class="text">Manage platform quality with clear oversight tools.</div>
        </div>

        <div class="mySlides fade">
          <div class="numbertext">2 / 3</div>
          <img src="../images/refresh/hero-2.svg">
          <div class="text">Review applications and scholarship approvals in one place.</div>
        </div>

        <div class="mySlides fade">
          <div class="numbertext">3 / 3</div>
          <img src="../images/refresh/hero-3.svg">
          <div class="text">Keep users, signatories, and programs aligned and secure.</div>
        </div>

        </div>
        <br>

        <div style="text-align:center">
          <span class="dot"></span>
          <span class="dot"></span>
          <span class="dot"></span>
        </div>

        <script>
        var slideIndex = 0;
        showSlides();

        function showSlides() {
          var i;
          var slides = document.getElementsByClassName("mySlides");
          var dots = document.getElementsByClassName("dot");
          for (i = 0; i < slides.length; i++) {
            slides[i].style.display = "none";
          }
          slideIndex++;
          if (slideIndex > slides.length) {slideIndex = 1}
          for (i = 0; i < dots.length; i++) {
            dots[i].className = dots[i].className.replace(" active", "");
          }
          slides[slideIndex-1].style.display = "block";
          dots[slideIndex-1].className += " active";
          setTimeout(showSlides, 2000); // Change image every 2 seconds
        }
        </script>
        </section>

        <article id = "main">
          <header class = "special container">
            <span class = "icon fa-bar-chart-o"></span>
            <h2>Administration center</h2>
            <p>Oversee user activity, scholarship quality, and application decisions from a central dashboard.</p>

          </header>

          <section class="wrapper style2 container special-alt">
            <div class="row 50%">
              <div class="8u 12u(narrower)">

                <header>
                  <h2>Operational control for your platform</h2>
                </header>
                <p>Validate scholarship postings, moderate user access, and ensure decision workflows remain consistent and fair.</p>
                <footer>
                  <ul class="buttons">
                    <li><a href="tempPendingApp.php" class="button">Review Queue</a></li>
                  </ul>
                </footer>

              </div>
            </div>
          </section>

          <section class="wrapper style3 container special">

            <header class="major">
              <h2>Administrative <strong>highlights</strong></h2>
            </header>

            <div class="row">
              <div class="6u 12u(narrower)">

                <section>
                  <a href="#" class="image featured"><img src="../images/refresh/card-1.svg" alt="" /></a>
                  <header>
                    <h3>Moderate scholarship quality</h3>
                  </header>
                  <p>Review pending entries and keep listings accurate, complete, and trustworthy.</p>
                </section>

              </div>

              <div class="6u 12u(narrower)">

                <section>
                  <a href="#" class="image featured"><img src="../images/refresh/card-2.svg" alt="" /></a>
                  <header>
                    <h3>Manage user lifecycle</h3>
                  </header>
                  <p>Activate, block, and monitor account status to maintain a healthy ecosystem.</p>
                </section>

              </div>
            </div>

            <div class="row">
              <div class="6u 12u(narrower)">

                <section>
                  <a href="#" class="image featured"><img src="../images/refresh/card-3.svg" alt="" /></a>
                  <header>
                    <h3>Streamline approvals</h3>
                  </header>
                  <p>Process pending applications quickly while maintaining transparent review criteria.</p>
                </section>

              </div>

              <div class="6u 12u(narrower)">

                <section>
                  <a href="#" class="image featured"><img src="../images/refresh/card-4.svg" alt="" /></a>
                  <header>
                    <h3>Improve platform trust</h3>
                  </header>
                  <p>Use consistent governance standards to improve reliability for every user group.</p>
                </section>

              </div>
            </div>

            <footer class="major">
              <ul class="buttons">
                <li><a href="tempScholarship.php?scholarship=Pending" class="button">View Pending Items</a></li>
              </ul>
            </footer>

          </section>


        </article>

<?php
$ctaTitle = 'Ready to keep the platform <strong>running smoothly</strong>?';
$ctaText = 'Continue managing approvals, users, and scholarships with confidence.';
require __DIR__ . '/../includes/footer-dashboard.php';
?>


    </div>
<?php require __DIR__ . '/../includes/scripts-dashboard.php'; ?>
