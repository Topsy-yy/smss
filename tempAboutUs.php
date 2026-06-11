<?php
  session_start();
require 'config.php';
$_SESSION['selectedAppID'] = 0;

  $_SESSION['appList'] = NULL;

  //check validity of the user
  $currentUserID=$_SESSION['currentUserID'];
  if($currentUserID==NULL){
    header("Location:index.php");
  }

  // Connect to database
    $conn = getDbConnection();

  // Checks Connection
    if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
    }

  $getName = "select S.firstName, S.middleName, S.lastName from student S where S.studentID = '".$_SESSION['currentUserID']."'";

  $nameResult = mysqli_query($conn,$getName);

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

<!DOCTYPE HTML>
<html>
  <head>


      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">

      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="description" content="">
      <meta name="author" content="">


      <!-- Bootstrap Core CSS -->
      <link href="css/bootstrap.min.css" rel="stylesheet">

      <!-- Custom CSS -->
      <link href="css/main.css" rel="stylesheet">
      <link href="css/tempAboutUS.css" rel="stylesheet">
  </head>

  <body class = "no-sidebar">
    <div id = "page-wrapper">

      <!-- Header -->
        <header id = "header">
          <h1 id = "logo"><a href = "tempUserHome.php">Scholarships <span>that matter</span></a></h1>
          <nav id = "nav">
            <ul>
              <li><a href = "tempUserHome.php">Home</a></li>
              <li><a href = "tempUserProfile.php">User Profile</a></li>
              <li><a href = "tempUserApply.php">Apply</a></li>
              <li><a href = "tempUserView.php">View Scholarship Status</a></li>
              <li><?php echo $_SESSION['currentUserName']. " (ID:" . $_SESSION['currentUserID'] . ")"?></li>
              <li><a href = "backend/logout.php" class = "button special">Logout</a></li>
            </ul>
          </nav>
        </header>

    <!--image-->

      <section>
        <!-- <section id="cta" > -->
          <div class="row">
            <img src="images/refresh/about-hero.svg" alt="Education community" style="width:100%" >
            <div class="text-block">
              <div class="transbox">
                <p>Scholarship access for every learner<p>
              </div>
            </div>
          </div>
        </section>

      <article id="main" >
        <section class="wrapper style3 container special">
              <div class="test2">
                <img src="images/refresh/card-2.svg" style="float:right;margin-left:2%;max-width:220px;border-radius:12px;" >
                <p style="text-align:justify">Access to quality education should never depend on chance. Many learners miss opportunities because they cannot easily find scholarships that match their goals, profile, and timeline.</p>
                <p style="text-align:left">This platform bridges scholarship providers and scholarship seekers in one place. Students discover relevant funding options, while institutions and organizations can publish, evaluate, and manage programs with clarity.</p>
              </div>
        </section>
    </article>

              <section class="wrapper style3 container special">
                <div class="row">
                  <div class="6u 12u(narrower)">

                    <section>
                      <a href="#" class="image featured"><img src="images/refresh/about-learning.svg" alt="" style="height:300px"/></a>
                      <header>
                        <h3><b>We  are</b></h3>
                      </header>
                      <p>We are a learner-first scholarship network focused on making quality education more accessible through transparent and trusted funding opportunities.</p>
                    </section>

                  </div>

                  <div class="6u 12u(narrower)">

                    <section>
                      <a href="#" class="image featured"><img src="images/refresh/about-mission.svg" alt="" style="height:300px" /></a>
                      <header>
                        <h3><b>We  do</b></h3>
                      </header>
                      <p>We provide end-to-end support for scholarship applications and robust management tools for providers to run impactful programs at scale.</p>
                    </section>

                  </div>
                </div>
</div>

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

      <!-- Scripts -->
      <!-- jQuery --
      <script src="js/jquery.js"></script>

      <!-- Bootstrap Core JavaScript -->
      <script src="js/bootstrap.min.js"></script>
        <script src="js/jquery.min.js"></script>
        <script src="js/jquery.dropotron.min.js"></script>
        <script src="js/jquery.scrolly.min.js"></script>
        <script src="js/jquery.scrollgress.min.js"></script>
        <script src="js/skel.min.js"></script>
        <script src="js/util.js"></script>
        <!--[if lte IE 8]><script src="assets/js/ie/respond.min.js"></script><![endif]-->
        <script src="js/main.js"></script>
  </body>
</html>
