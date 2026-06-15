<?php
session_start();
require '../config.php';
require 'security.php';

try {
    if (isset($_POST['submit'])) {
        validate_csrf_or_redirect('verify_signup_code_form', 'verify_signupcode.php');
        $num = $_POST['sixdn'];
        $conn = getDbConnection();
        $email = $_SESSION['email'];
        $sql = $conn->prepare("SELECT num FROM verify_signup WHERE upMail = ?");
        $sql->bind_param("s", $email);
        $sql->execute();
        $result = $sql->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if ((string)$row['num'] === (string)$num) {
                    $update = $conn->prepare("UPDATE verify_signup SET action = 1 WHERE upMail = ?");
                    $update->bind_param("s", $email);
                    if ($update->execute()) {
                        ?>
                        <script type="text/javascript">
                          alert("EMail Verified ! Please login");
                          location.replace("../index.php");
                        </script>
                        <?php
                    }
                } else {
                    ?>
                    <script type="text/javascript">
                      alert("Incorrect credentials");
                      location.replace("verify_signupcode.php");
                    </script>
                    <?php
                }
            }
        }
    }
} catch (Exception $e) {
    app_log('error', 'Signup verification failed', array('error' => $e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify Account</title>
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
      <h3 class="h3_home">Account Verification</h3>
      <h4 class="h3_home">Enter your 6-digit verification code</h4>
      <div class="login">
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
          <?php echo csrf_input('verify_signup_code_form'); ?>
          <input type="text" name="sixdn" maxlength="6" placeholder="Enter Six Digit Code" required>
          <input type="submit" value="Verify" name="submit" class="btn btn-lg mybutton_standard">
          <h5 class="h3_home"><a style="color:white" href="../index.php"><u>Back to Login</u></a></h5>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
