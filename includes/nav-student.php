<?php
$studentNavCurrent = isset($studentNavCurrent) ? $studentNavCurrent : '';
?>
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
