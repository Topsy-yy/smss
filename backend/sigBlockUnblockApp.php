<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title></title>
  </head>
  <body>
    <?php
    session_start();
require '../config.php';
  require_once 'notification_mailer.php';
  require_once 'email_templates.php';
$currentUserID=$_SESSION['currentUserID'];
      if($currentUserID==NULL){
        header("Location:../index.php");
      }
        $conn = getDbConnection();
        if ($conn->connect_error) {
          die("Connection failed: " . $conn->connect_error);
        }

        if(isset($_POST['blk_unblk_app']) AND $_POST['blk_unblk_app'] == "blockapp"){
            $appID = $_POST['appID'];
            $notifyEmail = '';
            $notifyStudentName = 'Student';
            $notifyScholarship = 'your scholarship application';
            $notifySql = "SELECT ST.upMail, ST.firstName, ST.lastName, SC.schname FROM application A JOIN student ST ON ST.studentID = A.studentID JOIN scholarship SC ON SC.scholarshipID = A.scholarshipID WHERE A.applicationID = '$appID' LIMIT 1";
            $notifyRes = $conn->query($notifySql);
            if ($notifyRes && $notifyRes->num_rows > 0) {
              $notifyRow = $notifyRes->fetch_assoc();
              $notifyEmail = $notifyRow['upMail'];
              $notifyStudentName = trim(($notifyRow['firstName'] ?? '') . ' ' . ($notifyRow['lastName'] ?? ''));
              if ($notifyStudentName === '') { $notifyStudentName = 'Student'; }
              $notifyScholarship = $notifyRow['schname'] ?: $notifyScholarship;
            }
            $app_sql = "UPDATE application SET previous_appstatus=appstatus, appstatus = 'inactive',previous_verifiedBySignatory=verifiedBySignatory, verifiedBySignatory = 'currently blocked' WHERE applicationID = '$appID'";
            if ($conn->query($app_sql) === TRUE) {
              $emailTemplate = email_tpl_application_blocked($notifyStudentName, $notifyScholarship);
              $subject = $emailTemplate['subject'];
              $message = $emailTemplate['body'];
              sendNotificationEmail($notifyEmail, $subject, $message);
              ?>
              <script type="text/javascript">
                alert('Successfully Blocked Application');
                location.replace('../signatory/tempSigApplication.php');
              </script>
            <?php
            } else {
              ?>
                <script type="text/javascript">
                  alert( "Unable to Block Application");
                  location.replace('../signatory/tempSigApplication.php');
                </script>
              <?php
            }
        } else if(isset($_POST['blk_unblk_app']) AND $_POST['blk_unblk_app'] == "unblockapp"){
          $appID = $_POST['appID'];
          $notifyEmail = '';
          $notifyStudentName = 'Student';
          $notifyScholarship = 'your scholarship application';
          $notifySql = "SELECT ST.upMail, ST.firstName, ST.lastName, SC.schname FROM application A JOIN student ST ON ST.studentID = A.studentID JOIN scholarship SC ON SC.scholarshipID = A.scholarshipID WHERE A.applicationID = '$appID' LIMIT 1";
          $notifyRes = $conn->query($notifySql);
          if ($notifyRes && $notifyRes->num_rows > 0) {
            $notifyRow = $notifyRes->fetch_assoc();
            $notifyEmail = $notifyRow['upMail'];
            $notifyStudentName = trim(($notifyRow['firstName'] ?? '') . ' ' . ($notifyRow['lastName'] ?? ''));
            if ($notifyStudentName === '') { $notifyStudentName = 'Student'; }
            $notifyScholarship = $notifyRow['schname'] ?: $notifyScholarship;
          }
          $app_sql = "UPDATE application SET appstatus = previous_appstatus, verifiedBySignatory = previous_verifiedBySignatory WHERE applicationID = '$appID'";
          if ($conn->query($app_sql) === TRUE) {
            $emailTemplate = email_tpl_application_restored($notifyStudentName, $notifyScholarship);
            $subject = $emailTemplate['subject'];
            $message = $emailTemplate['body'];
            sendNotificationEmail($notifyEmail, $subject, $message);
            ?>
            <script type="text/javascript">
              alert('Successfully UnBlocked Application');
              location.replace('../signatory/tempSigApplication.php');
            </script>
          <?php
          } else {
            ?>
              <script type="text/javascript">
                alert( "Unable to UnBlock Application");
                location.replace('../signatory/tempSigApplication.php');
              </script>
            <?php
          }
        } else{
          ?>
            <script type="text/javascript">
              alert('Invalid Request');
              location.replace('../signatory/tempSigHome.php');
            </script>
          <?php
        }
    ?>
  </body>
</html>
