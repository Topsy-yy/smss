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

  while($rows9=mysqli_fetch_row($nameResult)){
    foreach ($rows9 as $key => $value){
      if($key == 0){ $_SESSION['currentUserName'] = $value; }
      if($key == 1){ $_SESSION['currentUserName'] .= " " . $value; }
      if($key == 2){ $_SESSION['currentUserName'] .= ". " . $value; }
    }
  }
?>
<!DOCTYPE HTML>
<html>
  <head>
      <title>Add Scholarship | ScholarConnect</title>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">

      <link href="../css/bootstrap.min.css" rel="stylesheet">
      <link href="../css/sig.css" rel="stylesheet">
      <link href="../css/pages/signatory-dashboard.css" rel="stylesheet">
  </head>

    <body class="app-shell sig-add-scholarship-page">

  	<script type="text/javascript">
        function fileValidation(name){
            var fileInput = document.getElementById(name);
            var filePath = fileInput.value;
            var allowedExtensions = /(\.pdf)$/i;
            if(!allowedExtensions.exec(filePath)){
                alert('Please upload file having extensions .pdf only.');
                fileInput.value = '';
                return false;
            } else if(fileInput.files[0].size > 8000000){
                alert('File size too large. Max is 8MB.');
                fileInput.value = '';
                return false;
            }
        }
    </script>

    <div class="app-page">

        <?php
            $sigNavActive = 'scholarships';
            require __DIR__ . '/../includes/nav-signatory.php';
        ?>

        <!-- Main -->
        <article id="main" class="sig-scholarship-main">
            <header class="page-hero"></header>

            <!-- One -->
            <section class="content-card">
                <!-- Content -->
                <div class="content">
                    <section>
                        <header>
                            <h2 style="text-align: center; color: var(--sig-navy); margin-bottom: 2rem;"><strong>Create New Scholarship</strong></h2>
                        </header>

                        <form method="post" name="scholarshiplist" id="scholarshiplist" action="../backend/adminAddDelSch.php" enctype="multipart/form-data">

                            <div class="form-group">
                                <label><strong>Scholarship Title</strong></label>
                                <label style="font-size: 0.85rem; color: var(--text-muted); display: block; margin-bottom: 0.5rem;">This will be displayed as the main heading.</label>
                                <input type="text" class="form-control" name="schname" placeholder="Eg: Tech Innovators Grant 2026" required>
                            </div>

                            <div class="row" style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                                <div class="form-group" style="flex: 1;">
                                    <label><strong>Study Location</strong></label>
                                    <input type="text" class="form-control" name="schlocation" placeholder="Where must they study?">
                                </div>
                                <div class="form-group" style="flex: 1;">
                                    <label><strong>Target Origin Location</strong></label>
                                    <input type="text" class="form-control" name="schlocationfrom" placeholder="Where must applicants be from?">
                                </div>
                            </div>

                            <div class="row" style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                                <div class="form-group" style="flex: 1;">
                                    <label><strong>Minimum Education Requirement</strong></label>
                                    <select name="degree" class="form-control" required>
                                        <option value="select" selected disabled>Select Required Level</option>
                                        <option value="high school">High School</option>
                                        <option value="diploma">Diploma</option>
                                        <option value="undergraduate">Undergraduate</option>
                                        <option value="postgraduate">Postgraduate</option>
                                        <option value="phd">PhD</option>
                                    </select>
                                </div>
                                <div class="form-group" style="flex: 1;">
                                    <label><strong>Gender Restriction</strong></label>
                                    <select name="gender" class="form-control" required>
                                        <option value="select" selected disabled>Select Gender</option>
                                        <option value="male">Male Only</option>
                                        <option value="female">Female Only</option>
                                        <option value="male+female">Open to All (Male + Female)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row" style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                                <!-- NEW FINANCIAL NEED MATCHER INPUT -->
                                <div class="form-group" style="flex: 1;">
                                    <label><strong>Target Financial Need</strong> <span style="color: var(--sig-teal); font-size: 0.8rem;">(Used by Matching Engine)</span></label>
                                    <select name="target_financial_need" class="form-control" required>
                                        <option value="Any" selected>Any (Open to all)</option>
                                        <option value="Low">Low Need</option>
                                        <option value="Medium">Medium Need</option>
                                        <option value="High">High Need</option>
                                        <option value="Critical">Critical Need</option>
                                    </select>
                                </div>
                                <div class="form-group" style="flex: 1;">
                                    <label><strong>Scholarship Type / Category</strong></label>
                                    <select name="scholarship" class="form-control" required>
                                        <option value="select" selected disabled>Select Category</option>
                                        <option value="merit_based">Merit Based</option>
                                        <option value="means_based">Means Based</option>
                                        <option value="cultural_talent">Cultural / Arts</option>
                                        <option value="visual_art">Visual Art</option>
                                        <option value="sports_talent">Sports Talent</option>
                                        <option value="science_maths_based">Science & Maths</option>
                                        <option value="technology_based">Technology Based</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row" style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                                <div class="form-group" style="flex: 1;">
                                    <label><strong>Application Deadline</strong></label>
                                    <input type="date" class="form-control" name="appdeadline" required>
                                </div>
                                <div class="form-group" style="flex: 1;">
                                    <label><strong>Maximum Grantees (Cap)</strong></label>
                                    <input type="number" class="form-control" name="granteesNum" placeholder="e.g. 50">
                                </div>
                            </div>

                            <div class="form-group">
                                <label><strong>Funding Amount</strong></label>
                                <input type="text" class="form-control" name="funding" placeholder="e.g. 100% Tuition or KES 50,000">
                            </div>

                            <div class="form-group">
                                <label><strong>Description</strong></label>
                                <textarea name="description" class="form-control" rows="4" placeholder="Give a general overview of this scholarship."></textarea>
                            </div>

                            <div class="form-group">
                                <label><strong>Eligibility & Requirements</strong></label>
                                <textarea name="eligibility" class="form-control" rows="3" placeholder="What must they achieve to be considered?"></textarea>
                            </div>

                            <div class="form-group">
                                <label><strong>Benefits Covered</strong></label>
                                <textarea name="benefits" class="form-control" rows="3" placeholder="What does this grant actually pay for?"></textarea>
                            </div>

                            <div class="form-group">
                                <label><strong>Application Instructions</strong></label>
                                <textarea name="apply" class="form-control" rows="3" placeholder="Step by step application instructions..."></textarea>
                            </div>

                            <div class="form-group">
                                <label><strong>Important Links</strong></label>
                                <textarea name="links" class="form-control" rows="2" placeholder="Organization website, external application links..."></textarea>
                            </div>

                            <div class="form-group">
                                <label><strong>Contact Details</strong></label>
                                <textarea name="contact" class="form-control" rows="2" placeholder="Helpdesk email, phone numbers..."></textarea>
                            </div>

                            <div class="form-group" style="background: var(--sig-bg); padding: 1.5rem; border-radius: var(--radius-md); margin-top: 2rem;">
                                <label><strong>Upload Validation Document</strong> <span style="color: var(--accent-red);">*</span></label>
                                <label style="font-size: 0.85rem; color: var(--text-muted); display: block; margin-bottom: 0.5rem;">Provide an official soft copy letter from your organization authorizing this scholarship.</label>
                                <input type="file" class="form-control" name="validate" id="validate" onchange="return fileValidation('validate')" accept=".pdf"  required style="background: white;">
                            </div>

                            <input type="hidden" name="adminapproval" value="Pending">

                            <div class="text-center" style="margin-top: 2rem;">
                                <button type="submit" name="deladd" class="btn-submit" style="width: 100%; max-width: 400px; padding: 1rem; font-size: 1.1rem;">Submit For Approval</button>
                            </div>
                        </form>

                        <div class="text-center" style="margin-top: 1rem;">
                            <a href="tempSigScholarship.php" style="color: var(--text-muted); text-decoration: none; font-weight: 500;">Cancel and Go Back</a>
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