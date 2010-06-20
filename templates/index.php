<?



include("../lib/TableGear1.5.2.php");

/*

$options = array();
$options["database"] = array();


// Database host: if omitted defaults to localhost.
// $options["database"]["host"]        = "<DATABASE_HOST>",

// Basic database information. These are required.
$options["database"]["name"]        = "<DATABASE_NAME>";
$options["database"]["username"]    = "<DATABASE_USERNAME>";
$options["database"]["password"]    = "<DATABASE_PASSWORD>";
$options["database"]["table"]       = "<DATABASE_TABLE>";



// This will prevent the default query (which selects all fields in the table) from being run automatically.
// Turn this on when using custom queries (fetchDataArray). Note that "table" above is still necessary for
// update/insert to work.

// $options["database"]["noAutoQuery"] = true;


// Sortable fields.
// $options["sortable"]  = array("<FIELD1>", "<FIELD2", "ETC...");

// Editable fields.
// $options["editable"]  = array("<FIELD1>", "<FIELD2", "ETC...");

// Fields that use textareas instead of standard inputs.
// $options["textareas"] = array("<FIELD1>", "<FIELD2", "ETC...");

// Fields that use select boxes instead of standard inputs. ...MORE!!!
// $options["selects"]   = array("<FIELD1>" => array("yes", "no"));

// Format data from the database before it is output to the browser.
// $options["formatting"]  = array("<DATE_FIELD>" => "eDate[n/j/Y]");

// Format user input before it is inserted into the database.
// $options["inputFormat"] = array("<DATE_FIELD>" => "timestamp");

// If true, rows can be deleted.
// $options["allowDelete"]    = true;

// Text or an HTML element for the delete label. MORE!!
// $options["deleteRowLabel"] = array("tag" => "img", "attrib" => array("src" => "/images/delete.gif"));

// If set, pagination is allowed on the table using the parameters specified.
// $options["pagination"] = array();
// $options["pagination"]["perPage"]   = 10;
// $options["pagination"]["prev"]      = "Prev";
// $options["pagination"]["next"]      = "Next";
// $options["pagination"]["linkCount"] = 10;


$table = new TableGear($options);

// If you are using a custom query, you can specify it here. ... MORE!
$table->fetchDataArray("SELECT <FIELD1>,<FIELD2> FROM <DATABASE_TABLE> WHERE <etc..>");

 */


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
