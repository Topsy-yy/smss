<?php
  session_start();
  require '../config.php';
  require '../backend/security.php';
  require_login(3);
  $_SESSION['selectedAppID'] = 0;

  $_SESSION['appList'] = NULL;

  $currentUserID = $_SESSION['currentUserID'];
  $conn = getDbConnection();
  $getName = $conn->prepare("SELECT firstName, middleName, lastName FROM signatory WHERE sigID = ?");
  $getName->bind_param("i", $currentUserID);
  $getName->execute();
  $nameResult = $getName->get_result();

while($rows9=mysqli_fetch_row($nameResult))
{
foreach ($rows9 as $key => $value)
	{
	 	if($key == 0)
		{
			$_SESSION['currentUserName'] = $value;
		}


		if($key == 1)
		{
			$_SESSION['currentUserName'] = $_SESSION['currentUserName'] . " " . $value;
		}


	    if($key == 2)
	    {
			$_SESSION['currentUserName'] = $_SESSION['currentUserName'] . ". " . $value;
		}
	}
}

?>

<?php
$pageTitle = 'Signatory Home';
$assetPrefix = '../';
$roleStyles = array('css/sig.css');
$pageStyles = array('css/pages/signatory.css');
require __DIR__ . '/../includes/head-dashboard.php';
?>
  <body class = "index">
    <div id = "page-wrapper">

      <?php require __DIR__ . '/../includes/nav-signatory.php'; ?>

        <!-- Banner -->
        <section id="test">
        <div class="slideshow-container">

        <div class="mySlides fade">
          <div class="numbertext">1 / 3</div>
          <img src="../images/refresh/hero-1.svg">
          <div class="text">Create high-impact scholarship programs with confidence.</div>
        </div>

        <div class="mySlides fade">
          <div class="numbertext">2 / 3</div>
          <img src="../images/refresh/hero-2.svg">
          <div class="text">Review applications faster using a focused workflow.</div>
        </div>

        <div class="mySlides fade">
          <div class="numbertext">3 / 3</div>
          <img src="../images/refresh/hero-3.svg">
          <div class="text">Track outcomes and keep your process transparent.</div>
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
            <h2>Signatory dashboard overview</h2>
            <p>Publish scholarships, review applicants, and update decisions from one unified workspace.</p>

          </header>

          <section class="wrapper style2 container special-alt provider-hero-section">
            <div class="row 50%">
              <div class="10u 12u(narrower) provider-hero-content">

                <header>
                  <h2>Built for scholarship providers</h2>
                </header>
                <p>Create transparent opportunities, define clear eligibility criteria, and keep applicants informed throughout the lifecycle.</p>
                <footer>
                  <ul class="buttons">
                    <li><a href="tempAddScholarship.php" class="button">Create Scholarship</a></li>
                  </ul>
                </footer>

              </div>
            </div>
          </section>

          <section class="wrapper style3 container special">

            <header class="major">
              <h2>Program management <strong>highlights</strong></h2>
            </header>

            <div class="row">
              <div class="6u 12u(narrower)">

                <section>
                  <a href="#" class="image featured"><img src="../sig-pics/pub.jpg" alt="Publish opportunities quickly" /></a>
                  <header>
                    <h3>Publish opportunities quickly</h3>
                  </header>
                  <p>Launch new scholarship calls with structured details, deadlines, and funding information.</p>
                </section>

              </div>

              <div class="6u 12u(narrower)">

                <section>
                  <a href="#" class="image featured"><img src="../sig-pics/rev.jpg" alt="Review applications efficiently" /></a>
                  <header>
                    <h3>Review applications efficiently</h3>
                  </header>
                  <p>Sort and assess candidate submissions with clear visibility into their application status.</p>
                </section>

              </div>
            </div>

            <div class="row">
              <div class="6u 12u(narrower)">

                <section>
                  <a href="#" class="image featured"><img src="../sig-pics/app.jpg" alt="Approve with transparency" /></a>
                  <header>
                    <h3>Approve with transparency</h3>
                  </header>
                  <p>Maintain a clear audit trail for accepted, pending, and rejected applications.</p>
                </section>

              </div>

              <div class="6u 12u(narrower)">

                <section>
                  <a href="#" class="image featured"><img src="../sig-pics/track.jpg" alt="Track scholarship performance" /></a>
                  <header>
                    <h3>Track scholarship performance</h3>
                  </header>
                  <p>Monitor program progress and identify where additional outreach can improve impact.</p>
                </section>

              </div>
            </div>

            <footer class="major">
              <ul class="buttons">
                <li><a href="tempSigApplication.php" class="button">Open Applications</a></li>
              </ul>
            </footer>

          </section>


        </article>

<?php
$ctaTitle = 'Ready to run your <strong>next scholarship cycle</strong>?';
$ctaText = 'Create new opportunities and manage applicants from one streamlined interface.';
require __DIR__ . '/../includes/footer-dashboard.php';
?>


    </div>
<?php require __DIR__ . '/../includes/scripts-dashboard.php'; ?>
