<?php
session_start();
require '../config.php';
require 'security.php';

$showPasswordForm = false;
$invalidCode = false;

try {
    if (isset($_POST['submit'])) {
        validate_csrf_or_redirect('reset_code_form', 'reset_pass.php');
        $num = $_POST['sixdn'];
        $conn = getDbConnection();
        $email = $_SESSION['email'];
        $sql = $conn->prepare("SELECT num FROM reset_password WHERE upMail = ?");
        $sql->bind_param("s", $email);
        $sql->execute();
        $result = $sql->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if ((string)$row['num'] === (string)$num) {
                    $showPasswordForm = true;
                    break;
                }
            }
            if (!$showPasswordForm) {
                $invalidCode = true;
            }
        } else {
            $invalidCode = true;
        }
    }
} catch (Exception $e) {
    app_log('error', 'Reset code verification failed', array('error' => $e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password</title>
  <link href="../css/bootstrap.min.css" rel="stylesheet">
  <link href="../css/login.css" rel="stylesheet">
      <link href="../css/portal.css" rel="stylesheet">
  <link href="../font-awesome/css/font-awesome.min.css" rel="stylesheet">
  <link href="../css/general.css" rel="stylesheet">
  <link href="../css/style.css" rel="stylesheet">
</head>
<body id="home" class="auth-page">
  <div class="intro-header">
    <div class="col-xs-12 text-center">
      <h1 class="h1_home">SMS</h1>
      <?php if ($showPasswordForm) { ?>
        <h3 class="h3_home">Set New Password</h3>
        <h4 class="h3_home">Enter and confirm your new password</h4>
      <?php } else { ?>
        <h3 class="h3_home">Password Reset</h3>
        <h4 class="h3_home">Enter your 6-digit reset code</h4>
      <?php } ?>

      <div class="login">
        <?php if ($invalidCode) { ?>
          <div class="alert alert-danger text-center">
            <strong>Invalid!</strong> Incorrect reset code. Please try again.
          </div>
        <?php } ?>

        <?php if ($showPasswordForm) { ?>
          <form action="updatepassword.php" method="post">
            <?php echo csrf_input('update_password_form'); ?>
            <input type="password" name="pass" placeholder="Enter New Password" required>
            <input type="password" name="repass" placeholder="Confirm New Password" required>
            <input type="submit" value="Submit" name="submit" class="btn btn-lg mybutton_standard">
          </form>
        <?php } else { ?>
          <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <?php echo csrf_input('reset_code_form'); ?>
            <input type="text" name="sixdn" maxlength="6" placeholder="Enter Six Digit Code" required>
            <input type="submit" value="Submit" name="submit" class="btn btn-lg mybutton_standard">
          </form>
        <?php } ?>

        <h5 class="h3_home"><a style="color:white" href="../index.php"><u>Back to Login</u></a></h5>
      </div>
    </div>
  </div>
</body>
</html>
