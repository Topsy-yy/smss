<?php

  session_start();
require '../config.php';
// Connect to database
    $conn = getDbConnection();

  // Checks Connection
    if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
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

      <link href="../css/admin.css" rel="stylesheet">
      <link href="../css/pages/admin.css" rel="stylesheet">
      <link href="../css/pages/admin-schview.css" rel="stylesheet">

  </head>

  <body class="app-shell">
    <div class="app-page">

      <!-- Header -->
         <header class="app-header">
           <h1 class="app-logo"><a href = "javascript:history.back()" class="app-btn">Back</a></h1>
          <nav class="app-nav">
            <ul>
              <li class = ""><a href = "#">Home</a></li>
              <li class = "submenu">
                <a href = "#">Applications</a>
                <ul>
                  <li><a href = "tempPendingApp.php">Pending Students</a></li>
                  <li><a href = "tempAcceptedApp.php">Accepted Students</a></li>
                  <li><a href = "tempRejectedApp.php">Rejected Students</a></li>
                </ul>
              </li>
              <li class = "submenu current">
                <a href = "tempScholarship.php">Scholarships</a>
                <ul>
                  <li><a href = "tempScholarship.php?scholarship=Pending">Pending Scholarships</a></li>
                  <li><a href = "tempScholarship.php?scholarship=Approved">Accepted Scholarships</a></li>
                  <li><a href = "tempScholarship.php?scholarship=Rejected">Rejected Scholarships</a></li>
                </ul>
              </li>
              <li class = "submenu">
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
  					<section class="content-card container scholarship-view-page">
                <div class="scholarship-headline">
                  <h1 class="sch-title"><?php echo htmlspecialchars((string) $_POST['schname'], ENT_QUOTES, 'UTF-8'); ?></h1>
                  <div class="sch-meta-grid">
                    <div class="sch-meta-item"><span>Scholarship ID</span><strong><?php  echo (int) $_POST['schID']; ?></strong></div>
  							<div class="sch-meta-item"><span>Signatory ID</span><strong><?php  echo (int) $_POST['sigID']; ?></strong></div>
                  </div>
                </div>
            <?php
              try {
                $schid = (int) ($_POST['schID'] ?? 0);
                $isView = (($_POST['view'] ?? '') === 'View');

                $status = '';
                $adminapproval = '';
                $schname = (string) ($_POST['schname'] ?? 'Scholarship');
                $schlocation = '';
                $schlocationfrom = '';
                $description = '';
                $eligibility = '';
                $benefits = '';
                $apply = '';
                $links = '';
                $contact = '';
                $loadedFromDb = false;

                if ($isView && $schid > 0) {
                  $sql = "SELECT schname, schlocation, schlocationfrom, description, eligibility, benefits, apply, links, contact, adminapproval, schstatus
                          FROM scholarship WHERE scholarshipID = ? LIMIT 1";
                  $stmt = $conn->prepare($sql);
                  if ($stmt) {
                    $stmt->bind_param('i', $schid);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $row = $res ? $res->fetch_assoc() : null;
                    $stmt->close();

                    if ($row) {
                      $loadedFromDb = true;
                      $schname = (string) ($row['schname'] ?? $schname);
                      $schlocation = (string) ($row['schlocation'] ?? '');
                      $schlocationfrom = (string) ($row['schlocationfrom'] ?? '');
                      $description = (string) ($row['description'] ?? '');
                      $eligibility = (string) ($row['eligibility'] ?? '');
                      $benefits = (string) ($row['benefits'] ?? '');
                      $apply = (string) ($row['apply'] ?? '');
                      $links = (string) ($row['links'] ?? '');
                      $contact = (string) ($row['contact'] ?? '');
                      $adminapproval = (string) ($row['adminapproval'] ?? '');
                      $status = (string) ($row['schstatus'] ?? '');
                    }
                  }
                }

                // Fallback for legacy rows that may exist only in XML mirror.
                if (!$loadedFromDb && $schid > 0) {
                  $xmlPath = "../backend/scholarship_data.xml";
                  if (is_file($xmlPath)) {
                    $xml = @simplexml_load_file($xmlPath);
                    if ($xml !== false) {
                      foreach ($xml->children() as $sch) {
                        if ((int) $sch['scholarshipID'] === $schid) {
                          $schname = (string) ($sch->schname ?? $schname);
                          $schlocation = (string) ($sch->schlocation ?? '');
                          $schlocationfrom = (string) ($sch->schlocationfrom ?? '');
                          $description = (string) ($sch->description ?? '');
                          $eligibility = (string) ($sch->eligibility ?? '');
                          $benefits = (string) ($sch->benefits ?? '');
                          $apply = (string) ($sch->apply ?? '');
                          $links = (string) ($sch->links ?? '');
                          $contact = (string) ($sch->contact ?? '');
                          break;
                        }
                      }
                    }
                  }
                }

                $folder = $schid;
                $dir = "../scholarship/$folder/";
            ?>

                <div class="content scholarship-content">
                  <section>
                    <h1><b>What is <?php echo htmlspecialchars($schname, ENT_QUOTES, 'UTF-8'); ?> ?</b></h1>
                    <p><?php echo nl2br(htmlspecialchars($description, ENT_QUOTES, 'UTF-8')); ?></p>
                  </section>
                  <hr class="section-divider">
                  <section>
                    <h1><b>Who is offering the scholarship?</b></h1>
                    <p><?php //university or organization name ?></p>
                  </section>
                  <hr class="section-divider">
                  <section>
                    <h1><b>Documents required?</b></h1>
                    <p><?php //university or organization name ?></p>
                  </section>
                  <hr class="section-divider">
                  <section>
                    <h1><b>Who can apply for the scholarship?</b></h1>
                    <p><?php echo nl2br(htmlspecialchars($eligibility, ENT_QUOTES, 'UTF-8')); ?></p>
                  </section>
                  <hr class="section-divider">
                  <section>
                    <h1><b>What are the benifits?</b></h1>
                    <p><?php echo nl2br(htmlspecialchars($benefits, ENT_QUOTES, 'UTF-8')); ?></p>
                  </section>
                  <hr class="section-divider">
                  <section>
                    <h1><b>How can you apply?</b></h1>
                    <p><?php echo nl2br(htmlspecialchars($apply, ENT_QUOTES, 'UTF-8')); ?></p>
                  </section>
                  <hr class="section-divider">
                  <section>
                    <h1><b>Applicants must be Located at? </b></h1>
                    <p><?php echo htmlspecialchars($schlocation, ENT_QUOTES, 'UTF-8'); ?></p>
                  </section>
                  <hr class="section-divider">
                  <section>
                    <h1><b>Applicants HomeTown must be ?</b></h1>
                    <p><?php echo htmlspecialchars($schlocationfrom, ENT_QUOTES, 'UTF-8'); ?></p>
                  </section>
                  <hr class="section-divider">
                  <section>
                    <h1><b>Important Links</b></h1>
                    <p><?php echo nl2br(htmlspecialchars($links, ENT_QUOTES, 'UTF-8')); ?></p>
                  </section>
                  <hr class="section-divider">
                  <section>
                    <h1><b>Contact Details</b></h1>
                    <p><?php echo nl2br(htmlspecialchars($contact, ENT_QUOTES, 'UTF-8')); ?></p>
                  </section>
                  <hr class="section-divider">
                  <section>
                    <h1><b>Admin Approval</b></h1>
                    <p><?php echo htmlspecialchars($adminapproval, ENT_QUOTES, 'UTF-8'); ?></p>
                  </section>
                </div>

                <div class="files-header"><h1><b>Files</b></h1></div>
                <table class="table table-bordered file-list-table">
                  <thead>
                    <tr>
                      <th style="width:3%">File Name </th>
                      <th style="width:3%"></th>
                    </tr>
                  </thead>
                  <tbody>
              <?php
                // List uploaded files, but do not fail the page when the folder is missing/empty.
                $hasFiles = false;
                if (is_dir($dir)) {
                  if ($dh = opendir($dir)) {
                    while (($file = readdir($dh)) !== false) {
                      if ($file !== '.' && $file !== '..') {
                        $filePath = $dir . $file;
                        if (!is_file($filePath)) {
                          continue;
                        }
                        $hasFiles = true;
              ?>
                        <tr>
                          <td><?php echo htmlspecialchars($file, ENT_QUOTES, 'UTF-8'); ?></td>
                          <td>
                            <a href="<?php echo htmlspecialchars($filePath, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                              <button type="button" class="btn-inline-view">View</button>
                            </a>
                          </td>
                        </tr>
              <?php
                      }
                    }
                    closedir($dh);
                  }
                }

                if (!$hasFiles) {
              ?>
                    <tr>
                      <td colspan="2" class="file-empty-state">No supporting files uploaded for this scholarship.</td>
                    </tr>
              <?php
                }
              ?>
                  </tbody>
                </table>

                <hr class="section-divider">
                <div class="scholarship-actions">
                  <form method="post" class="action-row">
                    <input type="hidden" name="schID" value="<?php echo $schid; ?>">
                    <input type="submit" class="action-btn accept" name="accrej" value="Accept" formaction="../backend/adminAcceptReject.php">
                    <input type="submit" class="action-btn reject" name="accrej" value="Reject" formaction="../backend/adminAcceptReject.php">
                  </form>
                  <form name="blockform" class="action-row" action="../backend/adminBlockUnblockSch.php" method="post" onsubmit="confirmblock(this,'This will Block the Scholarship and corresponding Applications.\n This wont Block the corresponding Signatory.\n Are your Sure?')">
                    <input type="hidden" name="schID" value="<?php echo $schid; ?>">
                    <input type="submit" class="action-btn neutral" name="blk_unblk" id="blockSchbtn" value="blockScholarship" <?php if ($status === 'inactive') { echo " style='display:none'"; } ?>>
                  </form>
                  <form name="unblockform" class="action-row" action="../backend/adminBlockUnblockSch.php" onsubmit="confirmunblock(this,'This will Unblock the Scholarships and corresponding Applications.\n This wont Unblock the corresponding Signatory.\n Are your Sure?')" method="post">
                    <input type="hidden" name="schID" value="<?php echo $schid; ?>">
                    <input type="submit" class="action-btn neutral" name="blk_unblk" id="unblockSchbtn" value="unblockScholarship" <?php if ($status === 'active') { echo " style='display:none'"; } ?>>
                  </form>
                  <form action="tempScholarship.php" method="post" class="action-row">
                    <input type="submit" class="action-btn back" value="<< Go Back">
                  </form>
                </div>

            <?php
              } catch (Throwable $e) {
                echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
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
    <script type="text/javascript">

    function viewcontent(){
      var selectone=document.getElementById("class").value;
      var schview=document.getElementById("application");
      if(selectone!="select"){
        document.getElementById("schid").innerHTML = selectone;
        schview.style.display = 'block';
      }
      else{
        schview.style.display = 'none';
      }
    }

    function confirmblock(form,str){
      if(confirm(str)){
        document.blockform.submit();
      } else{
        event.preventDefault();
      }
    }

    function confirmunblock(form,str){
      if(confirm(str)){
        document.unblockform.submit();
      } else{
        event.preventDefault();
      }
    }
    </script>
      <script src="../js/jquery.min.js"></script>
	</body>
</html>
