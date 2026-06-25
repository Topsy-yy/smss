<!DOCTYPE HTML>

<html>
  <head>
  </head>
  <body>
<?php
require '../config.php';
require_once 'notification_mailer.php';
require_once 'email_templates.php';
require_once 'SmsService.php';
require_once 'MatchingEngine.php';
try{
		/*Open a connection to mySQL*/
		// Connect to database
    	$conn = getDbConnection();

		  // Checks Connection
	    if ($conn->connect_error) {
	      die("Connection failed: " . $conn->connect_error);
	    }

		/*If the accept button was clicked*/
		if ($_POST['accrej'] == 'Accept'){
			$schID=(int)$_POST['schID'];
			$notifyEmail = '';
			$notifyScholarship = '';
			$infoSql = "SELECT S.schname, SIG.upMail FROM scholarship S JOIN signatory SIG ON SIG.sigID = S.sigID WHERE S.scholarshipID = $schID LIMIT 1";
			$infoRes = $conn->query($infoSql);
			if ($infoRes && $infoRes->num_rows > 0) {
				$infoRow = $infoRes->fetch_assoc();
				$notifyEmail = $infoRow['upMail'];
				$notifyScholarship = $infoRow['schname'];
			}
			$sql = "UPDATE `scholarship` SET `adminapproval` = 'Approved' WHERE `scholarship`.`scholarshipID` = $schID;";
			if ($conn->query($sql) === TRUE) {
				$emailTemplate = email_tpl_scholarship_approved($notifyScholarship);
				$subject = $emailTemplate['subject'];
				$message = $emailTemplate['body'];
				sendNotificationEmail($notifyEmail, $subject, $message);

                // --- SMS Notifications for matching students ---
                $matchedStudents = MatchingEngine::getMatchedStudentsForScholarship($schID);
                $phones = [];
                foreach ($matchedStudents as $ms) {
                    if (!empty($ms['phone'])) {
                        $phones[] = $ms['phone'];
                    }
                }
                if (!empty($phones)) {
                    $smsMsg = "New Scholarship Alert! '{$notifyScholarship}' matches your profile. Log in to ScholarConnect to apply.";
                    SmsService::sendSms($phones, $smsMsg);
                }
		 ?>
			<script type="text/javascript">
				alert('Scholarship is Accepted!');
				location.replace("../admin/tempScholarship.php");
			</script>
		<?php

			} else {
		 ?>
			<script type="text/javascript">
				alert('Error updating record');
				location.replace("../admin/tempScholarship.php");
			</script>
		<?php
			}
		}

		/*If the reject button was clicked*/
		else if($_POST['accrej'] == 'Reject'){
			$schID=(int)$_POST['schID'];
			$notifyEmail = '';
			$notifyScholarship = '';
			$infoSql = "SELECT S.schname, SIG.upMail FROM scholarship S JOIN signatory SIG ON SIG.sigID = S.sigID WHERE S.scholarshipID = $schID LIMIT 1";
			$infoRes = $conn->query($infoSql);
			if ($infoRes && $infoRes->num_rows > 0) {
				$infoRow = $infoRes->fetch_assoc();
				$notifyEmail = $infoRow['upMail'];
				$notifyScholarship = $infoRow['schname'];
			}
			$sql = "UPDATE `scholarship` SET `adminapproval` = 'Rejected' WHERE `scholarship`.`scholarshipID` = $schID;";
			if ($conn->query($sql) === TRUE) {
				$emailTemplate = email_tpl_scholarship_rejected($notifyScholarship);
				$subject = $emailTemplate['subject'];
				$message = $emailTemplate['body'];
				sendNotificationEmail($notifyEmail, $subject, $message);
		 ?>
			<script type="text/javascript">
				alert('Scholarship is Rejected!');
				location.replace("../admin/tempScholarship.php");
			</script>
		<?php

			} else {
		 ?>
			<script type="text/javascript">
				alert('Error updating record');
				location.replace("../admin/tempScholarship.php");
			</script>
		<?php
			}
		}
	}

	catch(PDOException $e){
		echo $e->getMessage();
	}
?>
	</body>
</html>
