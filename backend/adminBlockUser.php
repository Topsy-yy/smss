<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title></title>
  </head>
  <body>
      <?php
require '../config.php';
    require_once 'notification_mailer.php';
  require_once 'email_templates.php';
  require_once 'IRRecommendationEngine.php';
$conn = getDbConnection();
      if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
      }

        if(isset($_POST['blockUser']) AND $_POST['blockUser'] == "blockStudent"){
            $studentID = $_POST['ID'];
            $notifyEmail = '';
            $notifyName = 'Student';
            $infoSql = "SELECT upMail, firstName, lastName FROM student WHERE studentID = '$studentID' LIMIT 1";
            $infoRes = $conn->query($infoSql);
            if ($infoRes && $infoRes->num_rows > 0) {
              $infoRow = $infoRes->fetch_assoc();
              $notifyEmail = $infoRow['upMail'];
              $notifyName = trim(($infoRow['firstName'] ?? '') . ' ' . ($infoRow['lastName'] ?? ''));
              if ($notifyName === '') { $notifyName = 'Student'; }
            }
            $student_sql = "UPDATE student SET status = 'inactive' WHERE studentID = '$studentID'";
            if ($conn->query($student_sql) === TRUE) {
              $app_sql = "UPDATE application SET previous_appstatus=appstatus, appstatus = 'inactive',previous_verifiedBySignatory=verifiedBySignatory, verifiedBySignatory = 'currently blocked' WHERE studentID = '$studentID'";
              if ($conn->query($app_sql) === TRUE) {
                $emailTemplate = email_tpl_account_blocked_student($notifyName);
                $subject = $emailTemplate['subject'];
                $message = $emailTemplate['body'];
                sendNotificationEmail($notifyEmail, $subject, $message);
              ?>
                <script type="text/javascript">
                  alert('Successfully Blocked the Student and corresponding Applicaitons');
                  location.replace('../admin/tempStudentShow.php');
                </script>
              <?php
              } else {
                ?>
                  <script type="text/javascript">
                    alert( "Unable to Block Applications");
                    location.replace('../admin/tempStudentShow.php');
                  </script>
                <?php
              }
            } else {
            ?>
              <script type="text/javascript">
                alert( "Unable to Block Student");
                location.replace('../admin/tempStudentShow.php');
              </script>
            <?php
          }


        } else if(isset($_POST['blockUser']) AND $_POST['blockUser'] == "blockSig"){
          $sigID = $_POST['ID'];
          $notifyEmail = '';
          $notifyName = 'Signatory';
          $infoSql = "SELECT upMail, firstName, lastName FROM signatory WHERE sigID = '$sigID' LIMIT 1";
          $infoRes = $conn->query($infoSql);
          if ($infoRes && $infoRes->num_rows > 0) {
            $infoRow = $infoRes->fetch_assoc();
            $notifyEmail = $infoRow['upMail'];
            $notifyName = trim(($infoRow['firstName'] ?? '') . ' ' . ($infoRow['lastName'] ?? ''));
            if ($notifyName === '') { $notifyName = 'Signatory'; }
          }
          $sig_sql = "UPDATE signatory SET status = 'inactive' WHERE sigID = '$sigID'";
          if ($conn->query($sig_sql) === TRUE) {
            $sch_sql = "UPDATE scholarship SET previous_adminapproval = adminapproval, adminapproval = 'currently blocked', schstatus = 'inactive' WHERE sigID = '$sigID'";
            if ($conn->query($sch_sql) === TRUE) {
                if (class_exists('IRRecommendationEngine')) {
                  IRRecommendationEngine::markScholarshipCorpusDirty($conn);
                }
                $app_sql = "UPDATE application SET previous_appstatus=appstatus, appstatus = 'inactive',previous_verifiedBySignatory=verifiedBySignatory, verifiedBySignatory = 'currently blocked' WHERE sigID = '$sigID'";
                if ($conn->query($app_sql) === TRUE) {
                  $emailTemplate = email_tpl_account_blocked_signatory($notifyName);
                  $subject = $emailTemplate['subject'];
                  $message = $emailTemplate['body'];
                  sendNotificationEmail($notifyEmail, $subject, $message);
                  ?>
                  <script type="text/javascript">
                    alert('Successfully Blocked the Signatory, corresponding Scholarships and Applications');
                    location.replace('../admin/tempSignatoryShow.php');
                  </script>
                <?php
                } else {
                  ?>
                    <script type="text/javascript">
                      alert( "Unable to Block Applications");
                      location.replace('../admin/tempSignatoryShow.php');
                    </script>
                  <?php
                }
            } else{
              ?>
                <script type="text/javascript">
                  alert( "Unable to Block Scholarships And Applications");
                  location.replace('../admin/tempSignatoryShow.php');
                </script>
              <?php
            }
          } else {
          ?>
            <script type="text/javascript">
              alert( "Unable to Block Signatory");
              location.replace('../admin/tempSignatoryShow.php');
            </script>
          <?php
          }


        } else if(isset($_POST['blockUser']) AND $_POST['blockUser'] == "blockAdmin"){
          echo "Admin";
        } else{
          ?>
            <script type="text/javascript">
              alert('Invalid Page');
              location.replace("../admin/tempAdmin.php");
            </script>
          <?php
        }
        $conn->close();
      ?>
  </body>
</html>
