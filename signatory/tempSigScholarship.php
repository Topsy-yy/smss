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


      <!-- Bootstrap Core CSS -->
      <link href="../css/bootstrap.min.css" rel="stylesheet">

      <!-- Custom CSS -->
      <link href="../css/sig.css" rel="stylesheet">
	<link href="../css/pages/signatory-dashboard.css" rel="stylesheet">

  </head>

  <body class="app-shell">
    <div class="app-page">

		<?php
		  $sigNavActive = 'scholarships';
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
									<section>

										<header>
											<h3 style="padding-left: 36%;"><strong>Your Scholarships</strong></h3><br>
										</header>

				                                <?php
				                                  	$sql = "SELECT * FROM scholarship WHERE sigID='".$_SESSION['currentUserID']."'";
													$result = $conn->query($sql);
													if ($result->num_rows > 0) {
				                                ?>
				                            <table class = "table table-hover table-condensed">
				                              <thead>
				                                <tr>
				                                  <th class = "col-md-1"><strong>Scholarship</strong></th>
				                                  <th class = "col-md-2"><strong>Application Deadline</strong></th>
				                                  <th class = "col-md-1"><strong>Applications Limit</strong></th>
																					<th class = "col-md-1"><strong>Total Applicants</strong></th>
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
																							<td>20</td>
				                                      <td><?php echo $row['adminapproval'];?></td>
																							<td><strong><u>active</u></strong></td>

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
