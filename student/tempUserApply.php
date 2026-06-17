 <?php
  session_start();
require '../config.php';
$_SESSION['selectedAppID'] = 0;
  $_SESSION['currentUserName'] = NULL;
  $_SESSION['appList'] = NULL;
  $currentUserID=$_SESSION['currentUserID'];
  if($currentUserID==NULL){
    header("Location:../index.php");
  }
  /* Connect to database */
    $conn = getDbConnection();
  /* Checks Connection */
    if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
    }
$getName = "select S.firstName, S.middleName, S.lastName from student S where S.studentID = '".$_SESSION['currentUserID']."'";
$nameResult = mysqli_query($conn,$getName);
// Get every row of the table formed from the query
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

$studentProfile = array(
  'current_level' => '',
  'gender' => '',
  'financial_need' => '',
  'career_interests' => ''
);

$studentProfileStmt = $conn->prepare("SELECT current_level, gender, financial_need, career_interests FROM student WHERE studentID = ? LIMIT 1");
if ($studentProfileStmt) {
  $studentProfileStmt->bind_param("i", $currentUserID);
  $studentProfileStmt->execute();
  $studentProfileResult = $studentProfileStmt->get_result()->fetch_assoc();
  if ($studentProfileResult) {
    $studentProfile = $studentProfileResult;
  }
  $studentProfileStmt->close();
}

$careerInterestMap = array(
  'Cultural / Arts' => 'cultural_talent',
  'Visual Art' => 'visual_art',
  'Sports Talent' => 'sports_talent',
  'Science & Maths' => 'science_maths_based',
  'Technology Based' => 'technology_based'
);

$allowedScholarshipTypes = array('merit_based', 'means_based');
$careerInterests = array_filter(array_map('trim', explode(',', (string)($studentProfile['career_interests'] ?? ''))));
foreach ($careerInterests as $interest) {
  if (isset($careerInterestMap[$interest])) {
    $allowedScholarshipTypes[] = $careerInterestMap[$interest];
  }
}
$allowedScholarshipTypes = array_values(array_unique($allowedScholarshipTypes));
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
          $studentNavCurrent = 'apply';
          require '../includes/nav-student.php';
        ?>


      <!-- Main -->
        <article id="main">

          <header class="page-hero container">
          </header>

          <!-- One -->
          <section class="content-card container">
                          <h1 style="padding-left: 40%"><strong>Apply for Scholarship</strong></h1>
                          <h1>Select Filters</h1>
                          <table>
                            <thead>
                               <tr>
                                 <th>Class</th>
                                 <th style="padding-left: 4%">Gender</th>
                                 
                                 <th style="padding-left: 4%">Scholarship</th>
                                </tr>
                            </thead>
                            <tbody>
                          <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="POST" name="login" >
                                <tr>
                                  <td >
                                  <select name="class" style="display: inline;">
                                    <option value="select" selected>Select</option>
                                    
                                    
                                    <option value="diploma">Diploma</option>
                                    <option value="undergraduate">UnderGraduate</option>
                                    <option value="postgraduate">Post-Graduate</option>
                                    <option value="phd">PhD</option>
                                  </select>
                                 </td>
                                 <td style="padding-left: 4%">
                                  <select name="gender" style="display: inline;">
                                    <option value="select" selected>Select</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="both">Both (Male & Female)</option>
                                    
                                  </select>
                                 </td>
                                 
                                </td>
                                <td style="padding-left: 4%">
                                  <select name="scholarship" style="display: inline;">
                                    <option value="select" selected>Select</option>
                                    <option value="merit">Merit Based</option>
                                    <option value="mean">Means Based</option>
                                    <option value="cultural">Cultural Talent</option>
                                    <option value="visual">Visual Art</option>
                                    <option value="sport">Sports Talent</option>
                                    <option value="science">Science, Maths Based</option>
                                    <option value="tech">Technology Based</option>
                                  </select>
                                </td>
                                <td style="padding-left: 4%">
                                  <input type="submit" id="apply" name="apply" value="Apply >">
                                </td>
                              </tr>
                        </form>
                      </tbody>
                    </table>
        </section>

          <!-- Two -->
            <section class="content-card container">
                <div class="content">
                  <section> <!-- start -->
                    <?php
                        $date1 = date("Y-m-d");
                        $selectedClass = trim($_POST['class'] ?? 'select');
                        $selectedGender = trim($_POST['gender'] ?? 'select');
                        $selectedScholarship = trim($_POST['scholarship'] ?? 'select');

                        $scholarshipFilterMap = array(
                          'merit' => 'merit_based',
                          'mean' => 'means_based',
                          'cultural' => 'cultural_talent',
                          'visual' => 'visual_art',
                          'sport' => 'sports_talent',
                          'science' => 'science_maths_based',
                          'tech' => 'technology_based'
                        );

                        $selectedScholarshipType = $scholarshipFilterMap[$selectedScholarship] ?? 'select';

                        $text = "Eligible Scholarships";
                        if ($selectedClass !== 'select' || $selectedGender !== 'select' || $selectedScholarship !== 'select') {
                          $text = "Filtered Eligible Scholarships";
                        }
                    ?>
                    <h1><?php echo $text; ?></h1>
                                <table class="table">
                                    <thead>
                                      <tr>
                                        <th style="width: 30%">Scholarship Name</th>
                                        <th>Description</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <?php
                                        $conditions = array();
                                        $params = array();
                                        $types = '';

                                        $conditions[] = "adminapproval = 'Approved'";
                                        $conditions[] = "appDeadline >= ?";
                                        $params[] = $date1;
                                        $types .= 's';

                                        $studentLevel = trim((string)($studentProfile['current_level'] ?? ''));
                                        if ($studentLevel !== '') {
                                          $conditions[] = "(degree = ? OR degree = 'select' OR degree = '')";
                                          $params[] = $studentLevel;
                                          $types .= 's';
                                        }

                                        $studentGender = trim((string)($studentProfile['gender'] ?? ''));
                                        if ($studentGender !== '') {
                                          $conditions[] = "(gender = ? OR gender = 'male+female' OR gender = 'select' OR gender = '')";
                                          $params[] = $studentGender;
                                          $types .= 's';
                                        }

                                        $studentNeed = trim((string)($studentProfile['financial_need'] ?? ''));
                                        if ($studentNeed !== '') {
                                          $conditions[] = "(target_financial_need = ? OR target_financial_need = 'Any' OR target_financial_need = '')";
                                          $params[] = $studentNeed;
                                          $types .= 's';
                                        }

                                        if (!empty($allowedScholarshipTypes)) {
                                          $placeholders = implode(',', array_fill(0, count($allowedScholarshipTypes), '?'));
                                          $conditions[] = "sch IN ($placeholders)";
                                          foreach ($allowedScholarshipTypes as $schType) {
                                            $params[] = $schType;
                                            $types .= 's';
                                          }
                                        }

                                        if ($selectedClass !== 'select') {
                                          $conditions[] = "degree = ?";
                                          $params[] = $selectedClass;
                                          $types .= 's';
                                        }

                                        if ($selectedGender !== 'select') {
                                          $conditions[] = "(gender = ? OR gender = 'male+female')";
                                          $params[] = $selectedGender;
                                          $types .= 's';
                                        }

                                        if ($selectedScholarshipType !== 'select') {
                                          $conditions[] = "sch = ?";
                                          $params[] = $selectedScholarshipType;
                                          $types .= 's';
                                        }

                                        $to_query = "SELECT scholarshipID, schname, description FROM scholarship WHERE " . implode(' AND ', $conditions);
                                        $sql_result_stmt = $conn->prepare($to_query);

                                        if ($sql_result_stmt) {
                                          if (!empty($params)) {
                                            $sql_result_stmt->bind_param($types, ...$params);
                                          }
                                          $sql_result_stmt->execute();
                                          $sql_result = $sql_result_stmt->get_result();

                                          while($row = $sql_result->fetch_assoc()){
                                      ?>
                                          <tr>
                                            <td><a href="tempschdesc.php?sch=<?php echo (int)$row['scholarshipID']; ?>" title="<?php echo htmlspecialchars((string)$row['schname'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string)$row['schname'], ENT_QUOTES, 'UTF-8'); ?></a></td>
                                            <td><?php echo htmlspecialchars((string)$row['description'], ENT_QUOTES, 'UTF-8'); ?></td>
                                          </tr>
                                      <?php
                                          }
                                          $sql_result_stmt->close();
                                        }
                                      ?>
                                    </tbody>
                                  </table>
                  </section> <!-- end -->
                </div>

            </section>

          <!-- Two -->
            <section class="content-strip container">
              <div class="row">
                <div class="4u 12u(narrower)">

                  <section>
                    <header>
                      <h3>Explore upcoming deadlines</h3>
                    </header>
                    <p>Review active scholarships by deadline so you can prioritize applications with enough preparation time.</p>
                    <footer>
                      <ul class="buttons">
                        <li><a href="#" class="button small">Learn More</a></li>
                      </ul>
                    </footer>
                  </section>

                </div>
                <div class="4u 12u(narrower)">

                  <section>
                    <header>
                      <h3>Match by eligibility</h3>
                    </header>
                    <p>Use eligibility filters to focus on opportunities that align with your academic profile and background.</p>
                    <footer>
                      <ul class="buttons">
                        <li><a href="#" class="button small">Learn More</a></li>
                      </ul>
                    </footer>
                  </section>

                </div>
                <div class="4u 12u(narrower)">

                  <section>
                    <header>
                      <h3>Submit with confidence</h3>
                    </header>
                    <p>Track every submission and quickly check whether your application is pending, approved, or requires updates.</p>
                    <footer>
                      <ul class="buttons">
                        <li><a href="#" class="button small">Learn More</a></li>
                      </ul>
                    </footer>
                  </section>

                </div>
              </div>
            </section>

        </article>

      <!-- Footer -->
        <footer id="footer"><ul class="copyright">
          </ul>

        </footer>

    </div>

    <!-- Scripts -->
    <!-- jQuery -->
    <script src="../js/jquery.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="../js/bootstrap.min.js"></script>
      <script src="../js/jquery.min.js"></script>
      <script src="../js/student-dashboard.js"></script>

    <script type="text/javascript">
    $(document).ready(function(){
      $("#applyBtn").click(function(){
        $("#applyModal").modal();
        });
    });
    </script>

  </body>
</html>
