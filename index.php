<?php
session_start();
require 'backend/security.php';
$pageTitle = 'Login';
$assetPrefix = '';
$pageStyles = array('css/portal.css');
require __DIR__ . '/includes/head-auth.php';
?>

  <body id="home" class="auth-page">

    <?php
      if(empty($_SESSION['errMsg'])){ ?>
        <div id = "preloader">
          <div id = "status"></div>
        </div>

      <?php } ?>

    <div class = "intro-header">
      <div class = "col-xs-12 text-center">
        <h1 class = "h1_home wow fadeIn" data-wow-delay = "0.4s">SMS</h1>
        <h3 class = "h3_home wow fadeIn" data-wow-delay = "0.6s">Scholarship Management System </h3>
		<h3 class = "h3_home wow fadeIn" data-wow-delay = "0.6s">Log in to your Scholarship Portal</h3>

        <div class="login">
          <form action="backend/login.php" method="POST" name="login">
            <?php echo csrf_input('login_form'); ?>
            <input type="email" name="email" class = "h3_home wow fadeIn" data-wow-delay = "0.8s" placeholder="Email Address" required autofocus>
            <input type="password" name="password" class = "h3_home wow fadeIn" data-wow-delay = "1.0s" placeholder="Password">
            <input type = "submit" value="Login" class = "btn btn-lg mybutton_standard wow swing wow fadeIn network-name text-center" data-wow-delay="1.2s">
            <h5 class = "h3_home wow fadeIn" data-wow-delay = "1.2s">Don't have an Account<a style="color:white" href="signup.php">&nbsp;&nbsp;<u>Click Here</u></a></h5>
            <h5 class = "h3_home wow fadeIn" data-wow-delay = "1.2s"><a style="color:white" href="forgotpassword.php"><u>Forgot Password</u></a></h5>
          </form>
          <?php
            if(!empty($_SESSION['errMsg'])){ ?>
              <div class = "wow fadeIn" data-wow-delay = "1.2s">
                <div class="alert alert-danger wow swing text-center" data-wow-delay="1.2s" style="margin-top:20px;">
                  <center><strong>Invalid! </strong><?php echo $_SESSION['errMsg']; ?></center>
                </div>
              </div>
          <?php unset($_SESSION['errMsg']); }?>

        </div>
     </div>
    </div>

<?php require __DIR__ . '/includes/scripts-auth.php'; ?>
