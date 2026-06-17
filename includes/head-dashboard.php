<?php
$pageTitle = isset($pageTitle) ? $pageTitle : 'Dashboard';
$assetPrefix = isset($assetPrefix) ? $assetPrefix : '../';
$roleStyles = isset($roleStyles) && is_array($roleStyles) ? $roleStyles : array();
$pageStyles = isset($pageStyles) && is_array($pageStyles) ? $pageStyles : array();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Scholarship Management System dashboard">
    <meta name="author" content="">
    <meta name="theme-color" content="#0b3558">
    <link rel="manifest" href="<?php echo $assetPrefix; ?>manifest.webmanifest">

    <link href="<?php echo $assetPrefix; ?>css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo $assetPrefix; ?>css/tempuserhome.css" rel="stylesheet">
    <link href="<?php echo $assetPrefix; ?>css/app.css" rel="stylesheet">

<?php foreach ($roleStyles as $stylePath): ?>
    <link href="<?php echo $assetPrefix . $stylePath; ?>" rel="stylesheet">
<?php endforeach; ?>

<?php foreach ($pageStyles as $stylePath): ?>
    <link href="<?php echo $assetPrefix . $stylePath; ?>" rel="stylesheet">
<?php endforeach; ?>
  </head>
