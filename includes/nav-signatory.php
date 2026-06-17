<?php
$sigNavActive = isset($sigNavActive) ? $sigNavActive : 'home';
$sigDisplayName = isset($sigDisplayName)
  ? $sigDisplayName
  : (($_SESSION['currentUserName'] ?? 'SIGNATORY') . ' (ID:' . ($_SESSION['currentUserID'] ?? '') . ')');
?>
<nav class="sig-navbar">
  <div class="sig-nav-left">
    <a href="tempSigHome.php" class="sig-nav-link <?php echo $sigNavActive === 'home' ? 'active' : ''; ?>">HOME</a>
    <a href="tempSigProfile.php" class="sig-nav-link <?php echo $sigNavActive === 'profile' ? 'active' : ''; ?>">USER PROFILE</a>

    <div class="dropdown">
      <a class="sig-nav-link <?php echo $sigNavActive === 'scholarships' ? 'active' : ''; ?>">SCHOLARSHIPS ▾</a>
      <div class="dropdown-menu">
        <a href="tempSigScholarship.php" class="dropdown-item">Browse All</a>
        <a href="tempAddScholarship.php" class="dropdown-item">Post New</a>
        <a href="tempSigScholarship.php" class="dropdown-item">My Listings</a>
      </div>
    </div>

    <div class="dropdown">
      <a class="sig-nav-link <?php echo $sigNavActive === 'applications' ? 'active' : ''; ?>">APPLICATIONS ▾</a>
      <div class="dropdown-menu">
        <a href="tempSigApplication.php?app=Pending" class="dropdown-item">Pending Review</a>
        <a href="tempSigApplication.php?app=Approved" class="dropdown-item">Accepted Applications</a>
        <a href="tempSigApplication.php?app=All" class="dropdown-item">All Applications</a>
      </div>
    </div>
  </div>

  <div class="sig-nav-right">
    <span style="font-weight: 600; font-size: 1.8rem;"><?php echo htmlspecialchars($sigDisplayName, ENT_QUOTES, 'UTF-8'); ?></span>
    <a href="../backend/logout.php" class="btn-logout">LOGOUT</a>
  </div>
</nav>
