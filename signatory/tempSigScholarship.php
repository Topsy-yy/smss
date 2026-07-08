	<?php
  session_start();
require '../config.php';
require_once '../backend/MatchingEngine.php';
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

function renderMatchedStudentsHtml($scholarshipId) {
	$matched = MatchingEngine::getMatchedStudentsForScholarship((int) $scholarshipId);
	if (empty($matched)) {
		return '<span style="color:#777;">No confident matches</span>';
	}

	$chunks = [];
	$count = 0;
	foreach ($matched as $student) {
		if ($count >= 3) {
			break;
		}
		$name = htmlspecialchars((string) ($student['name'] ?? ('Student ' . ($student['studentID'] ?? ''))));
		$score = isset($student['score']) ? (int) $student['score'] : null;
		if ($score !== null) {
			$chunks[] = $name . ' (' . $score . '%)';
		} else {
			$chunks[] = $name;
		}
		$count++;
	}

	return implode('<br>', $chunks);
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


      <!-- Bootstrap Core CSS -->
      <link href="../css/bootstrap.min.css" rel="stylesheet">

      <!-- Custom CSS -->
      <link href="../css/sig.css" rel="stylesheet">
	<link href="../css/pages/signatory-dashboard.css" rel="stylesheet">

  </head>

	<body class="app-shell sig-scholarships-page">
    <div class="app-page">

		<?php
		  $sigNavActive = 'scholarships';
		  require __DIR__ . '/../includes/nav-signatory.php';
		?>



			<!-- Main -->
				<article id="main" class="sig-scholarship-main">

					<header class="page-hero">
					</header>

					<!-- One -->
						<section class="content-card">

							<!-- Content -->
								<div class="content">
									<section>

										<header>
											<h3 style="padding-left: 30%;"><strong>All Scholarships and Matched Students</strong></h3><br>
										</header>

				                                <?php
				                                  	$sql = "SELECT S.*, SIG.firstName AS sigFirstName, SIG.lastName AS sigLastName FROM scholarship S LEFT JOIN signatory SIG ON SIG.sigID = S.sigID ORDER BY S.appDeadline ASC";
													$result = $conn->query($sql);
													if ($result->num_rows > 0) {
				                                ?>
					                            <div class="sig-scholarship-table-wrap">
					                            <table class = "table table-hover table-condensed">
				                              <thead>
				                                <tr>
				                                  <th class = "col-md-1"><strong>Scholarship</strong></th>
				                                  <th class = "col-md-2"><strong>Owner</strong></th>
				                                  <th class = "col-md-2"><strong>Application Deadline</strong></th>
				                                  <th class = "col-md-1"><strong>Applications Limit</strong></th>
																	<th class = "col-md-2"><strong>IR Matched Students</strong></th>
				                               	  <th class = "col-md-1"><strong>Admin Approval</strong></th>
																					<th class = "col-md-1"><strong>Scholarship Status</strong></th>
				                                  <th class = "col-md-1"></th>

				                                </tr>
				                              </thead>
				                              <tbody>
				                              		<?php
				                              			while($row = $result->fetch_assoc()) {
				                              		?>
				                                    <tr>

				                                      <td style="text-transform : uppercase;"><strong><?php echo $row['schname']; ?></strong></td>
															  <td><?php echo htmlspecialchars(trim(($row['sigFirstName'] ?? '') . ' ' . ($row['sigLastName'] ?? ''))); ?></td>
				                                      <td style="padding :1%">
				                                        <?php
				                                          $now = time();
				                                          $date = $row['appDeadline'];

				                                          if (strtotime($date) > $now){
				                                            echo "Ongoing", "(", $date, ")";
				                                          }

				                                          else{
				                                              echo "Finished";
				                                          }
				                                        ?>
				                                      </td>
				                                      <td><?php echo $row['granteesNum'];?></td>
																	<td><?php echo renderMatchedStudentsHtml((int) $row['scholarshipID']); ?></td>
				                                      <td><?php echo $row['adminapproval'];?></td>
																	<td><strong><u><?php echo htmlspecialchars((string) $row['schstatus']); ?></u></strong></td>

					                                  <td>
				                                      	<form method = "post" name = "editScholarshipForm" action = "tempEditScholarship.php">
					                                      	<input type = "hidden" name = "scholarshipID" value = "<?php echo $row['scholarshipID']; ?>">
					                                        <button type = "submit" name="view" class = "btn btn-info">View</button>
					                                  	</form>
					                                  	</td>
				                                    </tr>
				                                <?php }?>
				                              </tbody>
				                              <?php
				                                }
				                                else{
				                               ?>
				                                	<h3 align="text-center">You Have Not Submitted Any Scholarship</h3>
				                               <?php
				                            	}
				                              ?>
					                            </table>
					                            </div>


				                           <form action = "tempAddScholarship.php" class = "text-center">
												<input type = "submit" value = "Add Scholarship">
											</form>


									</section>
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
    <script src="../js/jquery.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="../js/bootstrap.min.js"></script>

    <!-- Plugin JavaScript -->
    <script src="../js/jquery.easing.min.js"></script>
    <script src="../js/jquery.fittext.js"></script>

    <!-- Custom Theme JavaScript -->
    <script src="../js/creative.js"></script>
	</body>
</html>
