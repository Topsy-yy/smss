<!DOCTYPE html>
<html>
<body>
  <?php
    session_start();
    require '../config.php';
    require 'security.php';
    validate_csrf_or_redirect('update_password_form', 'reset_pass.php');

    $pass = $_POST['pass'];
    $repass = $_POST['repass'];
    $email = $_SESSION['email'];
    if($pass === $repass){
      $phash=password_hash($pass, PASSWORD_DEFAULT);
      $conn = getDbConnection();
      $role = $_SESSION['role'];
      $stmt = null;
      if($role == 1){
        $stmt =  $conn->prepare("UPDATE student SET password = ? WHERE upMail = ?");
      }else if($role == 2){
        $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE upMail = ?");
      }else if($role == 3){
        $stmt = $conn->prepare("UPDATE signatory SET password = ? WHERE upMail = ?");
      }
      if($stmt){
        $stmt->bind_param("ss", $phash, $email);
      }
      if($stmt && $stmt->execute()){
        ?>
          <script type="text/javascript">
            alert("Password Reset");
            location.replace("../index.php");
          </script>
        <?php
      } else {
        app_log('error', 'Password update failed', array('email' => $email, 'role' => $role));
      }
    }

  ?>
</body>
</html>
