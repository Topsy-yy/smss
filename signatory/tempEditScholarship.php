<?php

  session_start();
require '../config.php';
//check validity of the user
  $currentUserID=$_SESSION['currentUserID'];
  if($currentUserID==NULL){
    header("Location:../index.php");
  }

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

  </head>

  <body class="app-shell">

  	<script type="text/javascript">
          function fileValidation(name){
              var fileInput = document.getElementById(name);
              var filePath = fileInput.value;
              var allowedExtensions = /(\.pdf)$/i;
              if(!allowedExtensions.exec(filePath)){
                  alert('Please upload file having extensions .pdf only.');
                  fileInput.value = '';
                  return false;
              }else if(fileInput.files[0].size > 8000000){
                alert('File size too large');
                  fileInput.value = '';
                  return false;
              }
              else{ }
          }
          </script>

    <div class="app-page">

			<?php
				$sigNavActive = 'scholarships';
				require __DIR__ . '/../includes/nav-signatory.php';
			?>


			<!-- Main -->
				<article id="main">

					<header class="page-hero container">
					</header>

          <?php
          // EDIT SCHOLARSHIP QUERY CHECK

        	try{
        		 // Connect to database
            	$conn = getDbConnection();

          		// Checks Connection
            	if ($conn->connect_error) {
              		die("Connection failed: " . $conn->connect_error);
            	}
			  $schname = $schlocation = $schlocationfrom = $degree = $gender = $scholarshipp = $appdeadline = NULL;
              $granteesNum = $funding = $description = $eligibility = $benefits = $apply = $links = $contact = $adminapproval = NULL;
			  $target_financial_need = 'Any';
			  $religion = array();
              if(isset($_POST['view'])){
                $schID = $_POST['scholarshipID'];

                $sql = "SELECT * FROM scholarship WHERE scholarshipID = $schID";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                      $schname = $row["schname"];
                			$schlocation = $row["schlocation"];
                			$schlocationfrom = $row["schlocationfrom"];
                			$degree = $row["degree"];
                			$gender = $row["gender"];
                			// $religion = $_POST['religion'];
                			$scholarshipp=$row["sch"];
                			$appdeadline = $row["appDeadline"];
                			$granteesNum = $row["granteesNum"];
                			$funding = $row["funding"];
                			$description = $row["description"];
                			$eligibility = $row["eligibility"];
                			$benefits = $row["benefits"];
                			$apply = $row["apply"];
                			$links = $row["links"];
                			$contact = $row["contact"];
                			$adminapproval = $row["adminapproval"];
		                      $target_financial_need = $row["target_financial_need"] ?? 'Any';

                      $religion = explode(',',$row['religion']);

                }
              }
            }
          } catch(Exception $e){}
        ?>

					<!-- One -->
						<section class="content-card container">

							<!-- Content -->
								<div class="content">
									<section>

										<header>
											<h2 style="padding-left: 33%;"><strong><u>Edit your Scholarship</u></strong></h2>
										</header>

                         				<form method = "post" name = "scholarshiplist" id = "scholarshiplist" action = "../backend/adminAddDelSch.php" enctype="multipart/form-data">
                                    <label style="padding-left : 40%"><strong>Scholarship ID : <?php echo $schID; ?></strong></label><br><br><br>
				                            <label><strong>Scholarship Name</strong></label><br>
				                            <label style="font-size: 15px;">This will be displayed and used for searching your scholarship</label>
				                            <br><input type = "text" name = "schname" style="background-color : #EDF2F2" placeholder="Eg:Joint Japan/World Bank Graduate Scholarship Program 2019" value="<?php echo $schname; ?>" required disabled>
				                            <br><br>

								            <label><strong>Locations</strong></label><br>
				                            <label style="font-size: 15px;">In which states or regions do the students need to study to be able to receive the scholarship?</label>
				                            <br><input type = "text" name = "schlocation" placeholder="Select one or multiple" value="<?php echo $schlocation; ?>">
				                            <br><br>

				                            <label><strong>Locations From</strong></label><br>
				                            <label style="font-size: 15px;">Is this scholarship specific for students from a specific state or region?</label>
				                            <br><input type = "text" name = "schlocationfrom" placeholder="Select one or multiple" value="<?php echo $schlocationfrom; ?>">
				                            <br><br>

				                            <label><strong>Degrees</strong></label><br>
				                            <label style="font-size: 15px;">This is a scholarship to study a ... (check all that apply)</label><br>
				                            <select name="degree" style="padding-top: 10px;padding-bottom: 10px; padding-left: 3% ; padding-right: 3%">
			                                    <option value="select" <?php if($degree === "select") echo "selected" ?>>Select</option>
			                                  
			                                    <option value="diploma" <?php if($degree === "diploma") echo "selected" ?>>Diploma</option>
			                                    <option value="graduation" <?php if($degree === "graduation") echo "selected" ?>>Under Graduate</option>
			                                    <option value="postgraduation" <?php if($degree === "postgraduation") echo "selected" ?>>Post-Graduate</option>
			                                    <option value="phd" <?php if($degree === "phd") echo "selected" ?>>PhD</option>
			                                </select>
				                            <br><br><br>

				                            <label><strong>Gender</strong></label><br>
				                            <label style="font-size: 15px;">This is a scholarship for a particular gender ...</label><br>
				                            <select name="gender" style="padding-top: 10px;padding-bottom: 10px; padding-left: 5%">
			                                    <option value="select" <?php if($gender === "select") echo "selected" ?>>Select</option>
			                                    <option value="male" <?php if($gender === "male") echo "selected" ?>>Male</option>
			                                    <option value="female" <?php if($gender === "female") echo "selected" ?>>Female</option>
			                                    <option value="male+female" <?php if($gender === "male+female") echo "selected" ?>>Both</option>
			                                    
			                                </select>
			                                <br><br><br>

										  <label><strong>Target Financial Need</strong></label><br>
										  <label style="font-size: 15px;">Used by the Matching Engine.</label><br>
										  <select name="target_financial_need" style="padding-top: 10px;padding-bottom: 10px; padding-left: 5%">
											  <option value="Any" <?php if($target_financial_need === 'Any') echo 'selected'; ?>>Any (Open to all)</option>
											  <option value="Low" <?php if($target_financial_need === 'Low') echo 'selected'; ?>>Low Need</option>
											  <option value="Medium" <?php if($target_financial_need === 'Medium') echo 'selected'; ?>>Medium Need</option>
											  <option value="High" <?php if($target_financial_need === 'High') echo 'selected'; ?>>High Need</option>
											  <option value="Critical" <?php if($target_financial_need === 'Critical') echo 'selected'; ?>>Critical Need</option>
										  </select>
										  <br><br><br>

			                                

											<label><strong>Scholarship type</strong></label><br>
				                            <label style="font-size: 15px;">Selct any Type of Scholarship from Below ...</label><br>
				                            <select name="scholarship" style="padding-top: 10px;padding-bottom: 10px; padding-left: 2% ; padding-right: 2%">
			                                    <option value="select" <?php if($scholarshipp === "select") echo "selected" ?>>Select</option>
			                                    <option value="merit_based" <?php if($scholarshipp === "merit_based") echo "selected" ?>>Merit Based</option>
			                                    <option value="means_based" <?php if($scholarshipp === "means_based") echo "selected" ?>>Means Based</option>
			                                    <option value="cultural_talent" <?php if($scholarshipp === "cultural_talent") echo "selected" ?> >Cultural Talent</option>
			                                    <option value="visual_art" <?php if($scholarshipp === "visual_art") echo "selected" ?> >Visual Art</option>
			                                    <option value="sports_talent" <?php if($scholarshipp === "sports_talent") echo "selected" ?>>Sports Talent</option>
			                                    <option value="science_maths_based" <?php if($scholarshipp === "science_maths_based") echo "selected" ?> >Science, Maths Based</option>
			                                    <option value="technology_based" <?php if($scholarshipp === "technology_based") echo "selected" ?> >Technology Based</option>
			                                  </select>
			                                <br><br><br>

				                            <label><strong>Application Deadline</strong></label><br>
				                            <label style="font-size: 15px;">What is the deadline of application?</label>
				                            <br><input type = "date" name = "appdeadline" value="<?php echo $appdeadline; ?>">
				                            <br><br>

				                            <label><strong>Number of Applications maximum allowed</strong></label><br>
				                            <label style="font-size: 15px;">You can limit the number of applicants[This wont be displayed]</label>
				                            <br><input type = "text" name = "granteesNum" value="<?php echo $granteesNum; ?>">
				                            <br><br>

											<label><strong>Funding</strong></label><br>
				                            <label style="font-size: 15px;">Short description about funding. e.g. "$5000,-" or "100% tuition fee"</label>
				                            <br><input type = "text" name = "funding" value="<?php echo $funding; ?>">
				                            <br><br>

											<label><strong>Description</strong></label><br>
				                            <label style="font-size: 15px;">Give a general description of the scholarship. This is the first text that users will read.</label>
				                            <br><textarea name = "description" rows="5" ><?php echo $description; ?></textarea>
				                            <br><br>

											<label><strong>Eligibility</strong></label><br>
				                            <label style="font-size: 15px;">What students are eligible? Are there any requirements?</label>
				                            <br><textarea name = "eligibility" rows="5"><?php echo $eligibility; ?></textarea>
				                            <br><br>

											<label><strong>Benefits</strong></label><br>
				                            <label style="font-size: 15px;">When a student gets the scholarship, what are their benefits?</label>
				                            <br><textarea name = "benefits" rows="5"><?php echo $benefits; ?></textarea>
				                            <br><br>

											<label><strong>How can you apply ?</strong></label><br>
				                            <label style="font-size: 15px;">How should a student apply? What are the requirements for application?</label>
				                            <br><textarea name = "apply" rows="5"><?php echo $apply; ?></textarea>
				                            <br><br>

				                            <label><strong>Important Links</strong></label><br>
				                            <label style="font-size: 15px;">Provide links for your organization and scholarship if any.</label>
				                            <br><textarea name = "links" rows="5"><?php echo $links; ?></textarea>
				                            <br><br>

				                            <label><strong>Contact Details</strong></label><br>
				                            <label style="font-size: 15px;">Email, website, contact info ...</label>
				                            <br><textarea name = "contact" rows="5"><?php echo $contact; ?></textarea>
				                            <br><br>

				                             <label><strong>Upload Document</strong></label>&nbsp;&nbsp;<label style="font-size: 15px;color: red; ">* This is compulsory.</label><br>
				                            <label style="font-size: 15px;">Provide a soft copy of your scholarship so as to validate your scholarship.</label>
				                            <br>
				                            <input type="file" name="validate" id="validate" onchange=" return fileValidation('validate')" required><br>
				                            <br><br>

				                            <input type="hidden" name="scholarshipID" value="<?php echo $schID; ?>">
                                    <input type="hidden" name="schname" value="<?php echo $schname; ?>">
				                            <input type="hidden" name="adminapproval" value="Pending">

                            				<div class = "text-center">
                            				<input type = "submit" name = "deladd" value = "EDIT Scholarship >">
											</div>
										</form>

										<br>
										<div class = "text-center">
											<form action = "tempSigScholarship.php">
												<input type = "submit" value = "Back">
											</form>
										</div>
									</div>

								</section>

							</div>

						</section>

				</article>

			<!-- Footer -->
				<footer id="footer"><ul class="copyright">
					</ul>

				</footer>

		</div>

		<!-- Scripts -->
      <script src="../js/jquery.min.js"></script>
    <script type="text/javascript">
    function selectAll(){
      sel = document.getElementById("selSigList");
      for (var i = 0; i < sel.options.length; i++){
        sel.options[i].selected = true;
      }
    }

    </script>
  </body>
</html>
