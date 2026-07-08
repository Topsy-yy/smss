<?php

  session_start();
require '../config.php';
//check validity of the user
  $currentUserID=$_SESSION['currentUserID'];
  if($currentUserID==NULL){
    header("Location:../index.php");
  }

  $sigFlashMessage = trim((string) ($_SESSION['sig_flash_message'] ?? ''));
  unset($_SESSION['sig_flash_message']);

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

      <style>
        .sig-toast {
          position: fixed;
          top: 20px;
          right: 20px;
          z-index: 2500;
          max-width: 420px;
          background: #0f172a;
          color: #f8fafc;
          border: 1px solid rgba(148, 163, 184, 0.3);
          border-left: 5px solid #16a34a;
          border-radius: 12px;
          box-shadow: 0 12px 30px rgba(2, 6, 23, 0.35);
          padding: 12px 14px;
          display: flex;
          align-items: flex-start;
          gap: 10px;
          opacity: 0;
          transform: translateY(-10px);
          pointer-events: none;
          transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .sig-toast.is-visible {
          opacity: 1;
          transform: translateY(0);
          pointer-events: auto;
        }

        .sig-toast__badge {
          background: #16a34a;
          color: #ffffff;
          border-radius: 999px;
          font-size: 0.75rem;
          line-height: 1;
          padding: 4px 8px;
          margin-top: 1px;
          flex: 0 0 auto;
        }

        .sig-toast__text {
          margin: 0;
          font-size: 0.92rem;
          line-height: 1.45;
          flex: 1 1 auto;
        }

        .sig-toast__close {
          background: transparent;
          border: 0;
          color: #cbd5e1;
          font-size: 1rem;
          line-height: 1;
          cursor: pointer;
          padding: 2px;
          margin-left: 4px;
          flex: 0 0 auto;
        }
      </style>

  </head>

  <body class="app-shell sig-applications-page">
    <?php if ($sigFlashMessage !== '') { ?>
      <div id="sigToast" class="sig-toast" role="status" aria-live="polite" aria-atomic="true">
        <span class="sig-toast__badge">Success</span>
        <p class="sig-toast__text"><?php echo htmlspecialchars($sigFlashMessage, ENT_QUOTES, 'UTF-8'); ?></p>
        <button type="button" id="sigToastClose" class="sig-toast__close" aria-label="Close notification">x</button>
      </div>
      <script type="text/javascript">
        (function () {
          var toast = document.getElementById('sigToast');
          var closeBtn = document.getElementById('sigToastClose');
          if (!toast) {
            return;
          }

          var hideToast = function () {
            toast.classList.remove('is-visible');
            window.setTimeout(function () {
              if (toast && toast.parentNode) {
                toast.parentNode.removeChild(toast);
              }
            }, 220);
          };

          window.setTimeout(function () {
            toast.classList.add('is-visible');
          }, 30);

          if (closeBtn) {
            closeBtn.addEventListener('click', hideToast);
          }

          window.setTimeout(hideToast, 4200);
        })();
      </script>
    <?php } ?>
    <div class="app-page">

      <?php
        $sigNavActive = 'applications';
        require __DIR__ . '/../includes/nav-signatory.php';
      ?>


      <!-- Main -->
        <article id="main" class="sig-app-main">

          <header class="page-hero">
					</header>

					<!-- One -->
            <section class="content-card">
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

                                $showAllByDefault = ($selectedScholarshipId <= 0);

                                $countCaseExpr = "COUNT(A.applicationID)";
                                if ($app === 'Pending') {
                                  $countCaseExpr = "SUM(CASE WHEN (LOWER(A.verifiedBySignatory) = 'pending' OR LOWER(A.appstatus) = 'pending') THEN 1 ELSE 0 END)";
                                } elseif ($app === 'Approved') {
                                  $countCaseExpr = "SUM(CASE WHEN (LOWER(A.verifiedBySignatory) = 'approved' OR LOWER(A.appstatus) = 'processing') THEN 1 ELSE 0 END)";
                                } elseif ($app === 'Rejected') {
                                  $countCaseExpr = "SUM(CASE WHEN (LOWER(A.verifiedBySignatory) = 'rejected' OR LOWER(A.appstatus) = 'rejected') THEN 1 ELSE 0 END)";
                                }

                                $countLabel = ($app === 'All') ? 'Total' : $app;

                                $sql = "SELECT 
                                          S.scholarshipID,
                                          S.schname,
                                          COALESCE($countCaseExpr, 0) AS filter_count
                                        FROM scholarship S
                                        LEFT JOIN application A
                                          ON A.scholarshipID = S.scholarshipID
                                         AND A.sigID = $currentUserID
                                        WHERE S.sigID = $currentUserID
                                        GROUP BY S.scholarshipID, S.schname
                                        ORDER BY S.schname ASC";
                                $result = mysqli_query($conn, $sql);
                              ?>
                                <label style="margin-left: 30%"><h2><b>Select Your Scholarship</b></h2></label>
                                <div class="col-sm-10">
                                  <center>
                                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="GET" name="scholarshipFilterForm">
                                      <input type="hidden" name="app" value="<?php echo htmlspecialchars($app, ENT_QUOTES, 'UTF-8'); ?>">
                                      <select name="class" id="class" onchange="this.form.submit()" style="padding-top:2%;padding-bottom:2%;padding-left:2%;display:block;">
                                        <option value="0" <?php echo ($selectedScholarshipId <= 0) ? 'selected' : ''; ?>><strong>All Scholarships</strong></option>
                                        <?php
                                        while ($rows9 = mysqli_fetch_row($result)) {
                                          $tempschid = (int) $rows9[0];
                                          $tempname = $rows9[1];
                                          $filterCount = (int) $rows9[2];
                                        ?>
                                          <option value="<?php echo $tempschid; ?>" <?php echo ($selectedScholarshipId === $tempschid) ? 'selected' : ''; ?>><?php echo htmlspecialchars($tempname, ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($countLabel, ENT_QUOTES, 'UTF-8'); ?>: <?php echo $filterCount; ?>)</option>
                                        <?php
                                        }
                                        ?>
                                      </select>
                                    </form>
                                  </center>
                                </div>
                              </div>
                              <div style="height: 1.5rem;"></div>

                              <?php if ($selectedScholarshipId > 0 || $showAllByDefault) { ?>
                                <section id="application">
                                  <?php
                                    if ($app === 'Pending' || $app === 'Approved' || $app === 'Rejected') {
                                      $statusFilter = strtolower($app);
                                      if ($statusFilter === 'approved') {
                                        $statusClause = "(LOWER(A.verifiedBySignatory) = 'approved' OR LOWER(A.appstatus) = 'processing')";
                                      } elseif ($statusFilter === 'rejected') {
                                        $statusClause = "(LOWER(A.verifiedBySignatory) = 'rejected' OR LOWER(A.appstatus) = 'rejected')";
                                      } else {
                                        $statusClause = "(LOWER(A.verifiedBySignatory) = 'pending' OR LOWER(A.appstatus) = 'pending')";
                                      }
                                      $queryScholarship = "SELECT A.applicationID, A.studentID, A.scholarshipID, A.appDate, A.appstatus, A.verifiedBySignatory, S.schname FROM application A INNER JOIN scholarship S ON S.scholarshipID = A.scholarshipID WHERE A.sigID = $currentUserID AND $statusClause";
                                    } else {
                                      $queryScholarship = "SELECT A.applicationID, A.studentID, A.scholarshipID, A.appDate, A.appstatus, A.verifiedBySignatory, S.schname FROM application A INNER JOIN scholarship S ON S.scholarshipID = A.scholarshipID WHERE A.sigID = $currentUserID";
                                    }

                                    if ($selectedScholarshipId > 0) {
                                      $queryScholarship .= " AND A.scholarshipID = $selectedScholarshipId";
                                    }

                                    $queryScholarship .= " ORDER BY A.appDate DESC, A.applicationID DESC";

                                    $qSchoResult = mysqli_query($conn, $queryScholarship);
                                    if ($qSchoResult && $qSchoResult->num_rows > 0) {
                                  ?>
                                    <h1><strong><center><?php echo htmlspecialchars($app, ENT_QUOTES, 'UTF-8'); ?> Applications <?php echo ($selectedScholarshipId > 0) ? ('of Scholarship ID: ' . $selectedScholarshipId) : '(All Scholarships)'; ?></center> </strong></h1>
                                    <div class="sig-app-table-wrap">
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
                                    </div>
                                  <?php
                                    } else {
                                      echo "<center><b>No applications found for this filter.</b></center>";
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
