<?php session_start();
  require 'config.php';
  require 'backend/security.php';
  require 'PHPMailer/PHPMailerAutoload.php';
 ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password</title>
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="css/login.css" rel="stylesheet">
  <link href="css/portal.css" rel="stylesheet">
  <link href="css/general.css" rel="stylesheet">
</head>
<body id="home" class="auth-page">

  <?php
    if(isset($_POST['submit'])){
      validate_csrf_or_redirect('forgot_password_form', 'forgotpassword.php');
      $email = $_POST['email'];
      $conn = getDbConnection();

      try{
        $lookup = $conn->prepare("SELECT upMail,1 AS role FROM student WHERE upMail = ? UNION SELECT upMail,2 AS role FROM signatory WHERE upMail = ?");
        $lookup->bind_param("ss", $email, $email);
        $lookup->execute();
        $result = $lookup->get_result();
        if ($result->num_rows > 0) {
          while($row = $result->fetch_assoc()) {
              $_SESSION['role'] = $row["role"];
          }
          $min = 100001;
          $max = 999999;
          $sixdigitnum = mt_rand ( $min ,  $max );
          $insertReset = $conn->prepare("INSERT INTO reset_password(upMail,num) VALUES (?, ?)");
          $insertReset->bind_param("si", $email, $sixdigitnum);
          if($insertReset->execute()){
            $mail = new PHPMailer;
            $mail->isSMTP();                            // Set mailer to use SMTP
            $mail->Host = SMTP_HOST;                    // Specify main and backup SMTP servers
            $mail->SMTPAuth = SMTP_AUTH;                // Enable SMTP authentication
            $mail->Username = SMTP_USER;                // SMTP username
            $mail->Password = SMTP_PASS;                // SMTP password
            $mail->SMTPSecure = SMTP_SECURE;            // Enable TLS encryption, `ssl` also accepted
            $mail->Port = SMTP_PORT;                    // TCP port to connect to

            $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
            $mail->addReplyTo(SMTP_USER, SMTP_FROM_NAME);
            $mail->addAddress($email);                  // Add a recipient
            // $mail->addCC('cc@example.com');
            // $mail->addBCC('bcc@example.com');
            $mail->isHTML(true);  // Set email format to HTML

            $bodyContent = '

            Hey There,
            <h1>We have got a Password Reset Request for your Account</h1><br/>

            Use the following code to Reset Password :<br/>'.$sixdigitnum.'<br/><br/>
            Thank You For Using Our WebSite!
            '; // Our message above including the
            $mail->Subject = 'Password Reset Request';
            $mail->Body    = $bodyContent;

            if(!$mail->send()) {
                app_log('warning', 'Reset password email failed', array('email' => $email, 'error' => $mail->ErrorInfo));
                echo '<br><br><div class="alert alert-info text-center"><strong>Local Development Hint:</strong> Since email failed to send, use this reset code: <strong>' . $sixdigitnum . '</strong></div>';
                $_SESSION['email'] = $email;
                ?>
                <div class="text-center">
                  <a href="backend/reset_pass.php" class="btn btn-primary">Go to Reset Password Page</a>
                </div>
                <?php
            } else {
              $_SESSION['email'] = $email;
              ?>
                <script type="text/javascript">
                  alert("Please check your Email for Password Reset!");
                  location.replace("backend/reset_pass.php")
                </script>
              <?php
              }
            }

        }else{?>
          <script type="text/javascript">
            alert("Account Doesnt Exists Please Signup First");
            location.replace('signup.php');
          </script><?php
        }
      }catch(Exception $e){
        app_log('error', 'Forgot password flow failed', array('email' => $email, 'error' => $e->getMessage()));
      }
    }else{
   ?>
  <div class="intro-header">
    <div class="col-xs-12 text-center">
      <h1 class="h1_home">SMS</h1>
      <h3 class="h3_home">Forgot Password</h3>
      <div class="login">
        <form action="<?php $_SERVER['PHP_SELF'] ?>" method="post">
          <?php echo csrf_input('forgot_password_form'); ?>
          <input type="email" name="email" placeholder="Enter your Email ID" required>
          <input type="submit" name="submit" value="Send Code" class="btn btn-lg mybutton_standard">
          <h5 class="h3_home"><a style="color:white" href="index.php"><u>Back to Login</u></a></h5>
        </form>
      </div>
    </div>
  </div>

  <?php } ?>

  <!-- JavaScript -->
  <script src="js/jquery-1.10.2.js"></script>
  <script src="js/bootstrap.js"></script>
</body>
</html>
