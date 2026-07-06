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
              <li class = "submenu">
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
              </li><li class = "submenu current">
                <a href = "">Users</a>
                <ul>
                  <li><a href = "tempSignatoryShow.php">Signatory</a></li>
                  <li><a href = "tempStudentShow.php">Students</a></li>
                </ul>
              </li>
              <li><a href="tempReports.php">Reports</a></li>
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
									<section>
                    <h1 style="text-align:center; font-size:25px">Signatory Details</h1>
                    <?php
require '../config.php';
$conn = getDbConnection();
                      if ($conn->connect_error) {
                        die("Connection failed: " . $conn->connect_error);
                      }
                      $sql = "SELECT * FROM signatory";
                      $result = $conn->query($sql);
                      if ($result->num_rows > 0) {
                        ?>
                        <table class="table table-bordered">
                          <thead>
                              <tr>
                                  <th style="width:10%">Signatory ID</th>
                                  <th style="width:16%">Email ID</th>
                                  <th style="width:20%">Name</th>
                                  <th style="width:20%">Organization/University</th>
                                  <th style="width:10%">Status</th>
                                  <th style="width:7%"></th>
                                  <th style="width:7%"></th>
                                  <th style="width:7%"></th>
                                  <th style="width:7%"></th>
                              </tr>
                          </thead>
                          <tbody>
                        <?php
                          while($row = $result->fetch_assoc()) {
                              $sigID =$row['sigID'];
                              $email = $row['upMail'];
                              $name = $row['firstName']." ".$row['lastName'];
                              if($name == NULL || $name == ""){
                                $name = "NULL";
                              }
                              $org = $row['organization/university'];
                              $con = $row['contact'];
                              $status = $row['status'];
                          ?>
                              <tr>
                                <td><?php echo $sigID; ?></td>
                                <td><?php echo $email; ?></td>
                                <td><?php echo $name; ?></td>
                                <td><?php echo $org; ?></td>
                                <td><?php echo $status; ?></td>
                                <td>
                                  <form action="adminShowUser.php" method="post">
                                    <input type="hidden" name="ID" value="<?php echo $sigID; ?>">
                                    <button name="showUser" value="showSig">View</button>
                                  </form>
                                </td>
                                <td>
                                  <form name="blockform" method="post" onsubmit="confirmblock(this)" action="../backend/adminBlockUser.php">
                                    <input type="hidden" name="ID" value="<?php echo $sigID; ?>">
                                    <button  name="blockUser" id="blockUserbtn" value="blockSig" <?php if($row['status'] === "inactive"){
                                      echo "disabled";
                                      echo " style = 'color:#fff'";
                                    } ?>>Block</button>
                                  </form>
                                </td>
                                <td>
                                  <form name="unblockform" action="../backend/adminUnblockUser.php" onsubmit="confirmunblock(this)"  method="post">
                                    <input type="hidden" name="ID" value="<?php echo $sigID; ?>">
                                    <button name="unblockUser" id="unblockUserbtn" value="unblockSig" <?php if($row['status'] === "active"){
                                      echo "disabled";
                                      echo " style = 'color:#fff'";
                                    } ?>>UnBlock</button>
                                  </form>
                                </td>
                                <td>
                                  <form action="../backend/adminDeleteUser.php" method="post" onsubmit="return confirm('Delete this signatory profile permanently? This will remove their scholarships and related applications.');">
                                    <input type="hidden" name="ID" value="<?php echo $sigID; ?>">
                                    <input type="hidden" name="userType" value="signatory">
                                    <button type="submit" style="background:#ef4444;color:#fff;">Delete</button>
                                  </form>
                                </td>
                              </tr>
                          <?php } ?>
                        </tbody>
                      </table>
                      <?php
                        } else {
                            echo "No result";
                        }
                        $conn->close();
                    ?>

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

    <script type="text/javascript">
      function confirmblock(form){
        if(confirm("This will Block Signatory, the Scholarships corresponding to them as well as All Applications.\n Are your Sure?")){
          document.blockform.submit();
        } else{
          event.preventDefault();
        }
      }
      function confirmunblock(form){
        if(confirm("This will Unblock Signatory, the Scholarships corresponding to them as well as All Applications.\n Are your Sure?")){
          document.unblockform.submit();
        } else{
          event.preventDefault();
        }
      }
    </script>
      <script src="../js/jquery.min.js"></script>
	</body>
</html>
