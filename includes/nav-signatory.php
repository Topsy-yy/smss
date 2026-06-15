<header id="header" class="alt" style="background-color:#f3f6fa;color:black;height:4%">
  <h1 id="logo"><a href="javascript:history.back()" class="button special">Back</a></h1>
  <nav id="nav">
    <ul>
      <li class="current"><a href="#">Home</a></li>
      <li><a href="tempSigProfile.php">User Profile</a></li>
      <li class="submenu">
        <a href="#">Scholarships</a>
        <ul>
          <li><a href="tempSigScholarship.php">My Scholarships</a></li>
          <li><a href="tempAddScholarship.php">Add Scholarships</a></li>
        </ul>
      </li>
      <li class="submenu">
        <a href="tempSigApplication.php">Applications</a>
        <ul>
          <li><a href="tempSigApplication.php?app=Pending">Pending applications</a></li>
          <li><a href="tempSigApplication.php?app=Approved">Accepted Applicaitons</a></li>
          <li><a href="tempSigApplication.php?app=Rejected">Rejected Applicaitons</a></li>
        </ul>
      </li>
      <li><?php echo $_SESSION['currentUserName'] . ' (ID:' . $_SESSION['currentUserID'] . ')'; ?></li>
      <li><a href="../backend/logout.php" class="button special">Logout</a></li>
    </ul>
  </nav>
</header>
