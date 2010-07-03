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
// Turn this on when using custom queries (fetchData). Note that "table" above is still necessary for
// update/insert to work.

// $options["database"]["noAutoQuery"] = true;

// This will find all fields in the database for the purpose of inserting new rows.
// Turn this on when your tables define defaults that are not NULL or 0.
// One example of this is a CURRENT_TIMESTAMP default for a date field.
// Note that this option cannot be used when you are using a custom query with fetchData

// $options["database"]["fetchEmptyRow"] = true;


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

// If you need to use a custom query instead of the default (fetching everything), you can specify it here.
// You can use any syntax in the query you want, however you MUST include the primary key field in the SELECT
// clause, otherwise none of the editing functionality will work! Also, if you need pagination on the table
// you MUST include "SQL_CALC_FOUND_ROWS" after the SELECT clause and not have any LIMIT or ORDER BY clauses!
$table->fetchData("SELECT SQL_CALC_FOUND_ROWS <FIELD1>,<FIELD2> FROM <DATABASE_TABLE> WHERE <etc..>");

 */


$username = "root";
//$username = "andrewpl_user";
$password = "";
//$password = "k2346";
$db = "demos";
//$db = "andrewpl_demos";

$tg = new TableGear(array(
  "database"      => array("username" => $username,
                           "password" => $password,
                           "database" => $db,
                           "fields" => array("field2"),
                           "table" => "labs_tablegear2"
                         ),

  "sortable"      => "all",
  "allowDelete" => true,
  "deleteRowLabel" => array("tag" => "img", "attrib" => array("src" => "images/delete.gif")),
  "textareas"      => array("item"),
  "selects"        => array("date" => "increment[step=86400,range=20,convert_time]", "item" => array("hmm", "yes", "no")),
  "inputFormat"    => array("date" => "date"),
  "formatting"     => array("date" => "date", "price" => "currency[prefix=$]", "memory" => "memory[auto]")
 // "pagination"     => array("perPage" => 5, "prev" => "Prev", "next" => "Next", "linkCount" => 10 ),
));

//$tg->fetchData("SELECT SQL_CALC_FOUND_ROWS id,field2 FROM labs_tablegear2");

//
//TODO MAKE SURE SHIT DOESNT ERROR JUST BECAUSE I FUCKED UP THE FIELDS



?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>TableGear for Mootools</title>
  <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/mootools/1.2.4/mootools-yui-compressed.js"></script>
  <script type="text/javascript" src="../lib/TableGear1.5.2.js"></script>
  <link type="text/css" rel="stylesheet" href="stylesheets/tablegear.css" />
</head>
<body>
  <div>
    <?= $tg->getTable() ?>
  </div>
<?= $tg->getJavascript() ?>
</body>
</html>
