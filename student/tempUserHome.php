<?php
  session_start();
  require '../config.php';
  require '../backend/security.php';
  require_login(1);
  $_SESSION['selectedAppID'] = 0;

  $_SESSION['appList'] = NULL;

  $currentUserID = $_SESSION['currentUserID'];
  $conn = getDbConnection();
  $getName = $conn->prepare("SELECT firstName, middleName, lastName FROM student WHERE studentID = ?");
  $getName->bind_param("i", $currentUserID);
  $getName->execute();
  $nameResult = $getName->get_result();

  // Get every row of the table formed from the query
    while($rows9=mysqli_fetch_row($nameResult)){
      foreach ($rows9 as $key => $value){
	 	    if($key == 0){
          $_SESSION['currentUserName'] = $value;
		    }
    		if($key == 1){
    			$_SESSION['currentUserName'] = $_SESSION['currentUserName'] . " " . $value;
    		}
        if($key == 2){
          $_SESSION['currentUserName'] = $_SESSION['currentUserName'] . ". " . $value;
  		  }
	    }
    }
?>

<?php
$pageTitle = 'Student Home';
$assetPrefix = '../';
$roleStyles = array('css/user.css');
$pageStyles = array('css/pages/student.css');
require __DIR__ . '/../includes/head-dashboard.php';
?>

  <body class = "index">
    <div id = "page-wrapper">

      <?php require __DIR__ . '/../includes/nav-student.php'; ?>

        <!-- Banner -->
        <section id="test">
        <div class="slideshow-container">

        <div class="mySlides fade">
          <div class="numbertext">1 / 3</div>
          <img src="../images/refresh/hero-1.svg">
          <div class="text">Discover global scholarships that align with your goals.</div>
        </div>

        <div class="mySlides fade">
          <div class="numbertext">2 / 3</div>
          <img src="../images/refresh/hero-2.svg">
          <div class="text">Compare opportunities faster with clear eligibility details.</div>
        </div>

        <div class="mySlides fade">
          <div class="numbertext">3 / 3</div>
          <img src="../images/refresh/hero-3.svg">
          <div class="text">Track every application from submission to final decision.</div>
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



        <!-- About (To be shifted on about page) -->
        <article id = "main">
          <header class = "special container">
            <span class = "icon fa-bar-chart-o"></span>
            <h2>Graph based info</h2>
            <p><u>From Database</u></p>

          </header>

          <section class="wrapper style2 container special-alt">
            <div class="row 50%">
              <div class="8u 12u(narrower)">

                <header>
                  <h2>A MODERN SCHOLARSHIP PLATFORM</h2>
                </header>
                <p>Make education funding more accessible with a streamlined application experience and transparent status updates.</p>
                <footer>
                  <ul class="buttons">
                    <li><a href="tempUserApply.php" class="button">Browse Scholarships</a></li>
                  </ul>
                </footer>

              </div>
            </div>
          </section>

          <section class="wrapper style3 container special">

            <header class="major">
              <h2><strong>Find the best-fit scholarship</strong></h2>
              <h4>Choosing the right scholarship is a daunting task. Pick relevant scholarships and stand a chance to win.</h4>
            </header>

            <div class="row">
              <div class="6u 12u(narrower)">

                <section>
                  <a href="#" class="image featured"><img src="../images/scholarships/merit-based-scholarship.jpg" alt="" /></a>
                  <header>
                    <h3>Scholarships for merit students</h3>
                  </header>
                  <p>Merit scholarships reward strong academic or extracurricular performance and are offered by universities, organizations, and private sponsors.</p>
                </section>

              </div>

              <div class="6u 12u(narrower)">

                <section>
                  <a href="#" class="image featured"><img src="../images/scholarships/PHYSICALLY-CHALLENGED-SCHOLARSHIPS.jpg" alt="" /></a>
                  <header>
                    <h3>Need based scholarships</h3>
                  </header>
                  <p>Need-based scholarships support learners facing financial barriers, helping cover tuition, living costs, and essential learning resources.</p>
                </section>

              </div>
            </div>

            <div class="row">
              <div class="6u 12u(narrower)">

                <section>
                  <a href="#" class="image featured"><img src="../images/scholarships/MINORITIES-SCHOLARSHIPS.jpg" alt="" /></a>
                  <header>
                    <h3>Student specific scholarship</h3>
                  </header>
                  <p>Student-specific scholarships are tailored for distinct backgrounds, profiles, or circumstances to improve access and inclusion.</p>
                </section>

              </div>

              <div class="6u 12u(narrower)">

                <section>
                  <a href="#" class="image featured"><img src="../images/scholarships/STUDY-BASED-SCHOLARSHIPS.jpg" alt="" /></a>
                  <header>
                    <h3>Career-specific scholarships</h3>
                  </header>
                  <p>Career-focused scholarships target learners pursuing fields such as technology, healthcare, research, education, and creative disciplines.</p>
                </section>

              </div>
            </div>

            <footer class="major">
              <ul class="buttons">
                <li><a href="#" class="button">See Suggested Matches</a></li>
              </ul>
            </footer>

          </section>



          <section class="wrapper style5 container special">
            <header>
              <h2><strong>Popular Regions</Strong></h2>
            <header>

            <div class="row1">
              <div class="column1">
                <a href="#">
                  <img src="../images/refresh/city-1.svg" alt="North America" style="width:100%" >
                  <span style="display:block;">Scholarships in </span><b>North America</b>
                </a>
              </div>
              <div class="column1">
                <a href="#">
                  <img src="../images/refresh/city-2.svg" alt="Europe" style="width:100%">
                  <span style="display:block;">Scholarships in </span><b>Europe</b>
                </a>
              </div>
              <div class="column1">
               <a href="#">
                 <img src="../images/refresh/city-3.svg" alt="Asia Pacific" style="width:100%">
                 <span style="display:block;">Scholarships in </span><b>Asia Pacific</b>
               </a>
             </div>
            </div>
        </section>


          <section class="wrapper style1 container special">
              <div class="row">

                <div class="4u 12u(narrower)">
                  <section>
                    <header>
                      <h3>REGION-WISE SCHOLARSHIPS</h3>
                    </header>
                    <footer style="padding-left: 50px; text-align: left;">
                      <ul>
                        <li><a href="#">Top Scholarships in North America</a></li>
                        <li><a href="#">Top Scholarships in Europe</a></li>
                        <li><a href="#">Top Scholarships in Asia Pacific</a></li>
                        <li><a href="#">Top Scholarships in the Middle East</a></li>
                        <li><a href="#">Top Scholarships in Africa</a></li>
                        <li><a href="#">Top Scholarships in Latin America</a></li>
                        <li><a href="#">Top Remote / Online Scholarships</a></li>
                        <li><a href="#">Top International Mobility Grants</a></li>
                      </ul>
                    </footer>
                  </section>
                </div>

                <div class="4u 12u(narrower)">
                  <section>
                    <header>
                      <h3>STUDY LEVEL SCHOLARSHIPS</h3>
                    </header>
                    <footer style="padding-left: 50px; text-align: left;">
                      <ul>
                        <li><a href="#">Top Scholarships for High School</a></li>
                        <li><a href="#">Top Scholarships for Undergraduate Study</a></li>
                        <li><a href="#">Top Scholarships for Graduate Study</a></li>
                        <li><a href="#">Top Scholarships for Doctoral Research</a></li>
                        <li><a href="#">Top Scholarships for Diplomas</a></li>
                        <li><a href="#">Top Scholarships for Certificates</a></li>
                        <li><a href="#">Top Scholarships for Exchange Programs</a></li>
                        <li><a href="#">Top Scholarships for Continuing Education</a></li>
                      </ul>
                    </footer>
                  </section>
                </div>

                <div class="4u 12u(narrower)">
                  <section>
                    <header>
                      <h3>CATEGORY-BASED SCHOLARSHIPS</h3>
                    </header>
                    <footer style="padding-left: 50px; text-align: left;">
                      <ul>
                        <li><a href="#">Top Scholarships for Women</a></li>
                        <li><a href="#">Top Merit-Based Scholarships</a></li>
                        <li><a href="#">Top Need-Based Scholarships</a></li>
                        <li><a href="#">Top Inclusion Scholarships</a></li>
                        <li><a href="#">Top Talent Scholarships</a></li>
                        <li><a href="#">Top Public Scholarships</a></li>
                        <li><a href="#">Top Health & Medical Scholarships</a></li>
                        <li><a href="#">Top STEM Scholarships</a></li>

                      </ul>
                    </footer>
                  </section>
                </div>

              </div>
            </section>

        </article>

<?php
$ctaTitle = 'Ready to find your <strong>next opportunity</strong>?';
$ctaText = 'Explore trusted scholarships, apply with confidence, and manage everything in one place.';
require __DIR__ . '/../includes/footer-dashboard.php';
?>


    </div>
<?php require __DIR__ . '/../includes/scripts-dashboard.php'; ?>
