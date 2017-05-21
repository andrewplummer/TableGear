## TableGear.php

### 1.6.1

Fixed a major bug with new rows in jQuery client.
Fixed a PHP bug blocking last_insert_id from functioning properly.
Added set names utf8 support for mysql.

### 1.6

jQuery client plugin added.
Composite primary keys are now allowed.
Using custom queries can now save data back to the database.
Release template is now much easier to understand and tweak. Almost all options are in the implementation code commented out with detailed explanations about how to use them.
Auto-incremented values now are updated inside the table.
Primary key field is no longer hidden by default.
All fields except auto-increment are now editable by default.
database["columns"] changed to database["fields"]
database["database"] changed to database["name"]
database["host"] now allowed in addition to database["server"]
"Range" sub-parameter when using incremented selects now refers to the actual option elements instead of the number range. If you have a select incrementing with steps of 100, and you want 10 option elements, range would be 10 instead of 10 * 100 = 1000.
Transformed data will now return from the server with the transform applied.

### 1.5.2

Different sql query for finding primary keys. This increases compatibility with older versions of MySQL.
New rows can't be added when paginating, except on the last page.
Sorting on paginated tables is now done directly through html links. Changing the sort takes you back to the first page. This plus #2 brings a lot more UI intuitiveness for pagination.

### 1.5.1

Added ability to have more than one table in a page. Mostly this required putting an automatic counter so that each table would have a unique id (although this can also be specified manually), and also letting the incoming request fall through the constructor if it is not intended for the relevant table.

### 1.5

Major overhaul to allow tables to add rows easily. This included adding a new form for new rows, which is then hidden, the empty row of which is used as a blank "template" for the rows added to the table, which required a major refactoring of HTML insertion code. Appropriate handling of insert vs. update statements in the back end. Also refactored some of the json data structure to use POST body arrays more effectively.

### 1.22

Fixed a bug not allowing the table to be updated with the numeral 0. Also fixed {KEY} parameter in data transforms returning the row id instead of the primary key entry.

### 1.21

Added "validate" parameter in the constructor to limit user input.

### 1.2

Added pagination. Fixed TableGear->fetchDataArray method so that custom queries can be used.

## TableGear.js

### 1.6

jQuery support added!

### 1.5.2

Pageup/pagedown buttons now work when paginating.

### 1.5.1

Added ability to have more than one table in a page. This required passing the associated table id through the json, and splitting up the newDataRow row and addNewRow form to use different ids. Fixed a bug where null values in "numeric" fields cause an error on sort.

### 1.5

Addition of new rows. This included JS to clone the emptyRowData row, which is included in a form that is hidden for graceful degredation without Javascript. Overhaul of row init function to accept new rows as they come in. Refactoring of params passed through the ajax request, and a few refactoring/cleanups in general.

### 1.21

Scrapped the idea of using HTML elements for loading blocks. CSS is much more flexible here. From this version on, cells will have a .loading class added to them as the AJAX request fires (or on the row for a delete request). The previous functionality can still be forced by adding custom elements through HTML injection and hiding/showing using this .loading class.
Back to TableGear
Software Photography
