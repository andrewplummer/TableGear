<?



include("include/TableGear1.6.php");

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






// DELETE ME

$options = array();
$options["database"] = array();


// Database host: if omitted defaults to localhost.
// $options["database"]["host"]        = "<DATABASE_HOST>",

// Basic database information. These are required.
$options["database"]["name"]        = "demos";
$options["database"]["username"]    = "root";
$options["database"]["password"]    = "";
$options["database"]["table"]       = "labs_tablegear2";



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
//$table->fetchData("SELECT SQL_CALC_FOUND_ROWS <FIELD1>,<FIELD2> FROM <DATABASE_TABLE> WHERE <etc..>");


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>TableGear for jQuery</title>
  <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
  <script type="text/javascript" src="javascripts/TableGear1.6-jQuery.js"></script>
  <link type="text/css" rel="stylesheet" href="stylesheets/tablegear.css" />
</head>
<body>
  <div>
    <?= $table->getTable() ?>
  </div>
<?= $table->getJavascript("jquery") ?>
</body>
</html>
