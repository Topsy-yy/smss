
<?php
/*Start a session*/
  session_start();
  require '../backend/security.php';
  require_login(2);
?>
<!DOCTYPE html>

<html lang="en">

  <head>
      <title>Home</title>

      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">

      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="description" content="">
      <meta name="author" content="">


      <!-- Bootstrap Core CSS -->
      <link href="../css/bootstrap.min.css" rel="stylesheet">
      <link href="../css/tempuserhome.css" rel="stylesheet">
      <!-- Custom CSS -->
      <link href="../css/main.css" rel="stylesheet">

  </head>

  <body class = "index">
    <div id = "page-wrapper">

      <!-- Header -->
        <header id = "header" class = "alt" style="background-color:#f3f6fa;color:black;height:4%">
          <h1 id = "logo"><a href = "javascript:history.back()" class="button special">Back</a></h1>
          <nav id = "nav">
            <ul>
              <li class = "current"><a href = "#">Home</a></li>
              <li class = "submenu">
                <a href = "#">Applications</a>
                <ul>
                  <li><a href = "tempPendingApp.php">Pending Students</a></li>
                  <li><a href = "tempAcceptedApp.php">Accepted Students</a></li>
                  <li><a href = "tempRejectedApp.php">Rejected Students</a></li>
                </ul>
              </li>
              <li class = "submenu">
                <a href = "tempScholarship.php">Scholarships</a>
                <ul>
                  <li><a href = "tempScholarship.php?scholarship=Pending">Pending Scholarships</a></li>
                  <li><a href = "tempScholarship.php?scholarship=Approved">Accepted Scholarships</a></li>
                  <li><a href = "tempScholarship.php?scholarship=Rejected">Rejected Scholarships</a></li>
                </ul>
              </li>
              <li class = "submenu">
                <a href = "">Users</a>
                <ul>
                  <li><a href = "tempAdminShow.php">Admin</a></li>
                  <li><a href = "tempSignatoryShow.php">Signatory</a></li>
                  <li><a href = "tempStudentShow.php">Students</a></li>
                </ul>
              </li>
              <li><a href = "../backend/logout.php" class = "button special">Logout</a></li>
            </ul>
          </nav>
        </header>

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

        <section id="cta">
          <header>
            <h2>Ready to keep the platform <strong>running smoothly</strong>?</h2>
            <p>Continue managing approvals, users, and scholarships with confidence.</p>
          </header>
          <footer>
            <ul class="buttons">
              <li><a href="../tempAboutUs.php" class="button special">About Us</a></li>
            </ul>
          </footer>
        </section>

      <!-- Footer -->
        <footer id="footer">

          <ul class="icons">
            <li><a href="#" class="icon circle fa-twitter"><span class="label">Twitter</span></a></li>
            <li><a href="#" class="icon circle fa-facebook"><span class="label">Facebook</span></a></li>
            <li><a href="#" class="icon circle fa-google-plus"><span class="label">Google+</span></a></li>
            <li><a href="#" class="icon circle fa-github"><span class="label">Github</span></a></li>
            <li><a href="#" class="icon circle fa-dribbble"><span class="label">Dribbble</span></a></li>
          </ul>

          <ul class="copyright">
            <li>&copy; Scholarship Management System</li><li>Design: <a href="#">Team SMS</a></li>
          </ul>

        </footer>


    </div>

    <!-- Scripts -->
      <script src="../js/jquery.min.js"></script>
      <script src="../js/jquery.dropotron.min.js"></script>
      <script src="../js/jquery.scrolly.min.js"></script>
      <script src="../js/jquery.scrollgress.min.js"></script>
      <script src="../js/skel.min.js"></script>
      <script src="../js/util.js"></script>
      <!--[if lte IE 8]><script src="assets/js/ie/respond.min.js"></script><![endif]-->
      <script src="../js/main.js"></script>

  </body>
</html>
