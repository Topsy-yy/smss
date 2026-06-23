
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
      <link href="../css/admin.css" rel="stylesheet">
      <link href="../css/pages/admin.css" rel="stylesheet">

  </head>

  <body class="app-shell">
    <div class="app-page">

      <!-- Header -->
        <header class="app-header">
          <h1 class="app-logo"><a href = "javascript:history.back()" class="app-btn">Back</a></h1>
          <nav class="app-nav">
            <ul>
              <li class = ""><a href = "tempAdmin.php">Home</a></li>
              <li class = "submenu current">
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
              </li><li class = "submenu">
                <a href = "">Users</a>
                <ul>
                  <li><a href = "tempAdminShow.php">Admin</a></li>
                  <li><a href = "tempSignatoryShow.php">Signatory</a></li>
                  <li><a href = "tempStudentShow.php">Students</a></li>
                </ul>
              </li>
              <li><a href = "../backend/logout.php" class="app-btn">Logout</a></li>
            </ul>
          </nav>
        </header>


			<!-- Main -->
				<article id="main">

					<header class="page-hero container">
					</header>

					<!-- One -->
						<section class="content-card container">

							<!-- Content -->
								<div class="content">
                  <section class="applications-list-page">

										<header>
                      <h3 class="section-title"><strong>Applications of Accepted Students</strong></h3>
										</header>
			                  <?php
require '../config.php';
/* Connect to database */
			                  $conn = getDbConnection();
                        /* Checks Connection */
                        if ($conn->connect_error) {
                          die("Connection failed: " . $conn->connect_error);
                        }
                        $flag=0;
                        $to_query = "SELECT A.applicationID,A.studentID,A.scholarshipID,S.schname,A.appDate,
                        A.appstatus,A.verifiedBySignatory from application AS A join scholarship AS S ON A.scholarshipID=S.scholarshipID WHERE A.verifiedBySignatory='Approved'";
                        $sql_result = mysqli_query($conn,$to_query);
                        if (mysqli_num_rows($sql_result) > 0) {
                          ?>
                          <table class="table table-bordered app-list-table">
                            <thead>
                              <tr>

                                <th class = "col-md-1"><strong>Application Number[ID]</strong></th>
                                <th class = "col-md-1"><strong>Applicant ID</strong></th>
                                <th class = "col-md-1"><strong>Scholarship ID</strong></th>
                                <th class = "col-md-1" style="width: 25%"><strong>Scholarship Name</strong></th>
                                <th class = "col-md-1" ><strong>Application Date</strong></th>
                                <th class = "col-md-1 text-center"><strong>AppStatus</strong></th>
                                <th class = "col-md-1"><strong>Signatory Approval</strong></th>

                              </tr>
                            </thead>
                            <tbody>
                          <?php
                          $flag=1;
                          while($rows=mysqli_fetch_row($sql_result))
                          {

                            $appID = 0;
                            foreach ($rows as $key => $value)
                                {
                                  if ($key == 0)
                                  {
                                    $appID = $value;
                                    ?><tr><td><?php echo $appID;?></td><?php
                                  }
                                      if($key == 1)
                                      {
                                        ?><td><?php echo $value;?></td><?php
                                      }
                                      if($key == 2)
                                      {
                                         ?><td><?php echo $value;?></td><?php
                                      }
                                      if($key == 3)
                                      {
                                      	?><td><?php echo $value;?></td><?php
                                      }
                                      if($key == 4)
                                      {
							?><td class="col-date"><?php echo $value;?></td><?php
                                      }
                                  if ($key == 5)
                                  {
                                    $statusClass = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim((string)$value)));
                                    ?><td><span class="status-chip status-chip-app status-app-<?php echo $statusClass; ?>"><?php echo $value;?></span></td><?php
                                  }
                                  if($key == 6){
                                    $statusClass = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim((string)$value)));
                                    ?>
                                      <td><span class="status-chip status-chip-approval status-approval-<?php echo $statusClass; ?>"><?php echo $value;?></span></td></tr>
                              <?php
                                  }
                                }
                            }
                        } else{
                            echo '<div class="empty-list-state">No Accepted Applications</div>';
                        }
                        mysqli_close($conn);
                        ?>

                        </tbody>
                    </table>
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
	</body>
</html>
