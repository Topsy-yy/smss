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

  $upMail=$firstName=$lastName=$middleName=$nationality=$gender=$birthPlace=$presStreetAddr=$presProvCity=$presRegion=$permProvCity=$permStreetAddr=$permRegion=$contactNo=$dept=$college=$birthDate=$status= NULL;
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
    $status = $row["status"];
    
    // Assign Matching Variables
    $current_level = $row["current_level"] ?? '';
    $financial_need = $row["financial_need"] ?? '';
    $career_interests = $row["career_interests"] ?? '';
  }

  $careerInterestSelections = array_filter(array_map('trim', explode(',', (string) $career_interests)));
  $isProfileComplete = !empty($current_level) && !empty($financial_need) && !empty($career_interests);
  $isOnboardingMode = (isset($_GET['onboarding']) && $_GET['onboarding'] == '1') || !$isProfileComplete;
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
        <?php if(!$isOnboardingMode){
          $studentNavCurrent = 'profile';
          require '../includes/nav-student.php';
        } ?>

      <!-- Main -->
        <article id="main">
          <header class="page-hero container"></header>

          <!-- One -->
            <section class="content-card container">
              <!-- Content -->
                <div class="content">
                  <section>
                    <header><h1><b style="font-size: 1.2rem; margin: 10% 0% 0% 42%;">User Profile</b></h1></header>
                    <?php if($isOnboardingMode){ ?>
                      <div class="alert alert-warning" style="margin: 1rem 0; border-radius: 8px;">
                        Please complete your profile.
                      </div>
                    <?php } ?>
                    
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
                        <form method="POST" action="../backend/userdata.php" class="form-horizontal" role="form" id="studentProfileForm">
                            <?php if($isOnboardingMode){ ?>
                              <input type="hidden" name="onboarding_mode" value="1">
                            <?php } ?>
                            
                            <div class="form-group">
                              <label class="control-label col-sm-2">Email:</label>
                              <div class="col-sm-10">
                                <input type="email" class="form-control" value="<?php echo $upMail ?>" disabled>
                              </div>
                            </div>

                            <div class="profile-step" data-step="1">
                              <h4 style="margin-left: 17%; color: var(--brand-primary); margin-bottom: 18px;">Section 1: Personal Information</h4>

                              <div class="form-group">
                                <label class="control-label col-sm-2">First Name:</label>
                                <div class="col-sm-10">
                                  <input type="text" class="form-control" name="firstName" value="<?php echo $firstName?>" required>
                                </div>
                              </div>
                              <div class="form-group">
                                <label class="control-label col-sm-2">Middle Name:</label>
                                <div class="col-sm-10">
                                  <input type="text" class="form-control" name="middleName" value="<?php echo $middleName?>">
                                </div>
                              </div>
                              <div class="form-group">
                                <label class="control-label col-sm-2">Last Name:</label>
                                <div class="col-sm-10">
                                  <input type="text" class="form-control" name="lastName" value="<?php echo $lastName;?>" required>
                                </div>
                              </div>
                              <div class="form-group">
                                <label class="control-label col-sm-2">Gender:</label>
                                <div class="col-sm-10">
                                  <select class="form-control" name="gender" style="height: auto;" required>
                                      <option value="">Select Gender</option>
                                      <option value="male" <?php echo (strtolower($gender)=='male')?'selected':'';?>>Male</option>
                                      <option value="female" <?php echo (strtolower($gender)=='female')?'selected':'';?>>Female</option>
                                      <option value="prefer_not_to_say" <?php echo (strtolower($gender)=='prefer_not_to_say')?'selected':'';?>>Prefer not to say</option>
                                  </select>
                                </div>
                              </div>
                              <div class="form-group">
                                <label class="control-label col-sm-2">Nationality:</label>
                                <div class="col-sm-10">
                                  <input type="text" class="form-control" name="nationality" value="<?php echo $nationality;?>" required>
                                </div>
                              </div>
                              <div class="form-group">
                                <label class="control-label col-sm-2">Birth Date:</label>
                                <div class="col-sm-10">
                                  <input type="date" class="form-control" name="birthDate" value="<?php echo ($birthDate && $birthDate !== '0000-00-00') ? $birthDate : ''; ?>" required>
                                </div>
                              </div>
                              <div class="form-group">
                                <label class="control-label col-sm-2">Birth Place:</label>
                                <div class="col-sm-10">
                                  <input type="text" class="form-control" name="birthPlace" value="<?php echo $birthPlace;?>">
                                </div>
                              </div>

                              <div class="form-group">
                                <div class="col-sm-offset-2 col-sm-10">
                                  <button type="button" class="btn btn-primary profile-next">Next</button>
                                </div>
                              </div>
                            </div>

                            <div class="profile-step" data-step="2" style="display:none;">
                              <h4 style="margin-left: 17%; color: var(--brand-primary); margin-bottom: 18px;">Section 2: Academic Profile</h4>

                              <div class="form-group">
                                <label class="control-label col-sm-2">College:</label>
                                <div class="col-sm-10">
                                  <input type="text" class="form-control" name="college" value="<?php echo $college;?>" required>
                                </div>
                              </div>
                              <div class="form-group">
                                <label class="control-label col-sm-2">Department:</label>
                                <div class="col-sm-10">
                                  <input type="text" class="form-control" name="dept" value="<?php echo $dept;?>" required>
                                </div>
                              </div>
                              <!-- <div class="form-group">
                                <label class="control-label col-sm-2">Status:</label>
                                <div class="col-sm-10">
                                  <select class="form-control" name="status" style="height:auto;" required>
                                    <option value="">Select Status</option>
                                    <option value="active" <?php echo (strtolower((string)$status) === 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo (strtolower((string)$status) === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                  </select>
                                </div>
                              </div> -->

                              <hr style="border-top: 1px dashed #cbd5e1; margin: 25px 0;">
                              <h4 style="margin-left: 17%; color: var(--brand-primary); margin-bottom: 20px;">Matching Profile Data</h4>

                              <div class="form-group">
                                <label class="control-label col-sm-2">Education Level:</label>
                                <div class="col-sm-10">
                                  <select class="form-control" name="current_level" style="height: auto;" required>
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
                                  <select class="form-control" name="financial_need" style="height: auto;" required>
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
                                    <label style="display:block; font-weight: 500;"><input type="checkbox" name="career_interests[]" value="Cultural / Arts" <?php echo in_array('Cultural / Arts', $careerInterestSelections) ? 'checked' : ''; ?> > Cultural / Arts</label>
                                    <label style="display:block; font-weight: 500;"><input type="checkbox" name="career_interests[]" value="Visual Art" <?php echo in_array('Visual Art', $careerInterestSelections) ? 'checked' : ''; ?>> Visual Art</label>
                                    <label style="display:block; font-weight: 500;"><input type="checkbox" name="career_interests[]" value="Sports Talent" <?php echo in_array('Sports Talent', $careerInterestSelections) ? 'checked' : ''; ?>> Sports Talent</label>
                                    <label style="display:block; font-weight: 500;"><input type="checkbox" name="career_interests[]" value="Science & Maths" <?php echo in_array('Science & Maths', $careerInterestSelections) ? 'checked' : ''; ?>> Science & Maths</label>
                                    <label style="display:block; font-weight: 500; margin-bottom: 0;"><input type="checkbox" name="career_interests[]" value="Technology Based" <?php echo in_array('Technology Based', $careerInterestSelections) ? 'checked' : ''; ?>> Technology Based</label>
                                  </div>
                                  <small style="color: #64748b;">You can select more than one interest.</small>
                                </div>
                              </div>

                              <div class="form-group">
                                <div class="col-sm-offset-2 col-sm-10">
                                  <button type="button" class="btn btn-default profile-prev" style="margin-right:8px;">Back</button>
                                  <button type="button" class="btn btn-primary profile-next">Next</button>
                                </div>
                              </div>
                            </div>

                            <div class="profile-step" data-step="3" style="display:none;">
                              <h4 style="margin-left: 17%; color: var(--brand-primary); margin-bottom: 18px;">Section 3: Contact & Address</h4>

                              <div class="form-group">
                                <label class="control-label col-sm-2">Contact Number:</label>
                                <div class="col-sm-10">
                                  <input type="text" class="form-control" name="contactNo" value="<?php if($contactNo!='0') { echo $contactNo; } ?>" required>
                                </div>
                              </div>
                              <div class="form-group">
                                <label class="control-label col-sm-2">Present Street Address:</label>
                                <div class="col-sm-10">
                                  <input type="text" class="form-control" name="presStreetAddr" id="presStreetAddr" value="<?php echo $presStreetAddr;?>" required>
                                </div>
                              </div>
                              <div class="form-group">
                                <label class="control-label col-sm-2">Present Province/City:</label>
                                <div class="col-sm-10">
                                  <input type="text" class="form-control" name="presProvCity" id="presProvCity" value="<?php echo $presProvCity;?>" required>
                                </div>
                              </div>
                              <div class="form-group">
                                <label class="control-label col-sm-2">Present Region:</label>
                                <div class="col-sm-10">
                                  <input type="text" class="form-control" name="presRegion" id="presRegion" value="<?php echo $presRegion;?>" required>
                                </div>
                              </div>

                              <div class="form-group">
                                <div class="col-sm-offset-2 col-sm-10">
                                  <label style="font-weight:500;"><input type="checkbox" id="sameAsPresent"> Permanent address same as present</label>
                                </div>
                              </div>

                              <div class="form-group">
                                <label class="control-label col-sm-2">Permanent Street Address:</label>
                                <div class="col-sm-10">
                                  <input type="text" class="form-control" name="permStreetAddr" id="permStreetAddr" value="<?php echo $permStreetAddr;?>">
                                </div>
                              </div>
                              <div class="form-group">
                                <label class="control-label col-sm-2">Permanent Province/City:</label>
                                <div class="col-sm-10">
                                  <input type="text" class="form-control" name="permProvCity" id="permProvCity" value="<?php echo $permProvCity;?>">
                                </div>
                              </div>
                              <div class="form-group">
                                <label class="control-label col-sm-2">Permanent Region:</label>
                                <div class="col-sm-10">
                                  <input type="text" class="form-control" name="permRegion" id="permRegion" value="<?php echo $permRegion;?>">
                                </div>
                              </div>

                              <div class="form-group">
                                <div class="col-sm-offset-2 col-sm-10">
                                  <button type="button" class="btn btn-default profile-prev" style="margin-right:8px;">Back</button>
                                  <button type="submit" class="btn btn-primary">Save Profile</button>
                                </div>
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
      <script>
        document.addEventListener('DOMContentLoaded', function () {
          var displayDiv = document.getElementById('display');
          var editDiv = document.getElementById('editDiv');
          var showDivButton = document.getElementById('showDivButton');
          var form = document.getElementById('studentProfileForm');
          if (!form) return;

          if (showDivButton) {
            showDivButton.addEventListener('click', function () {
              displayDiv.style.display = 'none';
              editDiv.style.display = 'block';
            });
          }

          var forceEdit = <?php echo $isOnboardingMode ? 'true' : 'false'; ?>;
          if (forceEdit && displayDiv && editDiv) {
            displayDiv.style.display = 'none';
            editDiv.style.display = 'block';
          }

          var steps = form.querySelectorAll('.profile-step');
          var currentStep = 0;

          function showStep(index) {
            Array.prototype.forEach.call(steps, function (step, i) {
              step.style.display = (i === index) ? 'block' : 'none';
            });
            currentStep = index;
          }

          function validateStep(stepElement) {
            if (!stepElement) return true;
            var inputs = stepElement.querySelectorAll('input, select, textarea');
            for (var i = 0; i < inputs.length; i++) {
              var input = inputs[i];
              if (typeof input.checkValidity === 'function' && !input.checkValidity()) {
                input.reportValidity();
                return false;
              }
            }
            return true;
          }

          var interestInputs = form.querySelectorAll('input[name="career_interests[]"]');
          function validateCareerInterests() {
            var checked = Array.prototype.some.call(interestInputs, function (input) {
              return input.checked;
            });
            var message = checked ? '' : 'Please select at least one career interest.';
            if (interestInputs.length) {
              interestInputs[0].setCustomValidity(message);
            }
            return checked;
          }

          Array.prototype.forEach.call(interestInputs, function (input) {
            input.addEventListener('change', validateCareerInterests);
          });

          var nextButtons = form.querySelectorAll('.profile-next');
          Array.prototype.forEach.call(nextButtons, function (btn) {
            btn.addEventListener('click', function () {
              var interestsOk = validateCareerInterests();
              if (!interestsOk) {
                interestInputs[0].reportValidity();
                return;
              }
              if (!validateStep(steps[currentStep])) {
                return;
              }
              if (currentStep < steps.length - 1) {
                showStep(currentStep + 1);
              }
            });
          });

          var prevButtons = form.querySelectorAll('.profile-prev');
          Array.prototype.forEach.call(prevButtons, function (btn) {
            btn.addEventListener('click', function () {
              if (currentStep > 0) {
                showStep(currentStep - 1);
              }
            });
          });

          var sameAsPresent = document.getElementById('sameAsPresent');
          var presStreetAddr = document.getElementById('presStreetAddr');
          var presProvCity = document.getElementById('presProvCity');
          var presRegion = document.getElementById('presRegion');
          var permStreetAddr = document.getElementById('permStreetAddr');
          var permProvCity = document.getElementById('permProvCity');
          var permRegion = document.getElementById('permRegion');

          function syncPermanentFields() {
            if (!sameAsPresent || !sameAsPresent.checked) {
              return;
            }
            permStreetAddr.value = presStreetAddr.value;
            permProvCity.value = presProvCity.value;
            permRegion.value = presRegion.value;
          }

          if (sameAsPresent) {
            sameAsPresent.addEventListener('change', function () {
              if (sameAsPresent.checked) {
                syncPermanentFields();
                permStreetAddr.readOnly = true;
                permProvCity.readOnly = true;
                permRegion.readOnly = true;
              } else {
                permStreetAddr.readOnly = false;
                permProvCity.readOnly = false;
                permRegion.readOnly = false;
              }
            });

            [presStreetAddr, presProvCity, presRegion].forEach(function (input) {
              input.addEventListener('input', syncPermanentFields);
            });
          }

          showStep(0);

          form.addEventListener('submit', function (event) {
            var interestsOk = validateCareerInterests();
            if (!validateStep(steps[currentStep]) || !form.checkValidity() || !interestsOk) {
              event.preventDefault();
              form.reportValidity();
            }
          });
        });
      </script>
  </body>
</html>