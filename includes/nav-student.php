<header id="header" class="alt" style="background-color:#f3f6fa;color:black;height:4%">
  <h1 id="logo"><a href="javascript:history.back()" class="button special">Back</a></h1>
  <nav id="nav">
    <ul>
      <li class="current"><a href="tempUserHome.php">Home</a></li>
      <li><a href="tempUserProfile.php">User Profile</a></li>
      <li><a href="tempUserApply.php">Apply</a></li>
      <li><a href="tempUserView.php">View Scholarship Status</a></li>
      <li><?php echo $_SESSION['currentUserName'] . ' (ID:' . $_SESSION['currentUserID'] . ')'; ?></li>
      <li><a href="../backend/logout.php" class="button special">Logout</a></li>
    </ul>
  </nav>
</header>
