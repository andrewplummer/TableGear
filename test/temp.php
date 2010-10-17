<?php

echo 'hooo';
include("../lib/include/TableGear1.6.php");
$tg = new TableGear(array("database" => array( "username" => "root" "password" => "", "name" => "andrewpl_demos", "table" => "labs_tablegear" )));
//$tg->getTable(); 
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>TableGear for jQuery</title>
  <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
  <script type="text/javascript" src="../lib/javascripts/TableGear1.6-jQuery.js"></script>
  <link type="text/css" rel="stylesheet" href="../stylesheets/tablegear.css" />
</head>
<body>
  <div>
    <?= $tg->getTable() ?>
  </div>
<?= $tg->getJavascript("jquery") ?>
</body>
</html>
