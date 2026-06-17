<?php
$studentNavCurrent = isset($studentNavCurrent) ? $studentNavCurrent : '';
$hideStudentNav = false;

if (isset($_SESSION['currentUserID'])) {
  require_once __DIR__ . '/../config.php';
  $profileConn = getDbConnection();

  if (!$profileConn->connect_error) {
    $profileStmt = $profileConn->prepare("SELECT current_level, financial_need, career_interests FROM student WHERE studentID = ? LIMIT 1");
    if ($profileStmt) {
      $studentId = (int) $_SESSION['currentUserID'];
      $profileStmt->bind_param("i", $studentId);
      $profileStmt->execute();
      $profileResult = $profileStmt->get_result()->fetch_assoc();
      $profileStmt->close();

      $isProfileComplete = !empty($profileResult['current_level']) && !empty($profileResult['financial_need']) && !empty($profileResult['career_interests']);
      $isProfilePage = basename($_SERVER['PHP_SELF']) === 'tempUserProfile.php';

      if (!$isProfileComplete) {
        if (!$isProfilePage) {
          header('Location: tempUserProfile.php?force_edit=1&onboarding=1');
          exit();
        }
        $hideStudentNav = true;
      }
    }
  }

  $profileConn->close();
}
?>
<?php if(!$hideStudentNav){ ?>
<header class="app-header">
  <h1 class="app-logo"><a href="javascript:history.back()" class="app-btn">Back</a></h1>
  <nav class="app-nav">
    <ul>
      <li class="<?php echo $studentNavCurrent === 'home' ? 'current' : ''; ?>"><a href="tempUserHome.php">Home</a></li>
      <li class="<?php echo $studentNavCurrent === 'profile' ? 'current' : ''; ?>"><a href="tempUserProfile.php">User Profile</a></li>
      <li class="<?php echo $studentNavCurrent === 'apply' ? 'current' : ''; ?>"><a href="tempUserApply.php">Apply</a></li>
      <li class="<?php echo $studentNavCurrent === 'view' ? 'current' : ''; ?>"><a href="tempUserView.php">View Scholarship Status</a></li>
      <li><?php echo $_SESSION['currentUserName'] . ' (ID:' . $_SESSION['currentUserID'] . ')'; ?></li>
      <li><a href="../backend/logout.php" class="app-btn">Logout</a></li>
    </ul>
  </nav>
</header>
<?php } ?>
