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
            $schid = 0;
            if (isset($_GET['sch'])) {
              $schid = (int)$_GET['sch'];
            } elseif (isset($_GET['id'])) {
              $schid = (int)$_GET['id'];
            }
            $sigID = '';
            $schFound = false;

            $schname = $description = $eligibility = $benefits = $apply = $links = $contact = '';

            // Primary source: database (same source used by listing page)
            if ($schid > 0) {
              $schStmt = $conn->prepare("SELECT * FROM scholarship WHERE scholarshipID = ? LIMIT 1");
              if ($schStmt) {
                $schStmt->bind_param("i", $schid);
                $schStmt->execute();
                $schRow = $schStmt->get_result()->fetch_assoc();
                $schStmt->close();

                if ($schRow) {
                  $schFound = true;
                  $sigID = (string)($schRow['sigID'] ?? '');
                  $schname = (string)($schRow['schname'] ?? '');
                  $description = (string)($schRow['description'] ?? '');
                  $eligibility = (string)($schRow['eligibility'] ?? '');
                  $benefits = (string)($schRow['benefits'] ?? '');
                  $apply = (string)($schRow['apply'] ?? '');
                  $links = (string)($schRow['links'] ?? '');
                  $contact = (string)($schRow['contact'] ?? '');
                }
              }
            }

            // Fallback source: legacy XML mirror
            if (!$schFound && $schid > 0) {
              $xml = simplexml_load_file("../backend/scholarship_data.xml");
              if ($xml !== false) {
                foreach($xml->children() as $sch){
                  if ((int)$sch['scholarshipID'] === $schid) {
                    $schFound = true;
                    $sigID = (string)$sch->sigID;
                    $schname = (string)$sch->schname;
                    $description = (string)$sch->description;
                    $eligibility = (string)$sch->eligibility;
                    $benefits = (string)$sch->benefits;
                    $apply = (string)$sch->apply;
                    $links = (string)$sch->links;
                    $contact = (string)$sch->contact;
                    break;
                  }
                }
              }
            }

            if ($schFound) {
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
                    <p><?php echo $contact; ?></p>
                  </section>
                  <br><hr><br>
                    <?php $_SESSION['schid'] = $schid; ?>
                    <?php if ((int)$sigID > 0) { ?>
                      <form action="apply.php" method="post">
                          <input type="hidden" name="sigID" value="<?php echo (int)$sigID; ?>">
                          <input type="submit" name="apply" value="Apply >>">
                      </form>
                    <?php } else { ?>
                      <p style="color:#b91c1c; font-weight:600;">This scholarship is missing signatory ownership data, so applications are temporarily unavailable.</p>
                      <a href="tempUserApply.php" class="button">Back to Scholarships</a>
                    <?php } ?>
                </div>

            </section>
            <?php } else { ?>
              <section class="content-card container">
                <div class="content">
                  <h2>Scholarship not found</h2>
                  <p>The selected scholarship record could not be loaded. Please go back and try again.</p>
                  <a href="tempUserApply.php" class="button">Back to Scholarships</a>
                </div>
              </section>
            <?php }
              $conn->close();
            ?>
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
