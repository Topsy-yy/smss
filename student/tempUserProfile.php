<?php
  session_start();
  require '../config.php';
  $_SESSION['selectedAppID'] = 0;
  $_SESSION['currentUserName'] = NULL;
  $_SESSION['appList'] = NULL;

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

  //Getting Name
  $getName = "select S.firstName, S.middleName, S.lastName from student S where S.studentID = '".$_SESSION['currentUserID']."'";
  $nameResult = mysqli_query($conn,$getName);
  while($rows9=mysqli_fetch_row($nameResult)){
    foreach ($rows9 as $key => $value){
      if($key == 0){
        $_SESSION['currentUserName'] = $value;
      }
      if($key == 1){
        $_SESSION['currentUserName'] = $_SESSION['currentUserName'] . " " . $value;
      }
      if($key == 2){
        $_SESSION['currentUserName'] = $_SESSION['currentUserName'] . ". " . $value;
      }
    }
  }

  $upMail=$firstName=$lastName=$middleName=$nationality=$gender=$birthPlace=$presStreetAddr=$presProvCity=$presRegion=$permProvCity=$permStreetAddr=$permRegion=$contactNo=$dept=$college=$birthDate= NULL;
  // NEW MATCHING ENGINE VARIABLES
  $current_level = $financial_need = $career_interests = NULL;
  
  //Get User Details
  $sql = "SELECT * FROM student WHERE studentID = '".$_SESSION['currentUserID']."'";
  $result = $conn->query($sql);
  while($row = $result->fetch_assoc()) {
    $upMail = $row["upMail"];
    $firstName = $row["firstName"];
    $lastName = $row["lastName"];
    $middleName = $row["middleName"];
    $nationality = $row["nationality"];
    $gender = $row["gender"];
    $birthDate = $row["birthDate"];
    $birthPlace = $row["birthPlace"];
    $presStreetAddr = $row["presStreetAddr"];
    $presProvCity = $row["presProvCity"];
    $presRegion = $row["presRegion"];
    $permStreetAddr = $row["permStreetAddr"];
    $permProvCity = $row["permProvCity"];
    $permRegion = $row["permRegion"];
    $contactNo = $row["contactNo"];
    $dept = $row["dept"];
    $college = $row["college"];
    
    // Assign Matching Variables
    $current_level = $row["current_level"] ?? '';
    $financial_need = $row["financial_need"] ?? '';
    $career_interests = $row["career_interests"] ?? '';
  }

  $careerInterestSelections = array_filter(array_map('trim', explode(',', (string) $career_interests)));
?>

<!DOCTYPE HTML>
<html>
  <head>
      <title>User Profile | ScholarConnect</title>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      
      <!-- Bootstrap Core CSS -->
      <link href="../css/bootstrap.min.css" rel="stylesheet">

      <!-- Custom CSS -->
      <link href="../css/user.css" rel="stylesheet">
      <link href="../css/pages/student.css" rel="stylesheet">
      <link href="../css/pages/student-dashboard.css" rel="stylesheet">
  </head>

  <body class="app-shell">
    <div class="app-page">

      <!-- Header -->
        <?php
          $studentNavCurrent = 'profile';
          require '../includes/nav-student.php';
        ?>

      <!-- Main -->
        <article id="main">
          <header class="page-hero container"></header>

          <!-- One -->
            <section class="content-card container">
              <!-- Content -->
                <div class="content">
                  <section>
                    <header><h1><b style="margin: 10% 0% 0% 42%;">User Profile</b></h1></header>
                    
                    <!-- DISPLAY MODE -->
                    <div id="display">
                        <form method="post" action="../backend/userdata.php" class="form-horizontal" role="form">

                            <!-- Core Identifiers -->
                            <?php if(!empty($upMail)){ ?>
                              <div class="form-group">
                                <label class="control-label col-sm-2" for="upMail">Email:</label>
                                <div class="col-sm-10"><input type="email" class="form-control" value="<?php echo $upMail;?>" disabled></div>
                              </div>
                            <?php } ?>

                            <?php if(!empty($lastName)){ ?>
                              <div class="form-group">
                                <label class="control-label col-sm-2" for="lastName">Last Name:</label>
                                <div class="col-sm-10"><input type="text" class="form-control" value="<?php echo $lastName;?>" disabled></div>
                              </div>
                            <?php } ?>

                            <?php if(!empty($firstName)){ ?>
                            <div class="form-group">
                              <label class="control-label col-sm-2" for="firstName">First Name:</label>
                              <div class="col-sm-10"><input type="text" class="form-control" value="<?php echo $firstName?>" disabled></div>
                            </div>
                            <?php } ?>

                            <!-- ========================================== -->
                            <!-- NEW: MATCHING ENGINE PROFILE DISPLAY       -->
                            <!-- ========================================== -->
                            <hr style="border-top: 1px dashed #cbd5e1; margin: 25px 0;">
                            <h4 style="margin-left: 17%; color: var(--brand-primary); margin-bottom: 20px;">Matching Profile Data</h4>

                            <?php if(!empty($current_level)){ ?>
                            <div class="form-group">
                              <label class="control-label col-sm-2">Education Level:</label>
                              <div class="col-sm-10"><input type="text" class="form-control" value="<?php echo ucfirst($current_level)?>" disabled></div>
                            </div>
                            <?php } ?>

                            <?php if(!empty($financial_need)){ ?>
                            <div class="form-group">
                              <label class="control-label col-sm-2">Financial Need:</label>
                              <div class="col-sm-10"><input type="text" class="form-control" value="<?php echo $financial_need?>" disabled></div>
                            </div>
                            <?php } ?>

                            <?php if(!empty($career_interests)){ ?>
                            <div class="form-group">
                              <label class="control-label col-sm-2">Career Interests:</label>
                              <div class="col-sm-10"><input type="text" class="form-control" value="<?php echo $career_interests?>" disabled></div>
                            </div>
                            <?php } ?>
                            <!-- ========================================== -->

                            <!-- Other Info -->
                            <?php if(!empty($gender)){ ?>
                            <div class="form-group">
                              <label class="control-label col-sm-2" for="gender">Gender:</label>
                              <div class="col-sm-10"><input type="text" class="form-control" value="<?php echo $gender?>" disabled></div>
                            </div>
                            <?php } ?>

                            <?php if(!empty($contactNo) && $contactNo != '0'){ ?>
                            <div class="form-group">
                              <label class="control-label col-sm-2" for="contactNo">Contact Number:</label>
                              <div class="col-sm-10"><input type="text" class="form-control" value="<?php echo $contactNo?>" disabled></div>
                            </div>
                            <?php } ?>

                        </form>
                        <button id="showDivButton" style="margin:2% 0% 3% 42%;" type="button" class="btn btn-primary">Edit User Profile</button>
                    </div>

                    <!-- EDIT MODE -->
                    <div id="editDiv" style="display:none">
                        <form method="POST" action="../backend/userdata.php" class="form-horizontal" role="form">
                            
                            <div class="form-group">
                              <label class="control-label col-sm-2">Email:</label>
                              <div class="col-sm-10">
                                <input type="email" class="form-control" value="<?php echo $upMail ?>" disabled>
                              </div>
                            </div>
                            <div class="form-group">
                              <label class="control-label col-sm-2">Last Name:</label>
                              <div class="col-sm-10">
                                <input type="text" class="form-control" name="lastName" value="<?php echo $lastName;?>">
                              </div>
                            </div>
                            <div class="form-group">
                              <label class="control-label col-sm-2">First Name:</label>
                              <div class="col-sm-10">
                                <input type="text" class="form-control" name="firstName" value="<?php echo $firstName?>">
                              </div>
                            </div>
                            <div class="form-group">
                              <label class="control-label col-sm-2">Middle Name:</label>
                              <div class="col-sm-10">
                                <input type="text" class="form-control" name="middleName" value="<?php echo $middleName?>">
                              </div>
                            </div>

                            <!-- ========================================== -->
                            <!-- NEW: MATCHING ENGINE PROFILE EDIT          -->
                            <!-- ========================================== -->
                            <hr style="border-top: 1px dashed #cbd5e1; margin: 25px 0;">
                            <h4 style="margin-left: 17%; color: var(--brand-primary); margin-bottom: 20px;">Matching Profile Data</h4>

                            <div class="form-group">
                              <label class="control-label col-sm-2">Education Level:</label>
                              <div class="col-sm-10">
                                <select class="form-control" name="current_level" style="height: auto;">
                                    <option value="">Select Level</option>
                                    <option value="high school" <?php echo (strtolower($current_level)=='high school')?'selected':'';?>>High School</option>
                                    <option value="diploma" <?php echo (strtolower($current_level)=='diploma')?'selected':'';?>>Diploma</option>
                                    <option value="undergraduate" <?php echo (strtolower($current_level)=='undergraduate')?'selected':'';?>>Undergraduate</option>
                                    <option value="postgraduate" <?php echo (strtolower($current_level)=='postgraduate')?'selected':'';?>>Postgraduate</option>
                                    <option value="phd" <?php echo (strtolower($current_level)=='phd')?'selected':'';?>>PhD</option>
                                </select>
                              </div>
                            </div>

                            <div class="form-group">
                              <label class="control-label col-sm-2">Financial Need:</label>
                              <div class="col-sm-10">
                                <select class="form-control" name="financial_need" style="height: auto;">
                                    <option value="">Select Need Level</option>
                                    <option value="Low" <?php echo ($financial_need=='Low')?'selected':'';?>>Low Need</option>
                                    <option value="Medium" <?php echo ($financial_need=='Medium')?'selected':'';?>>Medium Need</option>
                                    <option value="High" <?php echo ($financial_need=='High')?'selected':'';?>>High Need</option>
                                    <option value="Critical" <?php echo ($financial_need=='Critical')?'selected':'';?>>Critical Need</option>
                                </select>
                              </div>
                            </div>

                            <div class="form-group">
                              <label class="control-label col-sm-2">Career Interests:</label>
                              <div class="col-sm-10">
                                <div style="border: 1px solid #d8dee6; border-radius: 6px; padding: 10px 14px; background: #fff;">
                                  <label style="display:block; font-weight: 500;"><input type="checkbox" name="career_interests[]" value="Cultural / Arts" <?php echo in_array('Cultural / Arts', $careerInterestSelections) ? 'checked' : ''; ?>> Cultural / Arts</label>
                                  <label style="display:block; font-weight: 500;"><input type="checkbox" name="career_interests[]" value="Visual Art" <?php echo in_array('Visual Art', $careerInterestSelections) ? 'checked' : ''; ?>> Visual Art</label>
                                  <label style="display:block; font-weight: 500;"><input type="checkbox" name="career_interests[]" value="Sports Talent" <?php echo in_array('Sports Talent', $careerInterestSelections) ? 'checked' : ''; ?>> Sports Talent</label>
                                  <label style="display:block; font-weight: 500;"><input type="checkbox" name="career_interests[]" value="Science & Maths" <?php echo in_array('Science & Maths', $careerInterestSelections) ? 'checked' : ''; ?>> Science & Maths</label>
                                  <label style="display:block; font-weight: 500; margin-bottom: 0;"><input type="checkbox" name="career_interests[]" value="Technology Based" <?php echo in_array('Technology Based', $careerInterestSelections) ? 'checked' : ''; ?>> Technology Based</label>
                                </div>
                                <small style="color: #64748b;">You can select more than one interest.</small>
                              </div>
                            </div>
                            <hr style="border-top: 1px dashed #cbd5e1; margin: 25px 0;">
                            <!-- ========================================== -->

                            <div class="form-group">
                              <label class="control-label col-sm-2">Gender:</label>
                              <div class="col-sm-10">
                                <select class="form-control" name="gender" style="height: auto;">
                                    <option value="">Select Gender</option>
                                    <option value="male" <?php echo (strtolower($gender)=='male')?'selected':'';?>>Male</option>
                                    <option value="female" <?php echo (strtolower($gender)=='female')?'selected':'';?>>Female</option>
                                    <option value="prefer_not_to_say" <?php echo (strtolower($gender)=='prefer_not_to_say')?'selected':'';?>>Prefer not to say</option>
                                </select>
                              </div>
                            </div>

                            <div class="form-group">
                              <label class="control-label col-sm-2">Contact Number:</label>
                              <div class="col-sm-10">
                                <input type="text" class="form-control" name="contactNo" value="<?php if($contactNo!='0') { echo $contactNo; } ?>">
                              </div>
                            </div>

                            <div class="form-group">
                              <div class="col-sm-offset-2 col-sm-10">
                                <button type="submit" class="btn btn-primary" style="margin:2% 0% 3% 0%;">Save Profile</button>
                              </div>
                            </div>
                        </form>
                    </div>

                  </section>
                </div>
            </section>
        </article>

      <!-- Footer -->
        <footer id="footer"><ul class="copyright"></ul></footer>

    </div>

    <!-- Scripts -->
      <script src="../js/jquery.min.js"></script>
      <script src="../js/student-dashboard.js"></script>
  </body>
</html>