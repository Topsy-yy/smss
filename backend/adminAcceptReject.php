<!DOCTYPE HTML>

<html>
  <head>
  </head>
  <body>
<?php
require '../config.php';
require_once 'notification_mailer.php';
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
				$subject = 'Scholarship Approved - ' . $notifyScholarship;
				$message = '<h3>Scholarship Approved</h3><p>Your scholarship <strong>' . htmlspecialchars($notifyScholarship, ENT_QUOTES, 'UTF-8') . '</strong> has been approved by Admin and is now visible to students.</p><p>You can sign in to review applications.</p>';
				sendNotificationEmail($notifyEmail, $subject, $message);
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
				$subject = 'Scholarship Rejected - ' . $notifyScholarship;
				$message = '<h3>Scholarship Rejected</h3><p>Your scholarship <strong>' . htmlspecialchars($notifyScholarship, ENT_QUOTES, 'UTF-8') . '</strong> was rejected by Admin.</p><p>Please review and update the listing before re-submitting.</p>';
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
