<?php
  session_start();
require '../config.php';
$_SESSION['selectedAppID'] = 0;

  $_SESSION['appList'] = NULL;

  //check validity of the user
  $currentUserID=$_SESSION['currentUserID'];
  if($currentUserID==NULL){
    header("Location:../index.php");
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
    $conn->close();
?>
<!DOCTYPE HTML>
<html>
  <head>
      <title>Home</title>

      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">

      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="description" content="">
      <meta name="author" content="">


      <!-- Bootstrap Core CSS -->
      <link href="../css/bootstrap.min.css" rel="stylesheet">

      <!-- Custom CSS -->
      <link href="../css/user.css" rel="stylesheet">
      <link href="../css/pages/student.css" rel="stylesheet">
      <link href="../css/pages/student-dashboard.css" rel="stylesheet">

  </head>

  <body class="app-shell">
    <div class="app-page">

      <!-- Header -->
        <?php
          $studentNavCurrent = 'apply';
          require '../includes/nav-student.php';
        ?>

      <!-- Main -->
        <article id="main">

          <header class="page-hero container">
          </header>

          <!-- One -->
          <?php
            $conn = getDbConnection();
            $schid=$_GET['sch'];
            $sigID = NULL;
            $xml=simplexml_load_file("../backend/scholarship_data.xml") or die("Error: Cannot create object");
            foreach($xml->children() as $sch){
                if($sch['scholarshipID'] == $schid){
                  $sigID = $sch->sigID;
                  $schname = $sch->schname;
                  $schlocation = $sch->schlocation;
                  $schlocationfrom = $sch->schlocationfrom;
                  $degree = $sch->degree;
                  $gender = $sch->gender;
                  $religion = $sch->religion;
                  $scholarshipp = $sch->sch;
                  $appDeadline = $sch->appDeadline;
                  $granteesNum = $sch->granteesNum;
                  $funding = $sch->funding;
                  $description = $sch->description;
                  $eligibility = $sch->eligibility;
                  $benefits = $sch->benefits;
                  $apply = $sch->apply;
                  $links = $sch->links;
                  $contact = $sch->contact;

          ?>
            <section class="content-card container">

              <!-- Content -->
                <div class="content">
                  <section style="text-align: justify;">
                    <h1><b>What is <?php echo $schname; ?> ?</b></h1>
                    <p><?php echo $description; ?></p>
                  </section>
                  <br><hr><br>
                  <section>
                    <h1><b>Who can apply for the scholarship?</b></h1>
                    <p><?php echo $eligibility; ?></p>
                  </section>
                  <br><hr><br>
                  <section>
                    <h1><b>What are the benifits?</b></h1>
                    <p><?php echo $benefits; ?></p>
                  </section>
                  <br><hr><br>
                  <section>
                    <h1><b>How can you apply?</b></h1>
                    <p><?php echo $apply; ?></p>
                  </section>
                  <br><hr><br>
                  <section>
                    <h1><b>What are the documents required?</b></h1>
                    <p><?php //echo $row["documents"]; ?></p>
                  </section>
                  <br><hr><br>
                  <section>
                    <h1><b>What are the selection criteria?</b></h1>
                    <p><?php //echo $row["selection"]; ?></p>
                  </section>
                  <br><hr><br>
                  <section>
                    <h1><b>Important Links</b></h1>
                    <p><?php echo $links; ?></p>
                  </section>
                  <br><hr><br>
                  <section>
                    <h1><b>Contact Details</b></h1>
                    <p><?php echo $contact; } } $conn->close(); ?></p>
                  </section>
                  <br><hr><br>
                  <form action="apply.php" method="post">
                      <?php $_SESSION['schid']=$schid; ?>
                      <input type="hidden" name="sigID" value = "<?php echo $sigID; ?>">
                      <input type="submit" name="apply" value="Apply >>">
                  </form>
                </div>

            </section>
        </article>
        <!-- Footer -->
        <footer id="footer"><ul class="copyright">
          </ul>
        </footer>
    </div>

     <!-- Scripts -->
      <script src="../js/jquery.min.js"></script>
      <script src="../js/student-dashboard.js"></script>

  </body>
</html>
