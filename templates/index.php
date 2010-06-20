<?



include("../lib/TableGear1.5.2.php");



$tg = new TableGear(array(
  "database"      => array("username" => "andrewpl_user",
                           "password" => "k2346",
                           "database" => "andrewpl_demos",
                           "table" => "labs_tablegear",
                           "noAutoQuery" => false),
  "sortable"      => "all",
  "editable"      => "all",
  "allowDelete" => true,
  "deleteRowLabel" => array("tag" => "img", "attrib" => array("src" => "/images/icons/delete.gif")),
  "textareas"      => array("item"),
  "selects" => array("item" => array("hmm", "yes", "no")),
  "formatting"    => array("date" => "eDate[n/j/Y]"),
  "inputFormat"    => array("date" => "timestamp"),
  //"pagination"     => array("perPage" => 10, "prev" => "Prev", "next" => "Next", "linkCount" => 10 ),


  "formatting"    => array("price" => "currency[prefix=$]")
));

//$tk->fetchDataArray("SELECT price,date FROM labs_tablegear WHERE price > 300");



?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>TableGear for Mootools</title>
  <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/mootools/1.2.4/mootools-yui-compressed.js"></script>
  <script type="text/javascript" src="../lib/TableGear1.5.2.js"></script>
  <link type="text/css" rel="stylesheet" href="/css/demo.css" />
  <link type="text/css" rel="stylesheet" href="stylesheets/tablegear.css" />
</head>
<body>
  <div>
    <?= $tg->getTable() ?>
  </div>
<?= $tg->getJavascript() ?>
</body>
</html>
