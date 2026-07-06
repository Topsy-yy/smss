<?php

  session_start();
require '../config.php';
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

$getName = "select S.firstName, S.middleName, S.lastName from signatory S where S.sigID = '".$_SESSION['currentUserID']."'";

$nameResult = mysqli_query($conn,$getName);

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
<!DOCTYPE HTML>

<html>
  <head>
      <title>Home</title>

      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">

      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="description" content="">
      <meta name="author" content="">

      <link href="../css/bootstrap.min.css" rel="stylesheet">

      <link href="../css/sig.css" rel="stylesheet">
      <link href="../css/pages/signatory-dashboard.css" rel="stylesheet">

  </head>

  <body class="app-shell">
    <div class="app-page">

      <?php
        $sigNavActive = 'applications';
        require __DIR__ . '/../includes/nav-signatory.php';
      ?>


			<!-- Main -->
				<article id="main">

					<header class="page-hero container">
					</header>

					<!-- One -->
						<section class="content-card container">
							<!-- Content -->
  						<div class="content">
                              <div class="form-group">
                              <?php
                              try {
                                $app = isset($_GET['app']) ? $_GET['app'] : 'All';
                                $allowedFilters = array('Pending', 'Rejected', 'Approved', 'All');
                                if (!in_array($app, $allowedFilters, true)) {
                                  $app = 'All';
                                }

                                $selectedScholarshipId = 0;
                                if (isset($_GET['class'])) {
                                  $selectedScholarshipId = (int) $_GET['class'];
                                }

                                $sql = "SELECT scholarshipID, schname FROM scholarship WHERE sigID = $currentUserID";
                                $result = mysqli_query($conn, $sql);
                              ?>
                                <label style="margin-left: 30%"><h2><b>Select Your Scholarship</b></h2></label>
                                <div class="col-sm-10">
                                  <center>
                                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="GET" name="scholarshipFilterForm">
                                      <input type="hidden" name="app" value="<?php echo htmlspecialchars($app, ENT_QUOTES, 'UTF-8'); ?>">
                                      <select name="class" id="class" onchange="this.form.submit()" style="padding-top:2%;padding-bottom:2%;padding-left:2%;display:block;">
                                        <option value="0" <?php echo ($selectedScholarshipId <= 0) ? 'selected' : ''; ?>><strong>Select Your Scholarship</strong></option>
                                        <?php
                                        while ($rows9 = mysqli_fetch_row($result)) {
                                          $tempschid = (int) $rows9[0];
                                          $tempname = $rows9[1];
                                        ?>
                                          <option value="<?php echo $tempschid; ?>" <?php echo ($selectedScholarshipId === $tempschid) ? 'selected' : ''; ?>><?php echo htmlspecialchars($tempname, ENT_QUOTES, 'UTF-8'); ?></option>
                                        <?php
                                        }
                                        ?>
                                      </select>
                                    </form>
                                  </center>
                                </div>
                              </div>
                              <br><br><br><br><br><br><br>

                              <?php if ($selectedScholarshipId > 0) { ?>
                                <section id="application">
                                  <?php
                                    if ($app === 'Pending' || $app === 'Approved' || $app === 'Rejected') {
                                      $statusFilter = strtolower($app);
                                      if ($statusFilter === 'approved') {
                                        $statusClause = "(LOWER(verifiedBySignatory) = 'approved' OR LOWER(appstatus) = 'processing')";
                                      } elseif ($statusFilter === 'rejected') {
                                        $statusClause = "(LOWER(verifiedBySignatory) = 'rejected' OR LOWER(appstatus) = 'rejected')";
                                      } else {
                                        $statusClause = "(LOWER(verifiedBySignatory) = 'pending' OR LOWER(appstatus) = 'pending')";
                                      }
                                      $queryScholarship = "SELECT applicationID, studentID, scholarshipID, appDate, appstatus, verifiedBySignatory FROM application WHERE scholarshipID = $selectedScholarshipId AND sigID = $currentUserID AND $statusClause";
                                    } else {
                                      $queryScholarship = "SELECT applicationID, studentID, scholarshipID, appDate, appstatus, verifiedBySignatory FROM application WHERE scholarshipID = $selectedScholarshipId AND sigID = $currentUserID";
                                    }

                                    $qSchoResult = mysqli_query($conn, $queryScholarship);
                                    if ($qSchoResult && $qSchoResult->num_rows > 0) {
                                  ?>
                                    <h1><strong><center><?php echo htmlspecialchars($app, ENT_QUOTES, 'UTF-8'); ?> Applications of Scholarship ID: <?php echo $selectedScholarshipId; ?></center> </strong></h1>
                                    <table class="table table-bordered">
                                      <thead>
                                        <tr>
                                          <th style="width:3%">Application ID</th>
                                          <th style="width:3%">Student ID</th>
                                          <th style="width:3%">Scholarship ID</th>
                                          <th style="width:3%">App Date</th>
                                          <th style="width:3%">Status</th>
                                          <th class="col-md-1" style="width: 5%;text-align:center;font-size:26px" colspan="5"><strong>Action</strong></th>
                                        </tr>
                                      </thead>
                                      <tbody>
                                    <?php
                                      while ($rows = mysqli_fetch_row($qSchoResult)) {
                                        $status = NULL;
                                        foreach ($rows as $key => $value) {
                                          if ($key == 0) {
                                    ?>
                                            <tr><td><?php $appID = $value; echo $value; ?>
                                    <?php
                                          }
                                          if ($key == 1) {
                                    ?>
                                            </td><td><?php $studentID = $value; echo $value; ?>
                                    <?php
                                          }
                                          if ($key == 2) {
                                    ?>
                                            </td><td><?php $schID = $value; echo $value; ?>
                                    <?php
                                          }
                                          if ($key == 3) {
                                    ?>
                                            </td><td><?php echo $value; ?>
                                    <?php
                                          }
                                          if ($key == 4) {
                                    ?>
                                            </td><td><?php echo $value; $status = $value; ?>
                                    <?php
                                          }
                                          if ($key == 5) {
                                            $verifiedBySignatory = $value;
                                          }
                                        }
                                    ?>
                                        </td><td>
                                          <form action="sigAppView.php" method="post">
                                            <input type="hidden" name="appID" value="<?php echo $appID; ?>">
                                            <input type="hidden" name="schID" value="<?php echo $schID; ?>">
                                            <input type="hidden" name="studentID" value="<?php echo $studentID; ?>">
                                            <button name="view" value="View">View</button>
                                          </form>
                                        </td><td>
                                          <form action="../backend/sigAcceptReject.php" method="post">
                                            <input type="hidden" name="appID" value="<?php echo $appID; ?>">
                                            <button name="accrej" value="Accept" <?php if ($verifiedBySignatory == 'Approved') { echo "disabled style='color:#fff'"; } ?>>Approve</button>
                                          </form>
                                        </td><td>
                                          <form action="../backend/sigAcceptReject.php" method="post">
                                            <input type="hidden" name="appID" value="<?php echo $appID; ?>">
                                            <button name="accrej" value="Reject" <?php if ($verifiedBySignatory == 'Rejected') { echo "disabled style='color:#fff'"; } ?>>Reject</button>
                                          </form>
                                        </td><td>
                                          <form name="blockform" action="../backend/sigBlockUnblockApp.php" method="post" onsubmit="confirmblock(this)">
                                            <input type="hidden" name="appID" value="<?php echo $appID; ?>">
                                            <button name="blk_unblk_app" value="blockapp" <?php if ($status == 'inactive') { echo "disabled style='color:#fff'"; } ?>>Block</button>
                                          </form>
                                        </td><td>
                                          <form name="unblockform" action="../backend/sigBlockUnblockApp.php" method="post" onsubmit="confirmunblock(this)">
                                            <input type="hidden" name="appID" value="<?php echo $appID; ?>">
                                            <button name="blk_unblk_app" value="unblockapp" <?php if ($status != 'inactive') { echo "disabled style='color:#fff'"; } ?>>UnBlock</button>
                                          </form>
                                        </td></tr>
                                    <?php
                                      }
                                    ?>
                                      </tbody>
                                    </table>
                                  <?php
                                    } else {
                                      echo "<center><b>No applications found for this scholarship.</b></center>";
                                    }
                                  ?>
                                </section>
                              <?php } ?>
                  <?php
                    mysqli_close($conn);
                  } catch (Exception $e) {}
                  ?>
  						</div>
				</section>
			</article>
			<!-- Footer -->
				<footer id="footer"><ul class="copyright">
					</ul>

				</footer>

		</div>

		<!-- Scripts -->

    <script type="text/javascript">
      function confirmblock(form){
        if(confirm("This will Block corresponding Application.\n Are your Sure?")){
          document.blockform.submit();
        } else{
          event.preventDefault();
        }
      }
      function confirmunblock(form){
        if(confirm("This will Unblock corresponding Application.\n Are your Sure?")){
          document.unblockform.submit();
        } else{
          event.preventDefault();
        }
      }
    </script>

      <script src="../js/jquery.min.js"></script>
	</body>
</html>
