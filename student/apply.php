<?php

?>
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
          <section class="content-card container">

          <?php
          	$conn = getDbConnection();
            $schid=$_SESSION["schid"];
            $sigID = $_POST['sigID'];
            $_SESSION['sigID'] = $sigID;
    		    $sql="SELECT * FROM application where scholarshipID=$schid AND studentID=$currentUserID AND sigID = $sigID";
    		    $result = $conn->query($sql);
    			  if($result->num_rows > 0){
               	while($row = $result->fetch_assoc()){
          ?>
             <script type="text/javascript">
              alert("You Have Already Applied for this scholarship!");
              location.replace("tempUserView.php")
            </script>

           <?php
      			   }
    		    }
            else{
            ?>
                <h1>Dear&nbsp;&nbsp;<b><?php echo $_SESSION['currentUserName'] ?></b>,</h1>
                <h1>Make sure you have your Profile Completed.<br>Your Profile details will be submitted in this application.<br></h1>
                <form style="padding-left: 20%; display: inline;" method="post">
                  <input type="submit" id="apply" name="apply" value="Check Your Profile Here >>" title="User Profile" formaction="tempUserProfile.php">
                    &nbsp;&nbsp;&nbsp;
                    <input type="submit" id="apply" name="apply" value="Continue Otherwise >>" title="Click here only if your Profile is Completed!!" formaction="applyprocess.php">

                   </form>
            <?php
        }
    		$conn->close();

            ?>

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
