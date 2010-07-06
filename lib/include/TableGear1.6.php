<?php

/*
 *
 *  TableGear for PHP (An Intuitive Data Table Management Class)
 *
 *  Version: 1.6
 *  Documentation: AndrewPlummer.com (http://www.andrewplummer.com/code/tablegear/)
 *  License: MIT-style License
 *
 *  Copyright (c) 2010 Andrew Plummer
 *
 *
 */

$tgTableID = 0;

define("MYSQL_DATE_FORMAT", "Y-m-d H:i:s");


class TableGear
{

  var $processHTTP         = true;           // Process data submitted by HTTP
  var $indent              = 0;              // HTML indent base
  var $autoHeaders         = true;           // Automatically get the headers from field names
  var $readableHeaders     = true;           // Creates readable headers from camelCase and underscore field names.
  var $noDataMessage       = "- No Data -";  // The message to display when no data is available
  var $newRowLabel         = "New Row";
  var $primaryKeyDelimiter = "|";
  var $addNewRows          = true;
  var $_curIndent          = 0;              // For HTML output
  var $_hasTags            = false;          // For HTML output

  function TableGear($options)
  {
    global $tgTableID;
    $this->editableFields = array();
    if(!isset($options["editable"])) $options["editable"] = "allExceptAutoIncrement";
    if($options["editable"]) $this->form = array("url" => $_SERVER["REQUEST_URI"], "method" => "post", "submit" => "Update");
    $tgTableID++;
    $this->table = array("id" => "tgTable");
    $this->headers = array("EDIT" => "Edit Row", "DELETE" => "Delete Row");
    $this->_setOptions($options);
    if($tgTableID > 1) $this->table["id"] .= $tgTableID;
    if($this->database) $this->connect();
    if($this->processHTTP) $this->_checkSubmit();
    if(!$this->database["noAutoQuery"]) $this->fetchData();
    $this->_checkColumnShift();
  }



  /* Functions for working with the database */

  function connect()
  {
    $db = $this->database;
    if(!$db["database"] || !$db["username"]) trigger_error("Database info required!", E_USER_ERROR);
    if(!$db["table"]) trigger_error("Database table must be specified.", E_USER_ERROR);

    if($db["server"])   $server = $db["server"];
    elseif($db["host"]) $server = $db["host"];
    else                $server = "localhost";

    $this->connection = mysql_connect($server, $db["username"], $db["password"]);
    mysql_select_db($db["database"], $this->connection);
  }

  function query($query)
  {
    //echo "<br/>QUERY: $query<br/>"; // Leave for debug
    if(!$this->connection) trigger_error("No database connection established!", E_USER_ERROR);
    $result = mysql_query($query, $this->connection);
    $this->_affectedRows = mysql_affected_rows($this->connection);
    if(!$result){
      $this->database["error"] = mysql_error();
      return false;
    } elseif($result && $result != 1){
      $data = array();
      while($row = mysql_fetch_assoc($result)) array_push($data, $row);
      return $data;
    } else {
      return true;
    }
  }

  function fetchData($query = null)
  {
    if(!$query && !$this->database["table"]) return;
    $table = $this->database["table"];
    // Get the sorting field
    if($_GET["sort"]){
      $sort = $_GET["sort"];
      $desc = $_GET["desc"] ? " DESC" : " ASC";
    } elseif($this->database["sort"]){
      list($sort, $params) = $this->_getParams($this->database["sort"]);
      $desc = ($params == "desc") ? " DESC" : " ASC";
    } else {
      $sort = $this->_getPrimaryKeyNamesAsString(",");
    }
    $auto_query = !isset($query);
    if($auto_query){
      if(!$this->database["table"]) return;
      $fields = $this->database["fields"] ? implode(",", $this->database["fields"]) : "*";
      $query = "SELECT SQL_CALC_FOUND_ROWS $fields FROM $table ORDER BY $sort$desc";
    }
    if($this->pagination){
      if(!$auto_query && isset($sort)){
        // Add the sort field onto the query for custom queries,
        // but only if we have pagination otherwise sort is handled manually.
        $query .= " ORDER BY $sort$desc";
      }
      $page = $this->pagination["currentPage"] = ($_GET["page"]) ? $_GET["page"] : 1;
      if(!$this->pagination["perPage"]) $this->pagination["perPage"] = 10;
      $min = ($page - 1) * $this->pagination["perPage"];
      $perPage = $this->pagination["perPage"];
      $query .= " LIMIT $min, $perPage";
    }
    $data = $this->query($query);
    if($this->pagination){
      $result = mysql_query("SELECT FOUND_ROWS() AS total");
      $row = mysql_fetch_assoc($result);
      $this->totalRows = $row["total"];
      $this->pagination["totalPages"] = ceil($this->totalRows / $this->pagination["perPage"]);
    }
    if(!$data) return;
    $this->data = array();
    foreach($data as $row){
      $entry = array();
      $entry["key"] = $this->_getPrimaryKeyValues($row);
      $entry["data"] = $row;
      array_push($this->data, $entry);
    }
  }


  function _getPrimaryKeyColumns()
  {
    // This is a shortcut that the user can set. Only works with non-composite PKs.
    if($this->database["key"]) return array("name" => $this->database["key"]);
    // This will store the resulting PK fields fetched from the database.
    if($this->database["keys"]) return $this->database["keys"];
    $table = $this->database["table"];
    $columns = $this->query("SHOW COLUMNS FROM $table WHERE `Key`='PRI'");
    $keys = array();
    $this->primaryKeyColumnsByName = array();
    foreach($columns as $column){
      $key = array();
      $key["name"]    = $column["Field"];
      // MySQL appears to not allow a value of NULL as a default for a primary key field.
      $key["default"] = $column["Default"];
      if(stripos($column["Extra"], "auto_increment") !== -1){
        $key["auto"] = true;
      }
      array_push($keys, $key);
      $this->primaryKeyColumnsByName[$key["name"]] = $key;
      if($this->database["fields"]){
        array_push($this->database["fields"], $key["name"]);
      }
    }
    if(!count($keys) === 0) trigger_error("Primary key is required for table $table.", E_USER_ERROR);
    $this->database["keys"] = $keys;
    return $keys;
  }

  function _limitQueryByPrimaryKey($query, $key){
    $where = array();
    $values = explode($this->primaryKeyDelimiter, $key);
    $primaryKeys = $this->_getPrimaryKeyColumns();
    for($i = 0; $i < count($primaryKeys); $i++){
      $field = $primaryKeys[$i]["name"];
      $value = $values[$i];
      if(!is_numeric($value)) $value = '"'.mysql_real_escape_string($value, $this->connection).'"';
      array_push($where, "$field=$value");
    }
    $where = implode(" AND ", $where);
    return "$query WHERE $where";
  }

  function _getPrimaryKeyNamesAsString($delimiter)
  {
    if(!$delimiter) $delimiter = $this->primaryKeyDelimiter;
    $result = array();
    foreach($this->_getPrimaryKeyColumns() as $key){
      array_push($result, $key["name"]);
    }
    return implode($delimiter, $result);
  }

  function _getPrimaryKeyValues($data){
    $result = array();
    foreach($this->_getPrimaryKeyColumns() as $key){
      $value = $data[$key["name"]];
      array_push($result, $data[$key["name"]]);
    }
    return implode($this->primaryKeyDelimiter, $result);
  }

  function _getPrimaryKeyValuesAfterInsertion($data)
  {
    $primaryKeys = $this->_getPrimaryKeyColumns();
    $values = array();
    foreach($primaryKeys as $key){
      if($key["auto"]){
        // Note here that in mySQL it IS possible to have a composite primary key with a single field set
        // to auto_increment. In that case, LAST_INSERT_ID() will return 0, despite the fact that the field
        // gets incremented, effectively making it impossible to know the data in that field. I am NOT handling
        // that case here ("0" will be foreced into the resulting JSON data), and I can't see any reason it would
        // make sense to be using a composite primary key AND an auto_increment field in the same table. If there
        // IS a good reason and this is some kind of huge problem, contact me, especially if you have some good ideas
        // about how to retrieve the result without a reliable means of getting the last inserted id.
        $this->_json["auto"] = $key["name"];
        array_push($values, mysql_insert_id());
      } else {
        $value = $data[$key["name"]];
        if($value){
          array_push($values, $value);
        } else {
          // We know that this primary key is not auto-increment, and we don't have a value from the incoming user data,
          // so the value should be the default value for this field.
          array_push($values, $key["default"]);
        }
      }
    }
    return implode($this->primaryKeyDelimiter, $values);
  }

  function _getPrimaryKeyValuesAfterUpdate($updatedData, $cKey)
  {
    $primaryKeys = $this->_getPrimaryKeyColumns();
    $currentValues = explode($this->primaryKeyDelimiter, $cKey);
    $values = array();
    foreach($primaryKeys as $i => $key){
      $updated = $updatedData[$key["name"]];
      if($updated){
        array_push($values, $updated);
      } else {
        array_push($values, $currentValues[$i]);
      }
    }
    return implode($this->primaryKeyDelimiter, $values);
  }


  /* Functions for working with the data */



  function injectColumn($array, $position = "first", $fieldName = null)
  {
    if(!$this->data) return;

    foreach($this->data as $rowIndex => $row){
      $data = $row["data"];
      $column = count($data) + 1;
      if($fieldName) $data[$fieldName] = $array[$rowIndex];
      else $data[$column] = $array[$rowIndex];
      $this->data[$rowIndex]["data"] = $data;
    }
    $col = $fieldName ? $fieldName : $column;
    $this->shiftColumn($col, $position);
  }

  function shiftColumn($col, $pos)
  {
    if(is_numeric($col)){
      $keys = array_keys($this->data[0]["data"]);
      $col = $keys[$col-1];
    }
    if(!is_numeric($pos)) list($pos, $params) = $this->_getParams($pos);
    foreach($this->data as $rowIndex => $row){
      $new = array();
      $currentColumn = 1;
      if($pos == "first"){
        $new[$col] = $row["data"][$col];
        $currentColumn++;
      }
      foreach($row["data"] as $field => $data){
        if($pos == "before" && $params == $field){
          $new[$col] = $row["data"][$col];
          $currentColumn++;
        }
        if($pos == $currentColumn){
          $new[$col] = $row["data"][$col];
          $currentColumn++;
        }
        if($field != $col){
          $new[$field] = $data;
          $currentColumn++;
        }
        if($pos == "after" && $params == $field){
          $new[$col] = $row["data"][$col];
          $currentColumn++;
        }
      }
      if($pos == "last"){
        $new[$col] = $row["data"][$col];
      }
      $this->data[$rowIndex]["data"] = $new;
    }
  }

  function _fetchHeaders()
  {
    $headers = array();
    if($this->form && $this->editable) array_push($headers, array("field" => "EDIT", "html" => $this->headers["EDIT"], "attrib" => array("class" => $this->_checkColumnClass("EDIT"))));
    if(count($this->data) > 0){
      $firstRow = reset($this->data);
      $column = 1;
      foreach($firstRow["data"] as $field => $data){
        $sortable = $this->_testForOption("sortable", $field, $column) ? true : false;
        $sortType = $this->_getSortType($field);
        $class = $this->_addClass("sortable", null, $sortable);
        $class = $this->_addClass($sortType, $class);
        if($this->primaryKeyColumnsByName[$field]){
          $class = $this->_addClass("primary_key", $class);
          if($this->primaryKeyColumnsByName[$field]["auto"]){
            $class = $this->_addClass("auto_increment", $class);
          }
        }
        if($this->headers[$field]) $userHeader = $this->headers[$field];
        elseif($this->headers[$column]) $userHeader = $this->headers[$column];
        else $userHeader = null;
        $html = null;
        if(is_array($userHeader)){
          $html = $userHeader["html"];
          $class = $this->_addClass($userHeader["class"], $class);
        } elseif(is_string($userHeader)){
          $html = $userHeader;
        }
        if(!$html && $this->autoHeaders) $html = $this->_autoFormatHeader($field);
        if($this->pagination && $this->pagination["totalPages"] != 1){
          $desc = ($_GET["sort"] == $field && !$_GET["desc"]) ? "true" : null;
          $href = $this->_modifyURIParams(array("sort" => $field, "desc" => $desc, "page" => null));
          if($_GET["sort"] == $field){
            $carat = array("tag" => "span", "attrib" => array("class" => "carat"));
            if($_GET["desc"]){
              $desc = null;
              $carat["html"] = "▼";
            } else {
              $desc = "true";
              $carat["html"] = "▲";
            }
            $html = array(array("tag" => "span", "html" => $html), $carat);
          }
          $link = array("tag" => "a", "html" => $html, "attrib" => array("href" => $href));
          $header = array("html" => $link);
        } else {
          $header = array("html" => $html, "attrib" => array("class" => $class, "id" => $userHeader["id"]));
        }
        array_push($headers, $header);
        $column++;
      }
    } elseif($this->connection){
      if(!$this->database["table"]) return;
      $columns = $this->query("SHOW COLUMNS FROM " . $this->database["table"]);
      if(!$columns) return;
      foreach($columns as $column){
        $field = $column["Field"];
        $header["field"] = $field;
        if($this->autoHeaders) $header["html"] = $this->_autoFormatHeader($field);
        else $header["html"] = $field;
        array_push($headers, $header);
      }
    }
    if($this->allowDelete && $this->form) array_push($headers, array("field" => "DELETE", "html" => $this->headers["DELETE"], "attrib" => array("class" => $this->_checkColumnClass("DELETE"))));
    return $headers;
  }

  function _fetchFooters()
  {
    $footers = array();  
    if($this->form && $this->editable) array_push($footers, $this->footers["EDIT"]);
    if(count($this->data) > 0){
      $firstRow = reset($this->data);
      $column = 1;
      foreach($firstRow["data"] as $field => $data){
        $footer = $this->footers[$column] ? $this->footers[$column] : $this->footers[$field];
        if($footer) array_push($footers, $footer);
        $column++;
      }
    }
    if($this->allowDelete && $this->form) array_push($footers, $this->footers["DELETE"]);
    return $footers;
  }

  function _fetchTotals()
  {
    $totals = array();
    if($this->form && $this->editable) $totals[0] = array("field" => "EDIT");
    $dataArray = $this->data ? $this->data : array($this->_fetchEmptyDataRow());
    foreach($dataArray as $rowIndex => $row){
      $column = 1;
      foreach($row["data"] as $field => $data){
        if($rowIndex == 0) $totals[$column] = array("field" => $field);
        if($this->_testForOption("totals", $field, $column)) $totals[$column]["text"] += $data;
        $column++;
      }
    }
    if($this->allowDelete && $this->form) $totals[$column+1] = array("field" => "DELETE");
    return $totals;
  }





  /* Functions for handling options and working with HTML */

  function getTable()
  {
    if(!$this->data) $this->data = array();
    if($this->form){
      $this->_openTag("form", array("action" => $this->form["url"], "method" => $this->form["method"], "id" => $this->form["id"], "class" => $this->form["class"]));
      $this->_outputHTML($this->custom["FORM_TOP"]);
      if($this->errors){
        $this->_openTag("fieldset", array("class" => "errors"));
        foreach($this->errors as $error){
          $this->_openTag("p");
          $this->_outputHTML($error["message"] . " for field ");
          $this->_openTag("span", array("class" => "field"));
          $this->_outputHTML('"'.$error["field"].'".');
          $this->_closeTag("span");
          $this->_closeTag("p");
        }
        $this->_closeTag("fieldset");
      }
      $this->_openTag("fieldset");
    }
    $this->_outputHTML($this->_custom["TABLE_TOP"]);
    $this->_openTag("table", array("id" => $this->table["id"], "class" => $this->table["class"]));
    $headers = $this->_fetchHeaders();
    if($headers || $this->title){
      $this->_outputHeaders($headers, true);
    }
    if($this->footers || $this->totals || $this->addNewRows){
      $this->_openTag("tfoot");
      if($this->totals){
        $totals = $this->_fetchTotals();
        $this->_openTag("tr");
        foreach($totals as $column => $footer){
          $class = isset($footer["text"]) ? $footer["field"] . " total" : null;
          $attrib["class"] = $this->_addClass($class);
          $attrib["class"] = $this->_addClass($this->_checkColumnClass($footer["field"], $column), $attrib["class"]);
          $this->_openTag("td", $attrib);
          $text = $footer["text"] ? $this->_getFormatted($footer["text"], $footer["field"], $column) : null;
          $this->_outputHTML($text);
          $this->_closeTag("td");
        }
        $this->_closeTag("tr");
      }
      if($this->footers){
        $footers = $this->_fetchFooters();
        $this->_openTag("tr");
        foreach($footers as $footer){
          $colspan = ($footer == end($footers)) ? count($headers) - count($footers) + 1 : null;
          $this->_openTag("th", array("colspan" => $colspan));
          $this->_outputHTML($footer);
          $this->_closeTag("th");
        }
        $this->_closeTag("tr");
      }
      $this->_closeTag("tfoot");
    }
    $this->_openTag("tbody");
    if(!$this->data){
      $this->_openTag("tr", array("class" => "noDataRow odd"));
      $this->_openTag("td", array("colspan" => count($headers)));
      $this->_outputHTML($this->noDataMessage);
      $this->_closeTag("td");
      $this->_closeTag("tr");
    } else {
      foreach($this->data as $rowIndex => $row){
        $this->_constructDataRow($row, $rowIndex);
      }
    }
    $this->_closeTag("tbody");
    $this->_closeTag("table");
    if($this->pagination && $this->totalRows > $this->pagination["perPage"]){
      $this->_openTag("div", array("class" => "pagination"));
      $this->_navLink("prev", $this->pagination["prev"]);
      $this->_navLink("next", $this->pagination["next"]);
      $this->_openTag("div", array("class" => "pages"));
      $page = $this->pagination["currentPage"];
      $linkCount = $this->pagination["linkCount"] ? $this->pagination["linkCount"] : 5;
      $min = ($page - $linkCount < 0) ? 1 : $page - $linkCount;
      $max = ($page + $linkCount > $this->pagination["totalPages"]) ? $this->pagination["totalPages"] : $page + $linkCount;
      for($i=$min;$i<=$max;$i++){
        $attribs = array();
        if($i == $this->pagination["currentPage"]){
          $attribs["class"] = "current";
          $tag = "span";
        } else {
          $uri = $this->_injectURLParam("page", $i);
          $attribs = array("href" => $uri);
          $tag = "a";
        }
        $this->_openTag($tag, $attribs);
        $this->_outputHTML($i);
        $this->_closeTag($tag);
      }
      $this->_closeTag("div");
      $this->_closeTag("div");
    }
    $this->_outputHTML($this->custom["TABLE_BOTTOM"]);
    if($this->form){
      foreach(array_unique($this->editableFields) as $field){
        $this->_openTag("input", array("type" => "hidden", "name" => "fields[]", "value" => $field));
      }
      $this->_openTag("input", array("type" => "hidden", "name" => "noDataMessage", "value" => $this->noDataMessage));
      $this->_openTag("input", array("type" => "hidden", "name" => "table", "value" => $this->table["id"]));
      if($this->pagination){
        $this->_openTag("input", array("type" => "hidden", "name" => "page", "value" => $this->pagination["currentPage"]));
      }
      if($this->form["submit"]){
        $this->_openTag("div", array("class" => "submit"));
        $this->_openTag("input", array("type" => "submit", "value" => $this->form["submit"]));
        $this->_closeTag("div");
      }
      $this->_closeTag("fieldset");
      $this->_outputHTML($this->custom["FORM_BOTTOM"]);
      $this->_closeTag("form");
    }
    if($this->_newRowsAllowed()){
      $addNewRowID = "addNewRow_" . $this->table["id"];
      $this->_openTag("form", array("action" => $this->form["url"], "method" => $this->form["method"], "id" => $addNewRowID, "class" => "newRow"));
      $this->_outputHTML(array("tag" => "h3", "html" => $this->newRowLabel));
      $this->_openTag("table");
      $this->_outputHeaders($headers);
      $this->_openTag("tbody");
      $emptyDataRow = $this->_fetchEmptyDataRow();
      $newDataRowID = "newDataRow_" . $this->table["id"];
      $this->_constructDataRow($emptyDataRow, 1, false, array("id" => $newDataRowID));
      $this->_closeTag("tbody");
      $this->_closeTag("table");
      $this->_openTag("div", array("class" => "submit"));
      $this->_openTag("input", array("type" => "hidden", "name" => "insert", "value" => "true"));
      $this->_openTag("input", array("type" => "hidden", "name" => "table", "value" => $this->table["id"]));
      $this->_openTag("input", array("type" => "submit", "value" => $this->form["submit"]));
      $this->_closeTag("div");
      $this->_closeTag("form");
    }
    echo "\n";
  }

  function getJavascript($library, $id = null)
  {
    if(!$id) $id = $this->table["id"];
    $editableCellsPerRow = count(array_unique($this->editableFields));
    $options = array("noDataMessage" => $this->noDataMessage, "editableCellsPerRow" => $editableCellsPerRow);
    if(!$this->_newRowsAllowed()) $options["addNewRows"] = false;
    if($this->pagination) $options["paginated"] = true;
    $options = $this->_jsonEncode($options);
    $this->_openTag("script", array("type" => "text/javascript"));
    echo "\n";
    if($library == "mootools"){
      echo "new TableGear('$id', $options);";
    }
    if($library == "jquery"){
      echo "$('#$id').tableGear($options);";
    }
    echo "\n";
    $this->_closeTag("script");
  }

  function _newRowsAllowed()
  {
    if($this->pagination && $this->pagination["currentPage"] != $this->pagination["totalPages"]) return false;
    return $this->addNewRows && $this->form;
  }

  function _constructDataRow($data, $rowIndex, $appendKey = true, $attrib = array())
  {
    $key = $data["key"];
    $attrib["class"] = ($rowIndex % 2) ? "even" : "odd";
    $this->_openTag("tr", $attrib);
    if($this->form && $this->editable){
      $attrib = array();
      $attrib["class"] = $this->_checkColumnClass("EDIT");
      $this->_openTag("td", $attrib);
      $id = $appendKey ? "edit".$key : null;
      $value = $key ? $key : "NULL_STRING";
      $this->_openTag("input", array("type" => "checkbox", "name" => "edit[]", "value" => $value));
      $this->_getLabel("editRowLabel", "edit".$key, "edit");
      $this->_closeTag("td");
    }
    $currentColumn = 1;
    foreach($data["data"] as $column => $data){
      $hottext = ($this->_testForOption("hotText", $column, $currentColumn)) ? true : false;
      $editable = ($this->_testForOption("editable", $column, $currentColumn)) ? true : false;
      $attrib["class"] = $this->_addClass("hotText", null, $hottext);
      $attrib["class"] = $this->_addClass("editable", $attrib["class"], $editable);
      $attrib["class"] = $this->_addClass($this->_checkColumnClass($column, $currentColumn), $attrib["class"]);
      $this->_openTag("td", $attrib);
      if($editable){
        array_push($this->editableFields, $column);
        if($this->loading) $this->_outputHTML($this->loading, "loading");
        $tag = $this->blockEditable ? "div" : "span";
        $this->_openTag($tag);
        $text = $this->_getFormatted($data, $column, $currentColumn);
        $text = $this->_dataTransform($text, $column, $rowIndex, $currentColumn, $key);
        $this->_outputHTML($text, true);
        $this->_closeTag($tag);
        $name = $appendKey ? "data[$key][$column]" : "data[$column]";
        if($this->_testForOption("selects", $column, $currentColumn)){
          $options = $this->_getOptionsArray($column, $currentColumn, $data);
          $this->_openTag("select", array("name" => $name));
          $associative = $this->_isHash($options);
          foreach($options as $name => $value){
            $selected = ($value == $data) ? "selected" : null;
            $this->_openTag("option", array("value" => $value, "selected" => $selected));
            $text = ($associative) ? $name : $value;
            $text = $this->_getFormatted($text, $column, $currentColumn);
            $this->_outputHTML($text);
            $this->_closeTag("option");
          }
          $this->_closeTag("select");
        } elseif($this->_testForOption("textareas", $column, $currentColumn)){
          $args = $this->textareas[$currentColumn] ? $this->textareas[$currentColumn] : $this->textareas[$column];
          $rows = ($args["rows"]) ? $args["rows"] : 3;
          $cols = ($args["cols"]) ? $args["cols"] : 20;
          $this->_openTag("textarea", array("name" => $name, "rows" => $rows, "cols" => $cols));
          $this->_outputHTML($data);
          $this->_closeTag("textarea");
        } else {
          $this->_openTag("input", array("type" => "text", "name" => $name, "value" => $data));
        }
      } else {

        $useFormat = $this->_testForOption("formatting", $column, $currentColumn);
        $text = ($useFormat) ? $this->_getFormatted($data, $column, $currentColumn) : $data;
        $text = $this->_dataTransform($text, $column, $rowIndex, $currentColumn, $key);
        $this->_openTag("span");
        $this->_outputHTML($text);
        $this->_closeTag("span");
      }
      $this->_closeTag("td");
      $currentColumn++;
    }
    if($this->allowDelete && $this->form){
      $attrib["class"] = $this->_checkColumnClass("DELETE");
      $this->_openTag("td", $attrib);
      if($this->loading) $this->_outputHTML($this->loading, "loading");
      if(!$key) $key = "NULL_STRING";
      $this->_openTag("input", array("type" => "checkbox", "name" => "delete[]", "value" => $key, "id" => "delete".$key));
      $this->_getLabel("deleteRowLabel", "delete".$key);
      $this->_closeTag("td");
    }
    $this->_closeTag("tr");
  }

  function _fetchEmptyDataRow()
  {
    if($this->emptyDataRow) return $this->emptyDataRow;
    if($this->data && !$this->database["fetchEmptyRow"]){
      $emptyDataRow["data"] = $this->data[0]["data"];
      foreach($emptyDataRow["data"] as $index => $value){
        $emptyDataRow["data"][$index] = "";
      }
    } else {
      $emptyDataRow["data"] = array();
      $describe = $this->query("DESCRIBE " . $this->database["table"] . ";");
      foreach($describe as $row){
        $default = $row["Default"];
        $field   = $row["Field"];
        if($default == "CURRENT_TIMESTAMP"){
          $value = date(MYSQL_DATE_FORMAT);
        } else {
          $value = $default;
        }
        $emptyDataRow["data"][$field] = $value;
      }
    }
    $this->emptyDataRow = $emptyDataRow;
    return $emptyDataRow;
  }

  function _outputHeaders($headers, $showTitle = false)
  {
    $this->_openTag("thead");
    if($this->title && $showTitle){
      $this->_openTag("tr");
      $this->_openTag("th", array("colspan" => count($headers), "class" => "title"));
      $this->_outputHTML($this->title);
      $this->_closeTag("th");
      $this->_closeTag("tr");
    }
    if($headers){
      $this->_openTag("tr");
      foreach($headers as $header){
        $this->_openTag("th", $header["attrib"]);
        $this->_outputHTML($header["html"]);
        $this->_closeTag("th");
      }
      $this->_closeTag("tr");
    }
    $this->_closeTag("thead");
  }

  function _setOptions($options)
  {
    if(!$options) return;
    foreach($options as $name => $value){
      $this->$name = $value;
    }
  }

  function _openTag($tag, $args = null)
  {
    $nl   = "\n";
    $tabs = str_repeat("\t", $this->indent + $this->_curIndent);
    $selfClosing = (in_array($tag, array("input", "img", "br"))) ? true : false;
    $close = ($selfClosing) ? " /" : null;
    if($args){
      foreach($args as $name => $value){
        $value = trim($value);
        if($value || is_numeric($value)){
          if($value == "NULL_STRING") $value = ""; // Fairly ghetto hack to force value="" into checkboxes.
          $value = htmlspecialchars(trim($value));
          $attributes .= " $name=\"$value\"";
        }
      }
    }
    echo "$nl$tabs<$tag$attributes$close>";
    if(!$selfClosing) $this->_curIndent++;
    $this->_hasTags = ($selfClosing) ? true : false;
    return $selfClosing;
  }

  function _outputHTML($html, $lineBreaks = false)
  {
    if(!isset($html)) return;
    if(is_array($html)){
      if($this->_isHash($html)){
        $closed = $this->_openTag($html["tag"], $html["attrib"]);
        $this->_outputHTML($html["html"]);
        if(!$closed) $this->_closeTag($html["tag"]);
        return;
      } else {
        foreach($html as $element){
          $this->_outputHTML($element);
        }
        return;
      }
    }
    $html = htmlspecialchars($html);
    if($lineBreaks) $html = nl2br($html);
    echo $html;
  }

  function _closeTag($tag)
  {
    $this->_curIndent--;
    $nl   = "\n";
    $tabs = str_repeat("\t", $this->indent + $this->_curIndent);
    if(!$this->_hasTags){
      echo "</$tag>";
      $this->_hasTags = true;
    }
    else echo "$nl$tabs</$tag>";
  }

  function _autoFormatHeader($header)
  {
    if(is_numeric($header)) return null;
    elseif(in_array($header, array("FIRST", "LAST", "BEFORE", "AFTER"))) return null;
    if($this->readableHeaders){
      $header = str_replace("_", " ", $header);
      $header = preg_replace("/([A-Z])/", " \\1", $header);
      $header = ucwords(strtolower($header));
    }
    return $header;
  }

  function _getLabel($label, $for, $class = null)
  {
    $label = $this->$label;
    if(!$label) return;
    $this->_openTag("label", array("for" => $for, "class" => $class));
    $this->_outputHTML($label);
    $this->_closeTag("label");
  }

  function _checkColumnClass($column, $num = null)
  {
    if($this->columns[$column]) return $this->columns[$column];
    elseif($this->column[$num]) return $this->columns[$num];
    elseif($column == "EDIT" || $column == "DELETE") return strtolower($column);
    elseif(isset($this->primaryKeyColumnsByName[$column])){
      $class = "primary_key";
      if($this->primaryKeyColumnsByName[$column]["auto"]){
        $class .= " auto_increment";
      }
      return $class;
    }
  }

  function _getOptionsArray($field, $column, $data)
  {
    $arg = $this->selects[$column] ? $this->selects[$column] : $this->selects[$field];
    if(is_array($arg)|| !$arg) return $arg;
    list($type, $params) = $this->_getParams($arg, true);
    if($type == "increment"){
      $options = array();
      if($params["convert_time"]) $data = strtotime($data);
      $abs = ($params["absolute"] || $params["abs"]) ? true : false;
      $min = ($params["min"]) ? $params["min"] : -INF;
      $max = ($params["max"]) ? $params["max"] : INF;
      $step  = ($params["step"]) ? $params["step"] : 1;
      $start = ($abs) ? $min : $data - ceil($params["range"] / 2) * $step;
      $stop  = ($abs) ? $max : $data + ceil($params["range"] / 2) * $step;
      if(!is_numeric($start) || !is_numeric($stop) || !$step) return array();
      for($i=$start; $i<=$stop; $i+=$step){
        $num = $i;
        if(!$abs && ($num < $min || $num > $max)) continue;
        if($params["convert_time"]) $num = date(MYSQL_DATE_FORMAT, $num);
        array_push($options, $num);
      }
      return $options;
    }
  }

  function _getSortType($field)
  {
    $format = $this->formatting[$field];
    if(!$format) return null;
    list($type) = $this->_getParams($format);
    if($type == "date") return "date";
    if($type == "eDate") return "eDate";
    if($type == "memory") return "memory";
    elseif($type == "numeric" || $type == "currency") return "numeric";
  }

  function _getFormatted($data, $field, $column)
  {
    if(!$this->_testForOption("formatting", $field, $column)) return $data;
    $format = $this->formatting[$column] ? $this->formatting[$column] : $this->formatting[$field];
    list($type, $params) = $this->_getParams($format);
    if($type == "date" || $type == "eDate"){
      if(!is_numeric($data)) $data = strtotime($data);
      if(is_null($data)) return null;
      if(preg_match("/^[A-Z0-9_]+$/", $params) && strlen($params) > 1) $params = constant($params);
      return ($params) ? date($params, $data) : date("F j, Y", $data);
    } elseif($type == "currency"){
      list($type, $params) = $this->_getParams($format, true);
      $currency = $data;
      $precision  = (isset($params["precision"])) ? $params["precision"] : 2;
      $padding  = $params["pad"] ? $precision : false;
      $commas  = $params["nocommas"] ? false: true;
      $currency = $commas ? number_format(round($currency, $precision), $precision) : $currency;
      $currency = $padding ? $currency : str_replace(".00", "", $currency);
      $currency = $params["prefix"] . $currency;
      $currency = $currency . $params["suffix"];
      return $currency;
    } elseif($type == "numeric"){
      return number_format(round($data, $params), $params);
    } elseif($type == "memory"){
      list($type, $params) = $this->_getParams($format, true);
      $auto = $params["auto"];
      $decimals = $params["decimals"] ? $params["decimals"] : 0;
      $unit = $params["unit"] ? strtolower($params["unit"]) : "b";
      $units = array("b", "kb", "mb", "gb", "tb", "pb", "eb");
      $memory = $data;
      if($auto){
        $u = $unit;
        $u = str_replace("bytes", "b", $u);
        $u = str_replace("kilobytes", "kb", $u);
        $u = str_replace("megabytes", "mb", $u);
        $u = str_replace("gigabytes", "gb", $u);
        $u = str_replace("terabytes", "tb", $u);
        $u = str_replace("petabytes", "pb", $u);
        $u = str_replace("exabytes",  "eb", $u);
        $index = array_search($u, $units);
        while($memory > 999 && $index !== FALSE){
          if(!$units[++$index]) break;
          else {
            $unit = $units[$index];
            $memory = $memory / 1000;
          }
        }
      }
      if(!$params["small"] && $unit == "mb" || $unit == "kb") $decimals = 0;
      $unit = ($unit == "b") ? "B" : $unit;
      $unit = $params["capital"] ? strtoupper($unit) : $unit;
      $unit = $params["camel"] ? ucwords($unit) : $unit;
      $space = $params["space"] ? " " : null;
      $memory  = number_format(round($memory, $decimals), $decimals);
      if($decimals > 0) $memory  = str_replace(".0", "", str_replace(".00", "", $memory));
      $memory .= $space . $unit;
      return $memory;
    }
    return $data;
  }

  function _getInputFormat($value, $field)
  {
    list($type, $params) = $this->_getParams($this->inputFormat[$field]);
    if(!$type) return $value;
    $type = strtolower(str_replace(" ", "", $type));
    if($type == "date" || $type == "eDate" || $type == "timestamp" || $type == "eTimestamp"){
      /* Get English Dates */
      if($type == "eDate" || $type == "eTimestamp") $value = preg_replace("/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2,4})$/", "\\2/\\1/\\3", $value);
      /* Get Japanese/Chinese dates */
      $value = mb_convert_kana($value, "as", "UTF-8");
      $value = preg_replace("/^(\d+)年(\d+)月(\d+)日$/", "\\2/\\3/\\1", $value);
      /* Note: 32-bit platforms only support dates between 1901 and 2038 */
      $stamp = is_numeric($value) ? $value : strtotime($value);
      if(!$stamp){
        $this->_addError($field, "Timestamp is invalid");
        return false;
      }
      if($type == "timestamp" || $type == "eTimestamp"){
        return $stamp;
      } else {
        if(preg_match("/^[A-Z0-9_]+$/", $params)) $format = constant($params);
        else $format = $params ? $params : MYSQL_DATE_FORMAT;
        $date = date($format, $stamp);
        if(!$date) $this->_addError($field, "Date is invalid");
        return $date;
      }
    } elseif($type == "numeric"){
      $number = str_replace(",", "", $value);
      preg_match("/[-+]?[0-9]*\.?[0-9]+/", $number, $match);
      if(!$match[0]) $this->_addError($field, "Numeric format is invalid");
      return $match[0];
    }
  }

  function _getParams($option, $subparams = false)
  {
    $split  = explode("[", $option);
    $type   = $split[0];
    $params = rtrim($split[1], "]");
    if($subparams){
      $split  = explode(",", $params);
      $params = array();
      foreach($split as $sub){
        list($name, $value) = explode("=", $sub);
        if(!isset($value)) $value = true;
        $params[$name] = $value;
      }
    }
    return array($type, $params);
  }

  function _testForOption($option, $field, $column = null)
  {
    if($field == "EDIT" || $field == "DELETE") return false;
    $option = $this->$option;
    if($option == "all") return true;
    if($option == "allExceptAutoIncrement"){
      $column = $this->primaryKeyColumnsByName[$field];
      return ($column && $column["auto"]) ? false : true;
    }
    elseif(is_array($option)){
      $associative = $this->_isHash($option);
      if($option[$field] || ($associative && $option[$column])) return true;
      return (in_array($field, $option) || in_array($column, $option)) ? true : false;
    }
    return false;
  }


  function _addClass($add, $class = null, $test = null)
  {
    $class .= ($add && $class) ? " " : null;
    if(isset($test)) $class .= ($test) ? $add : null;
    else $class .= $add;
    return $class;
  }

  function _dataTransform($data, $field, $row, $column, $key, $transform = null, $associated = null)
  {
    if(!$this->_testForOption("transform", $field, $column)) return $data;
    if(!$transform){
      $transform = $this->transform[$field] ? $this->transform[$field] : $this->transform[$column];
    }
    if(is_array($transform)){
      if($transform["associate"]) $associated = $transform["associate"];
      if($transform["attrib"] && is_array($transform["attrib"])){
        foreach($transform["attrib"] as $attrib => $value){
          $transform["attrib"][$attrib] = $this->_dataTransform($data, $field, $row, $column, $key, $value, $associated);
        }
      }
      if($transform["html"]) $transform["html"] = $this->_dataTransform($data, $field, $row, $column, $key, $transform["html"], $associated);
    } else {
      $transform = str_replace("{DATA}", $data, $transform);
      $transform = str_replace("{KEY}", $key, $transform);
      $transform = str_replace("{FIELD}", $field, $transform);
      $transform = str_replace("{COLUMN}", $column, $transform);
      $transform = str_replace("{RANDOM}", rand(0, 9999), $transform);
      if($associated){
        $text = $this->_getFormatted($this->data[$row]["data"][$associated], $associated, $column);
        $transform = str_replace("{ASSOCIATED}", $text, $transform);
      }
    }
    return $transform;
  }

  function _checkColumnShift()
  {
    $shift = $this->shiftColumns;
    if(!$shift) return;
    foreach($shift as $col => $pos){
      $this->shiftColumn($col, $pos);
    }
  }

  function _injectURLParam($inputName, $inputValue)
  {
    $params = array();
    foreach($_GET as $name => $value){
      if($name == $inputName){
        $match = true;
        $value = $inputValue;
      }
      $param = "$name=$value";
      array_push($params, $param);
    }
    if(!$match){
      $param = "$inputName=$inputValue";
      array_push($params, $param);
    }
    $uri = $_SERVER["PHP_SELF"] . "?" . implode("&", $params);
    return $uri;
  }

  function _navLink($type, $html)
  {
    $current = $this->pagination["currentPage"];
    $total = $this->pagination["totalPages"];
    $tag = (($type == "prev" && $current <= 1) || ($type == "next" && $current >= $total)) ? "div" : "a";
    $attribs = array("class" => $type);
    if($tag == "a"){
      $page = ($type == "prev") ? $current - 1 : $current + 1;
      $attribs["href"] = $this->_injectURLParam("page", $page);
      $attribs["id"] = $type . "Page";
    }
    $this->_openTag($tag, $attribs);
    $this->_outputHTML($html);
    $this->_closeTag($tag);
  }

  function _modifyURIParams($added, $append = false)
  {
    $params = $this->_getURIParams();
    foreach($added as $name => $value){
      $params[$name] = $value;
    }
    if($append) $this->URIParams = $params;
    return $this->_getURI($params);
  }

  function _getURIParams()
  {
    if($this->URIParams) return $this->URIParams;
    $this->URIParams = array();
    $split = explode("&", $_SERVER["QUERY_STRING"]);
    foreach($split as $param){
      list($name, $value) = explode("=", $param);
      $this->URIParams[$name] = $value;
    }
    return $this->URIParams;
  }

  function _getURI($params = null)
  {
    $params = $params ? $params : $this->_getURIParams();
    $faux_params = array(); // Finally I see why Ruby is so much better (other than just syntax).
    foreach($params as $name => $value){
      if(!isset($value)) continue;
      array_push($faux_params, "$name=$value");
    }
    $URI = $_SERVER["PHP_SELF"] . "?" . implode("&", $faux_params);
    return $URI;
  }

  function _isHash($array)
  {
    return (array_keys($array) != range(0, count($array) - 1)) ? true : false;
  }

  /* Functions for handling submitted data */

  function _checkSubmit()
  {
    if(!$this->_httpArray) $http = ($this->form["method"] == "get") ? $_GET : $_POST;
    if($http["table"] != $this->table["id"]) return;
    $this->_httpArray = $this->_handleMagicQuotes($http);
    if($this->_httpArray["edit"]) $this->_processSubmit("edit");
    if($this->_httpArray["delete"]) $this->_processSubmit("delete");
    if($this->_httpArray["insert"]) $this->_processSubmit("insert");
    $this->_jsonOutput();
  }

  function _processSubmit($action)
  {
    $this->_json["affected"] = 0;
    if($action == "insert") $this->_insertRow();
    else {
      $rows = $this->_httpArray[$action];
      if(!$rows) return;
      // Note: $cKey denotes that there may be composite keys!
      foreach($rows as $cKey){
        if($action == "delete") $this->_deleteRow($cKey);
        elseif($action == "edit")  $this->_updateTable($cKey);
        $this->_json["affected"] = $this->_affectedRows;
      }
    }
    $this->_json["action"] = $action;
    $this->_getTotals();
  }

  function _insertRow()
  {
    $table = $this->database["table"];
    if(!$table) return;
    $fields = array_keys($this->_httpArray["data"]);
    $values = $this->_constructQueryValues($this->_httpArray["data"]);
    if($this->errors) return;
    $query = "INSERT INTO $table (".implode(",", $fields).") VALUES (".implode(",", $values).")";
    $data = $this->query($query);
    if($data !== false){
      $this->_json["affected"] = $this->_affectedRows; // Timing requires this to be here.
      $this->_json["key"] = $this->_getPrimaryKeyValuesAfterInsertion($this->_httpArray["data"]);
      $this->_callback("onInsert", $this->_json["key"], $callbackPrev, $this->_httpArray["data"]);
    }
  }

  function _deleteRow($cKey)
  {
    if($this->connection){
      $table    = $this->database["table"];
      if(!$table) return;
      if($this->callback["getPrevious"]){
        $query = $this->_limitQueryByPrimaryKey("SELECT * FROM $table", $cKey);
        $callbackPrev = $this->query($query);
        $callbackPrev = $callbackPrev[0];
      }
      $query = $this->_limitQueryByPrimaryKey("DELETE FROM $table", $cKey);
      $result = $this->query($query);
      if($result){
        $this->_json["key"] = $cKey;
        $this->_callback("onDelete", $cKey, $callbackPrev);
      }
    } elseif($this->data){
      $row = $this->_selectArrayRow($cKey);
      if($this->data[$row]){
        $callbackPrev = $this->data[$row];
        unset($this->data[$row]);
        $this->_callback("onDelete", $cKey, $callbackPrev);
        return 1;
      }
    }
  }

  function _updateTable($cKey)
  {
    $table = $this->database["table"];
    if(!$table) return;
    if($this->callback["getPrevious"]){
      $query = $this->_limitQueryByPrimaryKey("SELECT * FROM $table", $cKey);
      $callbackPrev = $this->query($query);
      $callbackPrev = $callbackPrev[0];
     }
    $values = $this->_constructQueryValues($this->_httpArray["data"][$cKey], true);
    if($this->errors) return;
    $query = $this->_limitQueryByPrimaryKey("UPDATE $table SET ".implode(",", $values), $cKey);
    $result = $this->query($query);
    if($result){
      $updatedData = $this->_httpArray["data"][$cKey];
      $this->_getUpdatedOptions($this->_httpArray["data"][$cKey], $this->_httpArray["column"]);
      $this->_json["key"] = $this->_getPrimaryKeyValuesAfterUpdate($updatedData, $cKey);
      $this->_callback("onUpdate", $cKey, $callbackPrev, $updatedData);
    }
  }

  function _constructQueryValues($dataSet, $update = null)
  {
    $values = array();
    $count = 1;
    foreach($dataSet as $field => $userInput){

      $data = $this->_getInputFormat($userInput, $field);
      $column = $this->_httpArray["column"] ? $this->_httpArray["column"] : $count;
      $this->_validateData($data, $field, $column);
      $sql = mysql_real_escape_string($data, $this->connection);
      if(!isset($sql)) $sql = "NULL";
      elseif(is_numeric($sql)) $sql = floatval($sql);
      else $sql = "'$sql'";
      $sql = ($update) ? "$field=$sql" : $sql;
      array_push($values, $sql);
      $this->_json["field"] = $field;
      $this->_json["value"] = $userInput;
      $this->_json["formatted"] = nl2br($this->_getFormatted($data, $field, $column));
      $count++;
    }
    return $values;
  }

  function _getUpdatedOptions($data, $column)
  {
    $options = array();
    foreach($data as $field => $value){
      if($this->_testForOption("selects", $field, $column)){
        $arr = $this->_getOptionsArray($field, $column, $value);
        foreach($arr as $val){
          $option = array();
          $option["value"] = $val;
          $option["formatted"] = $this->_getFormatted($val, $field, $column);
          array_push($options, $option);
        }
      }
    }
    if(count($options) > 0) $this->_json["updatedOptions"] = $options;
  }

  function _callback($type, $key, $previous = null, $updated = null)
  {
    $function = $this->callback[$type];
    if(!function_exists($function)) return;
    $userExposedKey = $this->_getPrimaryKeyArrayOrValue($key);
    $updated = $this->_appendPrimaryKeyValues($updated, $key);
    call_user_func($function, $userExposedKey, $previous, $updated, $this);
  }

  function _getPrimaryKeyArrayOrValue($key)
  {
    $key = implode($this->primaryKeyDelimiter, $key);
    return count($key == 1) ? $key[0] : $key;
  }

  function _appendPrimaryKeyValues($updated, $values)
  {
    $values = implode($this->primaryKeyDelimiter, $values);
    foreach($this->_getPrimaryKeyColumns() as $i => $key){
      $updated[$key["name"]] = $values[$i];
    }
    return $updated;
  }

  function _getTotals()
  {
    $totals = $this->totals;
    if(!$totals || !$this->connection) return;
    $this->_json["totals"] = array();
    $this->fetchData();
    foreach($totals as $field){
      $total = 0;
      if($this->data){
        foreach($this->data as $row){
          if(is_numeric($field)){
            $data = array_values($row["data"]);
            $total += $data[$field - 1];
          } else {
            $total += $row["data"][$field];
          }
        }
      }
      $total = $this->_getFormatted($total, $field, $this->_httpArray["column"]);
      $type = is_numeric($field) ? "column" : "field";
      array_push($this->_json["totals"], array($type => $field, "total" => $total));
    }
  }

  function _validateData($data, $field, $column)
  {
    if(!$this->_testForOption("validate", $field, $column)) return true;
    $validation = $this->validate[$field] ? $this->validate[$field] : $this->validate[$column];
    if(preg_match($validation, $data)) return true;
    else {
      $this->_addError($field, "Validation failed.");
    }
  }

  function _addError($field, $message)
  {
    if(!$this->errors) $this->errors = array();
    array_push($this->errors, array("field" => $field, "message" => $message));
  }

  function _selectArrayRow($key)
  {
    foreach($this->data as $index => $row){
      if($row["key"] == $key) return $index;
    }
  }

  function _handleMagicQuotes($array)
  {
    if(!get_magic_quotes_gpc()) return $array;
    foreach($array as $key => $value){
      if(is_array($value)) $value = $this->_handleMagicQuotes($value);
      else $value = stripslashes($value);
      $array[$key] = $value;
    }
    return $array;
  }

  function _jsonOutput()
  {
    if($_SERVER["HTTP_X_REQUESTED_WITH"] != "XMLHttpRequest") return;
    $json = $this->_json;
    if(!$json) $json = array("success" => false, "info" => "No actions performed.");
    $json = $this->_jsonEncode($json);
    die($json);
  }


  /* For PHP installs less than 5.2.0 */

  function _jsonEncode($array)
  {
    if(function_exists(json_encode)) return json_encode($array);
    $assoc = false;
    for($i=0;$i<sizeof($keys=array_keys($array));$i++){ if(strval($i)!=$keys[$i]) $assoc=true; }
    $json = ($assoc) ? "{" : "[";
    foreach($array as $key => $value){
      $key = addslashes($key);
      if($assoc) $json .= "'$key':";
      if(is_array($value))    $json .= $this->_jsonEncode($value);
      elseif(is_string($value)) $json .= "'".addslashes($value)."'";
      elseif(is_bool($value))    $json .= ($value) ? "true" : "false";
      elseif(is_null($value))    $json .= "null";
      else $json .= $value;
      $json .= ",";
    }
    $json = rtrim($json, ",");
    $json .= ($assoc) ? "}" : "]";
    return $json;
  }

}

?>
