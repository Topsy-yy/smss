<!DOCTYPE HTML>
<html>
  <head>
  </head>
  <body>

  	<?php
	session_start();
require '../config.php';
require_once 'notification_mailer.php';
$currentUserID=$_SESSION['currentUserID'];
  	if($currentUserID==NULL){
    	header("Location:../index.php");
  	}

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
			$appstatus = NULL;
			$verifiedBySignatory = NULL;
			$appID=$_POST['appID'];
			$notifyEmail = '';
			$notifyStudentName = 'Student';
			$notifyScholarship = 'your scholarship application';
			$notifySql = "SELECT ST.upMail, ST.firstName, ST.lastName, SC.schname FROM application A JOIN student ST ON ST.studentID = A.studentID JOIN scholarship SC ON SC.scholarshipID = A.scholarshipID WHERE A.applicationID = $appID LIMIT 1";
			$notifyRes = $conn->query($notifySql);
			if ($notifyRes && $notifyRes->num_rows > 0) {
				$notifyRow = $notifyRes->fetch_assoc();
				$notifyEmail = $notifyRow['upMail'];
				$notifyStudentName = trim(($notifyRow['firstName'] ?? '') . ' ' . ($notifyRow['lastName'] ?? ''));
				if ($notifyStudentName === '') { $notifyStudentName = 'Student'; }
				$notifyScholarship = $notifyRow['schname'] ?: $notifyScholarship;
			}
			$sql = "SELECT * FROM application WHERE applicationID = $appID";
			$result = $conn->query($sql);
			if ($result->num_rows > 0) {
    		while($row = $result->fetch_assoc()) {
					$appstatus = $row['appstatus'];
					$verifiedBySignatory = $row['verifiedBySignatory'];
				}
			} else{
				?>
		 			<script type="text/javascript">
		 				alert('Error');
		 				location.replace("../tempSigApplication.php");
		 			</script>
	 			<?php
			}
			if($appstatus !== 'inactive'){
					$sql = "UPDATE `application` SET `appstatus` = 'Processing', `verifiedBySignatory` = 'Approved' WHERE `application`.`applicationID` = $appID;";
					if ($conn->query($sql) === TRUE) {
						$subject = 'Application Approved - ' . $notifyScholarship;
						$message = '<h3>Application Approved</h3><p>Hello ' . htmlspecialchars($notifyStudentName, ENT_QUOTES, 'UTF-8') . ',</p><p>Your application for <strong>' . htmlspecialchars($notifyScholarship, ENT_QUOTES, 'UTF-8') . '</strong> has been approved by the signatory and is now in processing.</p>';
						sendNotificationEmail($notifyEmail, $subject, $message);
				 ?>
					<script type="text/javascript">
						alert('Application is in Accepted and Processing Mode now!');
						location.replace("../signatory/tempSigApplication.php");
					</script>
				<?php

					} else {
				 ?>
					<script type="text/javascript">
						alert('Error updating record');
						location.replace("../signatory/tempSigApplication.php");
					</script>
				<?php
					}
			} else{
				?>
				 <script type="text/javascript">
					 alert('Cannot Approve.\nThe Application is in inactive Mode');
					 location.replace("../signatory/tempSigApplication.php");
				 </script>
			 <?php
			}
		}

		/*If the reject button was clicked*/
		else if($_POST['accrej'] == 'Reject') {
			$appstatus = NULL;
			$verifiedBySignatory = NULL;
			$appID=$_POST['appID'];
			$notifyEmail = '';
			$notifyStudentName = 'Student';
			$notifyScholarship = 'your scholarship application';
			$notifySql = "SELECT ST.upMail, ST.firstName, ST.lastName, SC.schname FROM application A JOIN student ST ON ST.studentID = A.studentID JOIN scholarship SC ON SC.scholarshipID = A.scholarshipID WHERE A.applicationID = $appID LIMIT 1";
			$notifyRes = $conn->query($notifySql);
			if ($notifyRes && $notifyRes->num_rows > 0) {
				$notifyRow = $notifyRes->fetch_assoc();
				$notifyEmail = $notifyRow['upMail'];
				$notifyStudentName = trim(($notifyRow['firstName'] ?? '') . ' ' . ($notifyRow['lastName'] ?? ''));
				if ($notifyStudentName === '') { $notifyStudentName = 'Student'; }
				$notifyScholarship = $notifyRow['schname'] ?: $notifyScholarship;
			}
			$sql = "SELECT * FROM application WHERE applicationID = $appID";
			$result = $conn->query($sql);
			if ($result->num_rows > 0) {
    		while($row = $result->fetch_assoc()) {
					$appstatus = $row['appstatus'];
					$verifiedBySignatory = $row['verifiedBySignatory'];
				}
			} else{
				?>
		 			<script type="text/javascript">
		 				alert('Error');
		 				location.replace("../signatory/tempSigApplication.php");
		 			</script>
	 			<?php
			}
			if($appstatus !== 'inactive'){
					$sql = "UPDATE `application` SET `appstatus` = 'Rejected', `verifiedBySignatory` = 'Rejected' WHERE `application`.`applicationID` = $appID;";
					if ($conn->query($sql) === TRUE) {
						$subject = 'Application Rejected - ' . $notifyScholarship;
						$message = '<h3>Application Rejected</h3><p>Hello ' . htmlspecialchars($notifyStudentName, ENT_QUOTES, 'UTF-8') . ',</p><p>Your application for <strong>' . htmlspecialchars($notifyScholarship, ENT_QUOTES, 'UTF-8') . '</strong> has been rejected by the signatory.</p><p>You may review other opportunities and apply again where eligible.</p>';
						sendNotificationEmail($notifyEmail, $subject, $message);
				 ?>
					<script type="text/javascript">
						alert('Application is in Rejected Mode now!');
						location.replace("../signatory/tempSigApplication.php");
					</script>
				<?php

					} else {
				 ?>
					<script type="text/javascript">
						alert('Error updating record');
						location.replace("../signatory/tempSigApplication.php");
					</script>
				<?php
					}
			} else{
				?>
				 <script type="text/javascript">
					 alert('Cannot Reject.\nThe Application is in inactive Mode');
					 location.replace("../signatory/tempSigApplication.php");
				 </script>
			 <?php
			}
		}
	}catch(PDOException $e){
		echo $e->getMessage();
	}
?>
	</body>
</html>
