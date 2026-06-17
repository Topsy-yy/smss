<?php
$pageTitle = isset($pageTitle) ? $pageTitle : 'SMS';
$assetPrefix = isset($assetPrefix) ? $assetPrefix : '';
$pageStyles = isset($pageStyles) && is_array($pageStyles) ? $pageStyles : array();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0b3558">
    <meta name="description" content="Scholarship Management System">
    <meta name="author" content="">
    <link rel="manifest" href="<?php echo $assetPrefix; ?>manifest.webmanifest">

    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>

    <link href="<?php echo $assetPrefix; ?>css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo $assetPrefix; ?>css/login.css" rel="stylesheet">
    <link href="<?php echo $assetPrefix; ?>css/general.css" rel="stylesheet">
    <link href="<?php echo $assetPrefix; ?>css/custom.css" rel="stylesheet">
    <link href="<?php echo $assetPrefix; ?>css/app.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css?family=Lato:100,300,400,700,900,100italic,300italic,400italic,700italic,900italic" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Arvo:400,700" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Exo:100,200,400" rel="stylesheet" type="text/css">

<?php foreach ($pageStyles as $stylePath): ?>
    <link href="<?php echo $assetPrefix . $stylePath; ?>" rel="stylesheet">
<?php endforeach; ?>

  </head>
