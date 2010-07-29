<?

include("include/TableGear1.6.php");

//
// TableGear Usage:
//
// This is the starting point for using TableGear!
// There are a lot of options here but don't worry... most of what you'll see here are comments to
// help you. The uncommented options are required, but everything else is optional.
// <FIELD> marks an option that needs to be filled in with your database information.
// If you are having trouble, check out the docs here:
//
// http://andrewplummer.com/code/tablegear/
//

$options = array();
$options["database"] = array();
$options["pagination"] = array();


// Database host: if omitted defaults to localhost.
// $options["database"]["host"]        = "<DATABASE_HOST>",


// Basic database information. These are required.

$options["database"]["name"]        = "<DATABASE_NAME>";
$options["database"]["username"]    = "<DATABASE_USERNAME>";
$options["database"]["password"]    = "<DATABASE_PASSWORD>";
$options["database"]["table"]       = "<DATABASE_TABLE>";



// -- Row Deletion
//
// allowDelete     = Let rows be deleted from the database.
// deleteRowLabel  = Text or an element that is used for the delete label.
//                   This accepts a string or an HTML object (more info above).
//
// $options["allowDelete"] = false;
//
// $options["deleteRowLabel"] = "×";  // A simple character.
// $options["deleteRowLabel"] = array("tag" => "img", "attrib" => array("src" => "images/delete.gif")); // Default delete image.





// -- Default Sort
//
// Specify a default sort for the table. For descending order just put DESC after
// the field as you would in SQL. For sorting on multiple fields, pass an array.

// $options["database"]["sort"]       = "<FIELD1>";                      // Sorting on a single field.
// $options["database"]["sort"]       = array("<FIELD1>", "<FIELD2>");   // Sorting on multiple fields.



// -- Database Fields
//
// This option will limit the fields selected in the auto query to those specified in the array.
// Also note that if you are using a custom query with fetchData (below) and are limiting the fields
// returned in that query, you MUST specify those exact fields here as well if you want to be able
// to add new rows!

// $options["database"]["fields"]   = array("<FIELD1>", "<FIELD2>");



// -- noAutoQuery (for custom queries)
//
// This will prevent the default query (which selects all fields in the table) from being run automatically.
// Turn this on when using custom queries (fetchData). Note that "table" above is still necessary for
// update/insert to work.

// $options["database"]["noAutoQuery"] = true;



// -- fetchEmptyRow (for custom defaults)
//
// This will find all fields in the database for the purpose of inserting new rows.
// Turn this on when your tables define defaults that are not NULL or 0.
// One example of this is a CURRENT_TIMESTAMP default for a date field.
// Note that this option cannot be used when you are using a custom query with fetchData

// $options["database"]["fetchEmptyRow"] = true;






// Note that the following 3 options (sortable, editable, textareas, and selects) can also
// take "all" as a parameter to turn the option on for all fields.



// -- Sortable fields.
//
// Defaults to all.

// $options["sortable"]  = array("<FIELD1>", "<FIELD2", "ETC...");



// -- Editable fields.
//
// Defaults to all except the auto increment field.

// $options["editable"]  = array("<FIELD1>", "<FIELD2", "ETC...");



// -- Textareas
//
// Fields that use textareas instead of standard inputs.

// $options["textareas"] = array("<FIELD1>", "<FIELD2>", "ETC...");



// -- Select Elements
//
// Fields that use select boxes instead of standard inputs.
// For simple, static select elements, simply pass an array (key => value maps to label => value).
// For incremented selects, use "increment[<OPTIONS>]". Options are as follows:
//
// min   = The minimum value. <option> tags will not be generated below this value.
// max   = The maximum value. <option> tags will not be generated above this value.
// abs   = Defines static values that don't change depending on the current value of the field.
// step  = The amount by which each <option> increments. If 10, values will climb by 10, etc.
// range = The range of <option> tags to be generated (will be + 1 if even to include the current value).
//         Note that this may be different from the actual numeric range depending on the value of STEP.

// $options["selects"] = array("<FIELD>" => array("yes", "no"));             // Will output <option value="yes">yes</option>, etc.
// $options["selects"] = array("<FIELD>" => array("yes" => 1, "no" => 0));   // Will output <option value="1">yes</option>, etc.
// $options["selects"] = array("<FIELD>" => "increment[min=1,max=10,abs]");  // Will output <option> tags for values 1 - 10.
// $options["selects"] = array("<FIELD>" => "increment[range=10]");          // If the current value is 18, will output <option> tags with values from 13 - 23.
// $options["selects"] = array("<FIELD>" => "increment[range=5,step=5]");    // If the current value is 18, will output <option> tags with values 8,13,18,23,28.


// Advanced usage of "selects" allows for some pretty cool features, like date formatting.
// The example below would output 11 <option> elements that increment in steps of 86400, or 1 day in seconds.
// If you add a "formatting" option (explained below) to this field, the result would be a select element that
// lets you select 5 days before and 5 days after the current day, displayed in any date format, and sends
// the result to the database as a timestamp (in seconds).

// $options["selects"] = array("number" => "increment[range=11,step=86400]");





// -- Data Formatting
//
// Format data from the database before it is output to the browser.
// Four main types are available with various options:



// -- Date formatting:
//
// $options["formatting"]["<FIELD>"] = "date";        // Formats a date in the format "January 1, 2010".
// $options["formatting"]["<FIELD>"] = "date[Y-m-d]"; // Formats a date using a custom format (here "2010-01-01").




// -- Currency formatting:
//
// precision = The precision to calculate the number.                 Defaults to 2.
// pad       = When true, pads the number out to the precision value. Defaults to false.
// prefix    = A string to use before the currency value.             Default is null.
// suffix    = A string to use string the currency value.             Default is null.
// thousands = Character to use for the thousands delimiter.          Defaults to ",".
// decimal   = Character to use for the decimal delimiter.            Defaults to ".".

// $options["formatting"]["<FIELD>"] = "currency[prefix=$";                      // $4,000
// $options["formatting"]["<FIELD>"] = "currency[prefix=$,pad=true";             // $4,000.00
// $options["formatting"]["<FIELD>"] = "currency[prefix=$,pad=true,suffix= USD"; // $4,000.00 USD
// $options["formatting"]["<FIELD>"] = "currency[prefix=$,thousands=.";          // $4.000



// -- Numeric formatting
//
// precision = The precision to calculate the number.        Defaults to 0.
// thousands = Character to use for the thousands delimiter. Defaults to ",".
// decimal   = Character to use for the decimal delimiter.   Defaults to ".". (Note: Use "COMMA" for a comma.)

// $options["formatting"["<FIELD>"] = "numeric";                                         // 5,376
// $options["formatting"["<FIELD>"] = "numeric[precision=2";                             // 5,376.24
// $options["formatting"["<FIELD>"] = "numeric[precision=-2";                            // 5,400
// $options["formatting"["<FIELD>"] = "numeric[precision=2,thousands= ,decimal=COMMA]";  // 5 376,24




// -- Memory formatting
//
// precision = The precision to calculate the number.                                Defaults to 0.
// unit    = The unit that the memory is in. Can be written out ("kilobytes").       Defaults to "b".
// small   = Unless set to true, small values (below Megabytes) will be rounded off. Defaults to false.
// capital = Makes the units capitalized ("MB").                                     Default is false.
// camel   = Makes the units camel-case ("Mb").                                      Default is false.
// space   = Adds a space between the number and the unit ("24 kb").                 Default is false.
// auto`   = If true, memory will be displayed in the most appropriate unit.         Default is false.


// $options["formatting"]["<FIELD>"] = "memory[auto]";                           // 24b
// $options["formatting"]["<FIELD>"] = "memory[auto,unit=mb]";                   // 24mb
// $options["formatting"]["<FIELD>"] = "memory[auto,unit=mb,camel]";             // 24Mb
// $options["formatting"]["<FIELD>"] = "memory[auto,unit=mb,capital]";           // 24MB
// $options["formatting"]["<FIELD>"] = "memory[auto,unit=mb,space]";             // 24 mb
// $options["formatting"]["<FIELD>"] = "memory[auto,unit=gb,precision=2]";       // 24.42gb
// $options["formatting"]["<FIELD>"] = "memory[auto,unit=kb,precision=2,small]"; // 24.42kb




// -- Totals
//
// You can add totals to the bottom of the table by adding the "totals" option here.
//
// $options["totals"] = array("<FIELD1>", "<FIELD2>", "etc...");
//
//
//$options["totals"] = array("memory","number");







// -- Input Formatting
//
// Format user input before it is entered into the database.
// Three main types are available with various options:
//
// date     = A very educated guess of various date formats. Accepts values such as
//            "today" and "next week wednesday" as well as Asian dates. The [] parameter
//            following this format specifies the format to output to the database, with
//            MySQL DATE as the default. Use "timestamp" as the format to store the date as a timestamp.
//
// eDate    = Same as date, but anticipates European dates: 1/4/2010 = April 1, 2010.
//
// numeric  = This will find numeric values from user input.



// $options["inputFormat"]["<FIELD>"] = "date";            // Accepts human-readable dates like "January 1, 2001" or "tomorrow".
//                                                                     Result will be stored in standard MySQL format.
//
// $options["inputFormat"]["<FIELD>"] = "date[F j, Y]";    // Accepts human-readable dates like "January 1, 2001" or "tomorrow".
//                                                                     Result will be stored as "July 8, 2010".

// $options["inputFormat"]["<FIELD>"] = "date[timestamp]"; // Accepts human-readable dates like "January 1, 2001" or "tomorrow".
//                                                                     Result will be stored as a timestamp.

// $options["inputFormat"]["<FIELD>"] = "eDate";           // Accepts human-readable dates like "January 1, 2001" or "tomorrow".
//                                                                     Ambiguous dates like 1/4/2010 will be taken as European format (= April 1st, 2010)

// $options["inputFormat"]["<FIELD>"] = "numeric";         // Finds numeric values. ex. $2,432,44.00 => 243244.00







// -- Transform
//
// Although there are a few different types of data formats, sometimes this isn't enough to get your data
// into the format it needs to be in. You can use "transform" to transform your data into exactly the format
// it needs to be. One example of this is transforming an image url into an actual image. To access your data,
// just use the following keyword (include the curly braces):
//
// {DATA}       = The data as it is in the database.
// {KEY}        = The primary key for the row where {DATA} occurs.
// {FIELD}      = The name of the field where {DATA} occurs.
// {COLUMN}     = The number of the column where {DATA} occurs.
// {RANDOM}     = A random number from 0 to 9999.
// {ASSOCIATED} = Another field on the same row in which {DATA} occurs. To use this, you must specify the field
//                using "associate" (see below).


// $options["transform"]["<FIELD1>"] = "The data: {DATA} occurred in field {FIELD} (or column {COLUMN}) in row {KEY}.";  // Illustrating how to use a transform.
//
// $options["transform"]["<FIELD1>"] = array("tag" => "img",
//                                           "attrib" => array("src" => "/path/to/thumbnails/{DATA}")); // A simple url to image transform.
//
// $options["transform"]["<FIELD1>"] = array("tag" => "img",
//                                           "attrib" => array("src" => "/path/to/thumbnails/{DATA}",
//                                                             "alt" => "An image of {ASSOCIATED}"),
//                                           "associate" => "title");                                 // Using associated data by specifying 



// -- Pagination
//
// Very large tables may present performance issues, and are most easily manageable
// when broken up by paginating. There are four options to specify:
//
// prev      = The text for the "prev" link. If null, no link will be shown.
// next      = The text for the "next" link. If null, no link will be shown.
// perPage   = How many rows to display per page.
// linkCount = How many links to display on either side of the current page. For example:
//             linkCount = 1, 10 pages, current page = 5 will result in:   4 5 6
//             linkCount = 2, 10 pages, current page = 5 will result in:   3 4 5 6 7
//             linkCount = 2, 10 pages, current page = 9 will result in:   7 8 9 10
//             etc...


// $options["pagination"]["perPage"] = 10;  // 10 rows per page.
// $options["pagination"]["prev"] = "prev"; // "prev" link will be shown.
// $options["pagination"]["next"] = "next"; // "next" link will be shown.
// $options["pagination"]["linkCount"] = 2; //  2 links on each side of the current page.





// Instanciates the table. This must be included here!

$table = new TableGear($options);



// If you need to use a custom query instead of the default (fetching everything), you can specify it here.
// You can use any syntax in the query you want, however you MUST include the primary key field in the SELECT
// clause, otherwise none of the editing functionality will work! Also, if you need pagination on the table
// you MUST include "SQL_CALC_FOUND_ROWS" after the SELECT clause and not have any LIMIT or ORDER BY clauses!
//
// $table->fetchData("SELECT SQL_CALC_FOUND_ROWS <FIELD1>,<FIELD2> FROM <DATABASE_TABLE> WHERE <etc..>");



?>
