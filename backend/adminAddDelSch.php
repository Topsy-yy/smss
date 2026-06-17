<?php
  session_start();
require '../config.php';
//check validity of the user
  $currentUserID=$_SESSION['currentUserID'];
  if($currentUserID==NULL){
    header("Location:../index.php");
  }
?>
<!DOCTYPE HTML>
<html>
  	<head></head>
 	<body>
 	<?php

	try{
		 // Connect to database
    	$conn = getDbConnection();

  		// Checks Connection
    	if ($conn->connect_error) {
      		die("Connection failed: " . $conn->connect_error);
    	}

		$postOrEmpty = function($key) {
			return trim($_POST[$key] ?? '');
		};

		$action = trim($_POST['deladd'] ?? '');
		$hasScholarshipId = !empty($_POST['scholarshipID']);
		$isAddAction = ($action === 'Submit Scholarship >' || $action === 'Submit For Approval' || $action === 'on' || ($action === '' && !$hasScholarshipId));
		$isEditAction = ($action === 'EDIT Scholarship >');

		/*If the add button was clicked*/
		if($isAddAction){

			$flag=0;
			$name = $postOrEmpty('schname');
			$schlocation = $postOrEmpty('schlocation');
			$schlocationfrom = $postOrEmpty('schlocationfrom');
			$degree = $postOrEmpty('degree');
			$gender = $postOrEmpty('gender');
			$targetFinancialNeed = $postOrEmpty('target_financial_need');
			// $religion = $_POST['religion'];
			$scholarshipp=$postOrEmpty('scholarship');
			$appdeadline = $postOrEmpty('appdeadline');
			$granteesNum = (int) $postOrEmpty('granteesNum');
			$funding = $postOrEmpty('funding');
			$description = $postOrEmpty('description');
			$eligibility = $postOrEmpty('eligibility');
			$benefits = $postOrEmpty('benefits');
			$apply = $postOrEmpty('apply');
			$links = $postOrEmpty('links');
			$contact = $postOrEmpty('contact');
			$adminapproval = $postOrEmpty('adminapproval');
			$schID = 0;

	// Religion field was removed from the form; keep empty value for current DB schema.
	$religionn = '';


			$sql = "INSERT INTO scholarship (sigID,schname, schlocation,schlocationfrom,degree, gender, religion, target_financial_need, sch, appDeadline, granteesNum, funding, description, eligibility, benefits, apply, links, contact, adminapproval, previous_adminapproval) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
			$stmt = $conn->prepare($sql);
			if ($stmt === false) {
				$flag = 0;
				echo "Error preparing statement: " . $conn->error;
			} else {
				$stmt->bind_param(
					"isssssssssisssssssss",
					$currentUserID,
					$name,
					$schlocation,
					$schlocationfrom,
					$degree,
					$gender,
					$religionn,
					$targetFinancialNeed,
					$scholarshipp,
					$appdeadline,
					$granteesNum,
					$funding,
					$description,
					$eligibility,
					$benefits,
					$apply,
					$links,
					$contact,
					$adminapproval,
					$adminapproval
				);

				if ($stmt->execute()) {
					$schID = $conn->insert_id;
        }
      }

			if (!empty($schID)) {
        $xml = new DOMDocument("1.0","UTF-8");
			  $xml->load("scholarship_data.xml");
  			$rootTag = $xml->getElementsByTagName("scholarships")->item(0);
  			$dataTag = $xml->createElement("scholarship");

  			$sigidtag = $xml->createElement("sigID",$currentUserID);
  			$schnametag = $xml->createElement("schname",$name);
        $schlocationtag = $xml->createElement("schlocation",$schlocation);
        $schlocationfromtag = $xml->createElement("schlocationfrom",$schlocationfrom);
        $degreetag = $xml->createElement("degree",$degree);
        $gendertag = $xml->createElement("gender",$gender);
		$targetFinancialNeedTag = $xml->createElement("target_financial_need",$targetFinancialNeed);
        $religiontag = $xml->createElement("religion",$religionn);
        $schtag = $xml->createElement("sch",$scholarshipp);
        $appDeadlinetag = $xml->createElement("appDeadline",$appdeadline);
        $granteesNumtag = $xml->createElement("granteesNum",$granteesNum);
        $fundingtag = $xml->createElement("funding",$funding);
        $descriptiontag = $xml->createElement("description",$description);
        $eligibilitytag = $xml->createElement("eligibility",$eligibility);
        $benefitstag = $xml->createElement("benefits",$benefits);
        $applytag = $xml->createElement("apply",$apply);
        $linkstag = $xml->createElement("links",$links);
        $contacttag = $xml->createElement("contact",$contact);

  			$dataTag -> appendChild($sigidtag);
  			$dataTag -> appendChild($schnametag);
        $dataTag -> appendChild($schlocationtag);
        $dataTag -> appendChild($schlocationfromtag);
        $dataTag -> appendChild($degreetag);
        $dataTag -> appendChild($gendertag);
		$dataTag -> appendChild($targetFinancialNeedTag);
        $dataTag -> appendChild($religiontag);
        $dataTag -> appendChild($schtag);
        $dataTag -> appendChild($appDeadlinetag);
        $dataTag -> appendChild($granteesNumtag);
        $dataTag -> appendChild($fundingtag);
        $dataTag -> appendChild($descriptiontag);
        $dataTag -> appendChild($eligibilitytag);
        $dataTag -> appendChild($benefitstag);
        $dataTag -> appendChild($applytag);
        $dataTag -> appendChild($linkstag);
        $dataTag -> appendChild($contacttag);

  			$dataTag->setAttribute("scholarshipID",$schID);

  			$rootTag->appendChild($dataTag);
  			$xml->save("scholarship_data.xml");

    		$flag=1;
			} else {
				$flag=0;
	    		echo "Error inserting scholarship: " . ((isset($stmt) && $stmt instanceof mysqli_stmt) ? $stmt->error : $conn->error);
			}
			if (isset($stmt) && $stmt instanceof mysqli_stmt) {
				$stmt->close();
			}
			if($flag==1){
			$folder=$schID;
			mkdir("../scholarship/$folder/");
				if(is_uploaded_file($_FILES['validate']['tmp_name'])) {

	      		    //move_uploaded_file
				    copy($_FILES["validate"]["tmp_name"],"../scholarship/$folder/" . $_FILES["validate"]["name"]);
				    $fileupload = '1';
				}
  			if($fileupload=='1'){
  				 ?>
  				    <script type="text/javascript">
                			alert("Scholarship is added and will be further processed by Admin to validate!");
                			location.replace("../signatory/tempSigScholarship.php")
              		</script>
  			  	<?php
  			}
  		}
		}
		else if($isEditAction){
			//Update Query [Same as insert]

      $flag=0;
	$schID = (int) ($_POST['scholarshipID'] ?? 0);
			$name = $postOrEmpty('schname');
			$schlocation = $postOrEmpty('schlocation');
			$schlocationfrom = $postOrEmpty('schlocationfrom');
			$degree = $postOrEmpty('degree');
			$gender = $postOrEmpty('gender');
			$targetFinancialNeed = $postOrEmpty('target_financial_need');
			// $religion = $_POST['religion'];
			$scholarshipp=$postOrEmpty('scholarship');
			$appdeadline = $postOrEmpty('appdeadline');
			$granteesNum = (int) $postOrEmpty('granteesNum');
			$funding = $postOrEmpty('funding');
			$description = $postOrEmpty('description');
			$eligibility = $postOrEmpty('eligibility');
			$benefits = $postOrEmpty('benefits');
			$apply = $postOrEmpty('apply');
			$links = $postOrEmpty('links');
			$contact = $postOrEmpty('contact');
			$adminapproval = $postOrEmpty('adminapproval');

	// Religion field was removed from the form; keep empty value for current DB schema.
	$religionn = '';


			$sql = "UPDATE scholarship SET schlocation = ?,schlocationfrom = ?,
			  degree = ?,gender = ?, religion = ?, target_financial_need = ?, sch = ?, appDeadline = ?,
              granteesNum = ?, funding = ?, description = ?, eligibility = ?,
              benefits = ?, apply = ?, links = ?, contact = ?, adminapproval = ?
              WHERE scholarshipID = ? ";
			$stmt = $conn->prepare($sql);
			if ($stmt === false) {
				$flag = 0;
				echo "Error preparing update statement: " . $conn->error;
			} else {
				$stmt->bind_param(
					"ssssssssissssssssi",
					$schlocation,
					$schlocationfrom,
					$degree,
					$gender,
					$religionn,
					$targetFinancialNeed,
					$scholarshipp,
					$appdeadline,
					$granteesNum,
					$funding,
					$description,
					$eligibility,
					$benefits,
					$apply,
					$links,
					$contact,
					$adminapproval,
					$schID
				);

				if ($stmt->execute()) {
        $xml=simplexml_load_file("scholarship_data.xml") or die("Error: Cannot create object");
        foreach($xml->children() as $scholarship){
            if($scholarship['scholarshipID'] == $schID){
              $scholarship->{'schlocation'} = $schlocation;
              $scholarship->{'schlocationfrom'} = $schlocationfrom;
              $scholarship->{'degree'} = $degree;
              $scholarship->{'gender'} = $gender;
              $scholarship->{'religion'} = $religionn;
			  $scholarship->{'target_financial_need'} = $targetFinancialNeed;
              $scholarship->{'sch'} = $scholarshipp;
              $scholarship->{'appDeadline'} = $appdeadline;
              $scholarship->{'granteesNum'} = $granteesNum;
              $scholarship->{'funding'} = $funding;
              $scholarship->{'description'} = $description;
              $scholarship->{'eligibility'} = $eligibility;
              $scholarship->{'benefits'} = $benefits;
              $scholarship->{'apply'} = $apply;
              $scholarship->{'links'} = $links;
              $scholarship->{'contact'} = $contact;
            }
          }
          $xml->asXml('scholarship_data.xml');

	    			$flag=1;
				} else {
					$flag=0;
	    			echo "Error updating scholarship: " . $stmt->error;
				}
				$stmt->close();
			}

			if($flag==1){
			$folder=$schID;
      echo $folder;
			$dir = "../scholarship/$folder/";
      if (is_dir($dir)){
        $files = glob($dir . '/*');
        foreach($files as $file){
            if(is_file($file)){
                unlink($file);
            }
        }

				if(is_uploaded_file($_FILES['validate']['tmp_name'])) {
	      		    //move_uploaded_file
				    copy($_FILES["validate"]["tmp_name"],"../scholarship/$folder/" . $_FILES["validate"]["name"]);
				    $fileupload = '1';
				} else{
          echo "CP";
        }
  			if($fileupload=='1'){
  				 ?>
  				    <script type="text/javascript">
                			alert("Scholarship is Updated and will be further processed by Admin to validate!");
                			location.replace("../signatory/tempSigScholarship.php")
              		</script>
  			  	<?php
  			}else{
          echo "CPFU";
        }
  		} else{
        echo "dir not found";
      }

		}
		$conn->close();
	}
}
	catch(Exception $e){
		echo $e->getMessage();
	}
?>
	</body>
</html>
