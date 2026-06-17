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
      <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0">

      <meta name="description" content="">
      <meta name="author" content="">


      <title>Student Signup</title>

      <link href="css/bootstrap.min.css" rel="stylesheet">
      <link href="css/login.css" rel="stylesheet">
  <link href="css/portal.css" rel="stylesheet">

    <!-- Custom Google Web Font -->
    <link href='https://fonts.googleapis.com/css?family=Lato:100,300,400,700,900,100italic,300italic,400italic,700italic,900italic' rel='stylesheet' type='text/css'>
    <link href='https://fonts.googleapis.com/css?family=Arvo:400,700' rel='stylesheet' type='text/css'>
    <link href='https://fonts.googleapis.com/css?family=Exo:100,200,400' rel='stylesheet' type='text/css'>

    <!-- Custom CSS-->
    <link href="css/general.css" rel="stylesheet">

    <!-- Owl-Carousel -->
    <link href="css/custom.css" rel="stylesheet">
  </head>

  <body id="home" class="auth-page">

    <?php
    $email=NULL;
      $flag=1;
      try{
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
          validate_csrf_or_redirect('signup_student_form', 'signup_student.php');
          if (!empty($_POST["email"]) && !empty($_POST["password"])) {

            $email = $_POST['email'];

            $pass = $_POST['password'];
            $cpass = $_POST['confirm_password'];
            if(strcmp($pass, $cpass)!=0){
              $flag=-1;
            }
            $conn = getDbConnection();

            $sql = "SELECT upMail FROM student UNION SELECT upMail FROM signatory";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
              while($row = $result->fetch_assoc()) {
                if($row["upMail"]==$email){
                  $flag=0;
                }
              }
            }
            if($flag==0){
              $_SESSION['errMsg'] = "User Already Exists!";

            }else if($flag==-1){
               $_SESSION['errMsg'] = "Password and Confirm Password donot match";
            }
            else{
              //Convert password into hash
              $phash=password_hash($pass, PASSWORD_DEFAULT);

              // Write insert query
              $insertStudent = $conn->prepare("INSERT INTO student(upMail,password) VALUES (?, ?)");
              $insertStudent->bind_param("ss", $email, $phash);
              if ($insertStudent->execute()) {
                $min = 100001;
                $max = 999999;
                $sixdigitnum = mt_rand ( $min ,  $max );
                $insertVerify = $conn->prepare("INSERT INTO verify_signup(upMail,num) VALUES (?, ?)");
                $insertVerify->bind_param("si", $email, $sixdigitnum);
                if($insertVerify->execute()){
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

                  Thanks for signing up!
                  <h1>Your account has been created</h1>You can <strong>login</strong> with the following credentials after you have activated your account by pressing the url below.


                  Use the following code to Login To Our WebSite:<br/>'.$sixdigitnum.'<br/><br/>
                  Thank You For Using Our WebSite!
                  '; // Our message above including the
                  $mail->Subject = 'Signup | Verification';
                  $mail->Body    = $bodyContent;

                   if(!$mail->send()) {
                       app_log('warning', 'Student verification email failed', array('email' => $email, 'error' => $mail->ErrorInfo));
                       echo '<br><br><div class="alert alert-info text-center"><strong>Local Development Hint:</strong> Since email failed to send, use this verification code: <strong>' . $sixdigitnum . '</strong></div>';
                      $_SESSION['email'] = $email;
                      ?>
                      <div class="text-center">
                        <a href="backend/verify_signupcode.php" class="btn btn-primary">Go to Verification Page</a>
                      </div>
                      <?php
                  } else {
                    $_SESSION['email'] = $email;

                  ?>
                    <script type="text/javascript">
                      alert("Your Account Has been Created, Please check your Email for verification!");
                      location.replace("backend/verify_signupcode.php")
                    </script>
                  <?php
                  }
                }
              } else {
                app_log('error', 'Student signup insert failed', array('email' => $email, 'error' => $conn->error));
                echo "Error: Unable to create account.";
              }
            }
            $conn->close();
          }
        }
      }
      catch(Exception $e)
      {
        app_log('error', 'Student signup exception', array('error' => $e->getMessage()));
        echo "Unexpected error while creating account.";
      }
    ?>

    <div class = "intro-header">
      <div class = "col-xs-12 text-center">
        <h1 class = "h1_home wow fadeIn" data-wow-delay = "0.4s">SMS</h1>
        <h3 class = "h3_home wow fadeIn" data-wow-delay = "0.6s">Student Signup</h3>
        <h4 class = "h3_home wow fadeIn" data-wow-delay = "0.6s">Create Your Account</h4>

        <div class="login">
          <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="POST" name="login" >
            <?php echo csrf_input('signup_student_form'); ?>
            <input type="email" name="email" class = "h3_home wow fadeIn" data-wow-delay = "0.8s" value="<?php echo $email ?>" placeholder="Enter Email Address" required autofocus>

            <input type="password" name="password" id="password" class = "h3_home wow fadeIn" data-wow-delay = "1.0s" placeholder="Enter Password" required>

            <input type="password" name="confirm_password" id="confirm_password" class = "h3_home wow fadeIn" data-wow-delay = "1.2s" placeholder="Confirm Password" required>

            <input type = "submit" id="submit" class = "btn btn-lg mybutton_standard wow swing wow fadeIn network-name text-center" data-wow-delay="1.2s">

            <h5 class = "h3_home wow fadeIn" data-wow-delay = "1.4s">Already have an Account<a style="color:white" href="index.php">&nbsp;&nbsp;<u>Click Here</u></a></h5>

            <h5 class = "h3_home wow fadeIn" data-wow-delay = "1.6s">Signup as a<a style="color:white" href="signup_sig.php">&nbsp;&nbsp;<u title="Terms and Conditions apply">Signatory</u></a></h5>
          </form>

          <?php
            if(!empty($_SESSION['errMsg'])){ ?>
              <div class = "wow fadeIn" data-wow-delay = "1.8s">
                <div class="alert alert-danger wow swing text-center" data-wow-delay="2.2s" style="margin-top:20px;">
                  <center><strong>Invalid! </strong><?php echo $_SESSION['errMsg']; ?></center>
                </div>
              </div>
          <?php unset($_SESSION['errMsg']); }?>
        </div>
     </div>
    </div>


    <!-- JavaScript -->
    <script src="js/jquery-1.10.2.js"></script>
    <script src="js/bootstrap.js"></script>
    <!-- StikyMenu -->
    <script type="text/javascript">
      jQuery(function($) {
      $(document).ready( function() {

      });
      });

      //Checking Password and Confirm Password
      function check_pass(){
        if(document.getElementById("password").value == document.getElementById("confirm_password").value){
          document.getElementById("submit").disabled=false;
        }
        else{
          document.getElementById("submit").disabled=true;
        }
      }

    </script>
    <!-- Smoothscroll -->
    <script>
    </script>
    <!-- Magnific Popup core JS file -->

  </body>
</html>
