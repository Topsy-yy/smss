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
$conn = getDbConnection();
        if ($conn->connect_error) {
          die("Connection failed: " . $conn->connect_error);
        }

        if(isset($_POST['blk_unblk']) AND $_POST['blk_unblk'] == "blockScholarship"){
          $schID = $_POST['schID'];
          $notifyEmail = '';
          $notifyScholarship = '';
          $infoSql = "SELECT S.schname, SIG.upMail FROM scholarship S JOIN signatory SIG ON SIG.sigID = S.sigID WHERE S.scholarshipID = '$schID' LIMIT 1";
          $infoRes = $conn->query($infoSql);
          if ($infoRes && $infoRes->num_rows > 0) {
            $infoRow = $infoRes->fetch_assoc();
            $notifyEmail = $infoRow['upMail'];
            $notifyScholarship = $infoRow['schname'];
          }
          $sch_sql = "UPDATE scholarship SET previous_adminapproval = adminapproval, adminapproval = 'currently blocked', schstatus = 'inactive' WHERE scholarshipID = '$schID'";
          if ($conn->query($sch_sql) === TRUE) {
              $app_sql = "UPDATE application SET previous_appstatus=appstatus, appstatus = 'inactive',previous_verifiedBySignatory=verifiedBySignatory, verifiedBySignatory = 'currently blocked' WHERE scholarshipID = '$schID'";
              if ($conn->query($app_sql) === TRUE) {
                $emailTemplate = email_tpl_scholarship_blocked($notifyScholarship);
                $subject = $emailTemplate['subject'];
                $message = $emailTemplate['body'];
                sendNotificationEmail($notifyEmail, $subject, $message);
                ?>
                <script type="text/javascript">
                  alert('Successfully Blocked Scholarships and corresponding Applications');
                  location.replace('../admin/tempScholarship.php');
                </script>
              <?php
              } else {
                ?>
                  <script type="text/javascript">
                    alert( "Unable to Block Applications");
                    location.replace('../admin/tempScholarship.php');
                  </script>
                <?php
              }
          } else{
            ?>
              <script type="text/javascript">
                alert( "Unable to Block Scholarships And Applications");
                location.replace('../admin/tempScholarship.php');
              </script>
            <?php
          }

        } else if(isset($_POST['blk_unblk']) AND $_POST['blk_unblk'] == "unblockScholarship"){
          $schID = $_POST['schID'];
          $notifyEmail = '';
          $notifyScholarship = '';
          $infoSql = "SELECT S.schname, SIG.upMail FROM scholarship S JOIN signatory SIG ON SIG.sigID = S.sigID WHERE S.scholarshipID = '$schID' LIMIT 1";
          $infoRes = $conn->query($infoSql);
          if ($infoRes && $infoRes->num_rows > 0) {
            $infoRow = $infoRes->fetch_assoc();
            $notifyEmail = $infoRow['upMail'];
            $notifyScholarship = $infoRow['schname'];
          }
          $sch_sql = "UPDATE scholarship SET  adminapproval = previous_adminapproval, schstatus = 'active' WHERE scholarshipID = '$schID'";
          if ($conn->query($sch_sql) === TRUE) {
              $app_sql = "UPDATE application SET appstatus = previous_appstatus, verifiedBySignatory = previous_verifiedBySignatory WHERE scholarshipID = '$schID'";
              if ($conn->query($app_sql) === TRUE) {
                $emailTemplate = email_tpl_scholarship_restored($notifyScholarship);
                $subject = $emailTemplate['subject'];
                $message = $emailTemplate['body'];
                sendNotificationEmail($notifyEmail, $subject, $message);
                ?>
                <script type="text/javascript">
                  alert('Successfully UnBlocked Scholarships and corresponding Applications');
                  location.replace('../admin/tempScholarship.php');
                </script>
              <?php
              } else {
                ?>
                  <script type="text/javascript">
                    alert( "Unable to UnBlock Applications");
                    location.replace('../admin/tempScholarship.php');
                  </script>
                <?php
              }
          } else{
            ?>
              <script type="text/javascript">
                alert( "Unable to UnBlock Scholarships And Applications");
                location.replace('../admin/tempScholarship.php');
              </script>
            <?php
          }
        } else{
          ?>
            <script type="text/javascript">
              alert('Invalid Request');
              location.replace('../admin/tempAdmin.php');
            </script>
          <?php
        }
    ?>

  </body>
</html>
