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
$conn = getDbConnection();
      if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
      }

        if(isset($_POST['unblockUser']) AND $_POST['unblockUser'] == "unblockStudent"){
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
            $student_sql = "UPDATE student SET status = 'active' WHERE studentID = '$studentID'";
            if ($conn->query($student_sql) === TRUE) {
              $app_sql = "UPDATE application SET appstatus = previous_appstatus, verifiedBySignatory = previous_verifiedBySignatory WHERE studentID = '$studentID'";
              if ($conn->query($app_sql) === TRUE) {
                $subject = 'Account Restored - ScholarConnect';
                $message = '<h3>Account Restored</h3><p>Hello ' . htmlspecialchars($notifyName, ENT_QUOTES, 'UTF-8') . ',</p><p>Your account has been <strong>unblocked</strong> by Admin. Your related applications were restored to their previous states.</p>';
                sendNotificationEmail($notifyEmail, $subject, $message);
              ?>
                <script type="text/javascript">
                  alert('Successfully Unblocked the Student and corresponding Applicaitons');
                  location.replace('../admin/tempStudentShow.php');
                </script>
              <?php
              } else {
                ?>
                  <script type="text/javascript">
                    alert( "Unable to Unblock Applications");
                    location.replace('../admin/tempStudentShow.php');
                  </script>
                <?php
              }
            } else {
            ?>
              <script type="text/javascript">
                alert( "Unable to Unblock Student");
                location.replace('../admin/tempStudentShow.php');
              </script>
            <?php
          }


        } else if(isset($_POST['unblockUser']) AND $_POST['unblockUser'] == "unblockSig"){
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
          $sig_sql = "UPDATE signatory SET status = 'active' WHERE sigID = '$sigID'";
          if ($conn->query($sig_sql) === TRUE) {
            $sch_sql = "UPDATE scholarship SET  adminapproval = previous_adminapproval, schstatus = 'active' WHERE sigID = '$sigID'";
            if ($conn->query($sch_sql) === TRUE) {
                $app_sql = "UPDATE application SET appstatus = previous_appstatus, verifiedBySignatory = previous_verifiedBySignatory WHERE sigID = '$sigID'";
                if ($conn->query($app_sql) === TRUE) {
                  $subject = 'Account Restored - ScholarConnect';
                  $message = '<h3>Account Restored</h3><p>Hello ' . htmlspecialchars($notifyName, ENT_QUOTES, 'UTF-8') . ',</p><p>Your signatory account has been <strong>unblocked</strong> by Admin. Related scholarships and applications were restored.</p>';
                  sendNotificationEmail($notifyEmail, $subject, $message);
                  ?>
                  <script type="text/javascript">
                    alert('Successfully UnBlocked the Signatory, corresponding Scholarships and Applications');
                    location.replace('../admin/tempSignatoryShow.php');
                  </script>
                <?php
                } else {
                  ?>
                    <script type="text/javascript">
                      alert( "Unable to UnBlock Applications");
                      location.replace('../admin/tempSignatoryShow.php');
                    </script>
                  <?php
                }
            } else{
              ?>
                <script type="text/javascript">
                  alert( "Unable to UnBlock Scholarships And Applications");
                  location.replace('../admin/tempSignatoryShow.php');
                </script>
              <?php
            }
          } else {
          ?>
            <script type="text/javascript">
              alert( "Unable to UnBlock Signatory");
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
