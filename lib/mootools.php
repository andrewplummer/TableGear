<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>TableGear for Mootools</title>
  <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/mootools/1.2.4/mootools-yui-compressed.js"></script>
  <script type="text/javascript" src="javascripts/TableGear1.6-MooTools.js"></script>
  <link type="text/css" rel="stylesheet" href="stylesheets/tablegear.css" />
</head>
<body>
  <div>
    <?= $table->getTable() ?>
  </div>
<?= $table->getJavascript('mootools') ?>
</body>
</html>
