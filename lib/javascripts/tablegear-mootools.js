/*
 *
 *  TableGear (Dynamic table data in HTML)
 *
 *  Version: 1.6.2
 *  Documentation: AndrewPlummer.com (http://www.andrewplummer.com/code/tablegear/)
 *  Inspired by: TableKit for Prototype (http://www.millstream.com.au/view/code/tablekit/)
 *  Written for: Mootools 1.2
 *  License: MIT-style License
 *
 *  Copyright (c) 2010 Andrew Plummer
 *
 *
 */


var TableGear = new Class({

  Implements: Options,

  options: {
    hideInputs: true,
    rowStriping: true,
    autoSelect: true,
    shortcutKeys: true,
    addNewRows: true,
    ascCarat: "▲",
    descCarat: "▼",
    deletePrompt: "Delete this row?",
    noDataMessage: "- No Data -",
    addRowLabel: "Add a Row",
    limitAddedRows: 0,
    taskMaster: -1
  },

  initialize: function(id, options){

    this.tableID = id;
    this.setOptions(options);
    var el = $(id);

    if(!el){
      this.throwError("Element '"+id+"' does not exist.");
    } else if(el.get("tag") == "form"){
      this.form = el;
      this.table = this.requireElement("table", this.form, "A <table> element is required inside <form> '"+id+"'.");
    } else if(el.get("tag") == "table"){
      this.form = el.getParent("form");
      this.table = el;
    } else if(el.get("tag") != "table"){
      this.throwError("Element '"+id+"' must be a <table> or <form>.");
    }

    if(this.form){
      this.url = (this.options.url) ? this.options.url : this.form.get("action");
      var submitButton = this.form.getElement("input[type=submit]");
      if(submitButton) submitButton.setStyle("display", "none");
    }

    var thead = this.requireElement("thead", this.table, "Element <thead> is required inside <table>.");
    this.headerRow = this.requireElement("tr", thead, "A <tr> element is required inside <thead>.", "title");
    this.headers = this.headerRow.getChildren("th");

    this.headers.each(function(header, colIndex){

      if(!header.hasClass("sortable")) return;

      if(header.hasClass("date")) header.store("colType", "date");
      else if(header.hasClass("eDate")) header.store("colType", "eDate");
      else if(header.hasClass("memory")) header.store("colType", "memory");
      else if(header.hasClass("alphabetic")) header.store("colType", "string");
      else header.store("colType", "numeric");

      header.store("colIndex", colIndex);
      header.carat = header.getChildren(".carat");

      header.addEvent("click", function(event){

        var colIndex = header.retrieve("colIndex");
        if(this.currentSortIndex == colIndex){
          this.currentDesc = (this.currentDesc) ? false : true;
        } else {
          this.currentSortIndex = colIndex;
          this.currentDesc = false;
        }

        this.currentSortIndex = colIndex;
        this.currentType  = header.retrieve("colType");


        this.sortRows();

      }.bindWithEvent(this));

    }, this);


    var tfoot = this.table.getElement("tfoot");
    if(tfoot){
      var totals = tfoot.getElements("td.total,th.total");
      if(totals.length > 0){
        this.totals = new Array();
        this.totalsRow = totals[0].getParent("tr");
        this.footers = this.totalsRow.getElements("td,th");
        totals.each(function(cell){
          var field = cell.get("class").match(/\w+/);
          if(!field) return;
          this.totals[field] = cell;
        }, this);
      }
    }

    this.editableCells = new Array();

    this.tbody = this.requireElement("tbody", this.table, "Element <tbody> is required inside <table>.");

    this.rows = this.tbody.getChildren("tr");
    if(!this.rows) this.throwError("Element <tbody> requires at least one row.");

    this.rows.each(function(row, rowIndex){
      if(row.hasClass("noDataRow")) return;
      this.initializeRow(row, rowIndex);
    }, this);

    this.addedRows = [];
    if(this.options.addNewRows && this.form){
      var newRowForm = $('addNewRow_' + id);
      newRowForm.setStyle("display", "none");
      this.emptyDataRow = $('newDataRow_' + id);
      this.addRow = new Element("p", {"class": "addRow"});
      this.addRow.adopt(new Element("a", {html: this.options.addRowLabel, events: {click: function(event){
        this.addRow.setStyle("display", "none");
        this.walkCells(this.editableCells.getLast(), 1);
      }.bind(this)}}));
      if(!this.table.getElement(".noDataRow")) this.addRow.setStyle("display", "none");
      this.addRow.inject(this.table, "after");
      if(!this.options.editableCellsPerRow){
        this.options.editableCellsPerRow = this.emptyDataRow.getElements("td.editable").length;
      }
    }

    if(this.options.taskMaster > 1000) this.taskMaster.periodical(this.options.taskMaster, this);

    if(this.options.paginated){
      if($('prevPage')){
        this.prevPage = $('prevPage');
        document.addEvent('pageUpKey', function(event){
          window.location = this.prevPage.href;
        }.bind(this));
      }
      if($('nextPage')){
        this.nextPage = $('nextPage');
        document.addEvent('pageDownKey', function(event){
          window.location = this.nextPage.href;
        }.bind(this));
      }
    }



  },

  queue: new Array(),

  requireElement: function(css, parent, error, exclude){

    //var elements = parent.getChildren(css);

    /* This is a workaround for lack of "," support in getChildren... change to getChildren when fixed */
    var split = css.split(",");
    var elements = [];
    for(i=0;i<split.length;i++){
      elements.combine(parent.getChildren(split[i]));
    }
    /* End workaround */

    if(!elements) this.throwError(error);
    var match;
    elements.each(function(element){
      if(element.hasClass(exclude)) return;
      else match = element;
    });
    return match;
  },

  throwError: function(error){

    var exception = "TableGear Error: " + error;
    alert(exception);
    throw new Error(exception);
  },

  addJob: function(row, cell, input, span){

    if(input.get("value") == input.retrieve("currentValue")) return;

    if(input.retrieve("column") == this.currentSortIndex){
      this.headers[this.currentSortIndex].carat.empty();
      this.currentSortIndex = null;
    }

    var job = {
      row: row,
      input: input,
      span: span,
      cell: cell
    }
    if(this.addedRows.contains(row))  this.addedRows.erase(row);
    cell.addClass("loading");
    this.addToQueue(job);
  },

  getInputField: function(input){
    var name  = input.get("name");
    var match = name.match(/^data(?:\[.+?\])?\[(.+?)\]$/);
    return match[1];
  },

  addToQueue: function(job){

    this.queue.push(job);
    if(!this.request || !this.request.running) this.nextRequest();
  },

  nextRequest: function(){

    if(this.queue.length <= 0) return;
    this.currentJob = this.queue.shift();

    var input = this.currentJob.input;
    var params = {
      "table": this.tableID,
      "data": {},
      "column": input.retrieve("column")
    }
    var field = this.getInputField(input);
    var keyInput = this.currentJob.row.retrieve("keyInput");
    if(keyInput.value){
      params["edit[]"] = keyInput.value;
      params.data[keyInput.value] = {};
      params.data[keyInput.value][field] = input.value;
    } else {
      params["insert"] = true;
      params.data[field] = input.value;
    }

    this.request = new Request({
      url: this.url,
      onComplete: this.onComplete.bind(this),
      data: params
    });
    this.request.send();
  },

  onComplete: function(response){

    var json = JSON.decode(response);

    this.currentJob.cell.removeClass("loading");

    if(!json || json.affected <= 0){

      this.currentJob.input.set("value", this.currentJob.input.retrieve("currentValue"));
      return;

    } else {

      this.currentJob.span.set("html", json.formatted);
      if(!this.currentJob.input.match('select')){
        this.setValue(this.currentJob.input, json.value);
      }
      this.currentJob.input.store("currentValue", json.value);
      this.currentJob.row.store("rowUnchanged", true);

      if(json.action == "insert" || json.action == "edit"){
        var formElements = this.currentJob.row.getElements("select,input,textarea");
        formElements.each(function(el){
          var name = el.get("name");
          if(name == "edit[]" || name == "delete[]") el.value = json.key;
          else {
            var field = this.getInputField(el);
            el.set("name", "data["+json.key+"]["+field+"]");
            if(json.auto && json.auto == field){
              el.set("value", json.key);
            }
          }
        }.bind(this));
        if(json.auto){
          this.currentJob.row.getElement('.auto_increment').set("text", json.key);
        }
        if(json.updatedOptions && this.currentJob.input.match('select')){
          var select = this.currentJob.input;
          select.empty();
          json.updatedOptions.each(function(data){
            var option = new Element("option", {"text": data.formatted, "value": data.value})
            if(data.value == json.value) option.set("selected", "selected");
            select.adopt(option);
          });
        }
      }
      if(json.totals) this.updateTotals(json.totals);
    }
    this.currentJob = null;
    this.nextRequest();
  },

  sortRows: function(){

    if($defined(this.currentSortIndex)){
      this.rows = this.rows.sort(this.sortData.bind(this));
      if(this.currentDesc) this.rows = this.rows.reverse();
    }

    this.editableCells.empty();

    this.rows.each(function(row, index){

      this.addStripe(row, index+1);
      var cells = row.getChildren("td.editable");
      cells.each(function(cell){
        this.editableCells.push(cell);
      }, this);
    }, this);

    this.tbody.adopt(this.rows);
    var carat = (this.currentDesc) ? this.options.descCarat : this.options.ascCarat;

    this.headers.each(function(header){

      if(header.carat){
        if(header == this.headers[this.currentSortIndex]) header.carat.set("html", carat);
        else header.carat.set("html", "");
      }
    }, this);

  },

  update: function(){

    this.rows = this.tbody.getChildren("tr");
    this.sortRows();
  },

  addStripe: function(row, index){

    if(!this.options.rowStriping) return;
    var css = ((index + 1) % 2) ? "even" : "odd";
    row.erase("class");
    row.addClass(css);
  },

  sortData: function(row1, row2){

    var data1 = this.getData(row1, this.currentSortIndex);
    var data2 = this.getData(row2, this.currentSortIndex);

    if(this.currentType == "numeric"){

      data1 = data1.replace(/[,:]/g, "");
      data2 = data2.replace(/[,:]/g, "");
      data1 = data1.match(/[-+]?\d*\.?\d+/);
      data2 = data2.match(/[-+]?\d*\.?\d+/);
      if(data1) data1 = data1[0].toFloat();
      else data1 = -Infinity;
      if(data2) data2 = data2[0].toFloat();
      else data2 = -Infinity;

    } else if(this.currentType == "date" || this.currentType == "eDate"){

      var eur = (this.currentType == "eDate") ? true : false;
      data1 = this.getDate(data1, eur);
      data2 = this.getDate(data2, eur);

    } else if(this.currentType == "memory"){

      data1 = this.getMemory(data1);
      data2 = this.getMemory(data2);

    } else if (this.currentType == "string"){
      data1 = data1.toLowerCase();
      data2 = data2.toLowerCase();
    }

    if(data1 == data2) return 0;
    return (data1 < data2) ? -1 : 1;
  },

  getData: function(row, index){

    if(index == null) return;
    var cell = row.cells[index];
    var span = cell.getElement("span");
    var text = (span) ? span.get("text") : cell.get("text");
    return text.trim();
  },

  getDate: function(s, eur){

    if(eur){
      s = s.replace(/(\d+)\/(\d+)\/(\d+)/g, "$2/$1/$3");
    }
    var date = new Date(s);
    if(!date.getYear()){

      /* Get dates formatted 10*23*2003 (or some other strange delimiter) */
      var match = s.match(/^(\d{1,2})\D(\d{1,2})\D(\d{2,4})$/);
      if(match) date = new Date(match[1] + "/" + match[2] + "/" + match[3]);
      else {
        /* Get foreign dates: 2003年10月23日 */
        match = s.match(/^(\d{4})\D(\d{1,2})\D(\d{1,2})\D$/g);
        if(match) date = new Date(match[1] + "/" + match[2] + "/" + match[3]);
      }
    }

    return date.getYear() ? date : s;
  },

  getMemory: function(s){

    var match = s.match(/^([0-9.]+)\s*([a-z]+)$/i);
    if(!match) return null;
    var data = match[1].replace(/,/g, "").toFloat();
    var unit = match[2].toLowerCase();
    if(!unit) unit = "b";
    var units = ["b", "kb", "mb", "gb", "tb"];
    data = data * Math.pow(1000, units.indexOf(unit));
    return data;
  },

  setValue: function(input, value){
    var tag = input.get("tag");
    if(tag == "select") input.selectedIndex = input.getElement("option[value="+value+"]").index;
    else input.set("value", value);
  },

  walkCells: function(current, step){

    var index = this.editableCells.indexOf(current);
    if(this.options.addNewRows && (index + step >= this.editableCells.length)) this.addNewRow();
    var target = this.editableCells[index + step];
    if(step < 0 && target) this.removeUnusedRows(target);
    if(target) target.fireEvent("click");
  },

  updateTotals: function(totals){

    if(this.rows.length == 0) this.totalsRow.setStyle("display", "none");
    else this.totalsRow.removeProperty("style");

    totals.each(function(obj){
      if(obj.column){
        var editOffset = this.hasKeyInput ? 1 : 0;
        var cell = this.totalsRow.cells[(obj.column - 1) + editOffset];
      } else if(obj.field){
        var cell = this.totals[obj.field];
      }
      if(cell) cell.set("text", obj.total);
    }, this);
  },

  initializeRow: function(row, rowIndex){

    this.addStripe(row, rowIndex+1);
    var cells = row.getChildren("td");

    if(!this.options.editableCellsPerRow) this.options.editableCellsPerRow = row.getElements("td.editable").length;

    var keyInput = row.getElement("input[name^=edit]");
    if(keyInput){
      this.hasKeyInput = true;
      row.store('keyInput', keyInput);
      if(this.options.hideInputs){
        var parentCell = keyInput.getParent("td");
        if(parentCell){
          var editColumn = cells.indexOf(parentCell);
          if(this.headers) this.headers[editColumn].setStyle("display", "none");
          if(this.footers) this.footers[editColumn].setStyle("display", "none");
          parentCell.setStyle("display", "none");
        }
        else keyInput.setStyle("display", "none");
      }
    }
    cells.each(function(cell, colIndex){

      var cellID = rowIndex + ":" + colIndex;
      var column = this.headers[colIndex];
      var colType = column.retrieve("colType");
      if(column.hasClass("sortable")){
        if(colType == "numeric" && !column.hasClass("numeric")){
          var text = cell.get("text");
          if(text && !text.match(/[-+]?\d*\.?\d+/)) column.store("colType", "string");
        }
      }
      if(cell.hasClass("editable")){

        this.editableCells.push(cell);

        if (!this.form) this.throwError("Cells require a <form> element to be editable.");

        var span  = this.requireElement("span", cell, "A <span> element is required in editable cell " + cellID + ".");
        var input = this.requireElement("input,select,textarea", cell, "An <input>, <select>, or <textarea> element is required in editable cell " + cellID + ".");
          //var field = this.getInputField(input);
          //if(!field) this.throwError("Input element's \"name\" attribute must be in the format [field][number].");
          span.setStyle("display", "inline");
          input.setStyle("display", "none");
          input.set("autoComplete", "off");
          var tag = input.get("tag");
          input.store("currentValue", input.get("value"));
          input.store("column", colIndex);

          /* IE Selects fire on every key press, so make them act like Firefox */
          var tridentSelect = (Browser.Engine.trident && tag == "select") ? true : false;
          input.store("tridentSelect", tridentSelect);

        if(this.options.hideInputs){
          cell.addEvent("click", function(event){

            span.setStyle("display", "none");
            input.setStyle("display", "inline");

            input.focus();
            if(input.select && this.options.autoSelect) input.select();

          }.bindWithEvent(this));
        }


        input.addEvent("blur", function(event){

          if(tridentSelect) this.addJob(row, cell, input, span);
          if(!this.options.hideInputs) return;

          span.setStyle("display", "inline");
          input.setStyle("display", "none");

        }.bindWithEvent(this));

        input.addEvent("change", function(event){
          if(tridentSelect) return;
          this.addJob(row, cell, input, span);
        }.bindWithEvent(this));

        input.addEvent("click", function(event){
          event.stopPropagation();
        }.bindWithEvent(this));

        // Protect changed rows from being destroyed.
        if(this.options.addNewRows){
          input.addEvents({
            "keydown": this.protectRow.bindWithEvent(this),
            "click": this.protectRow.bindWithEvent(this)
          });
        }

        input.addEvent("esckey", function(event){
          this.setValue(input, input.retrieve("currentValue"));
          if(input.select && this.options.autoSelect) input.select();
          else input.focus();
        }.bindWithEvent(this));


        if(this.options.shortcutKeys){

          input.addEvent("tabkey", function(event){
            event.preventDefault();
            var step = (event.shift) ? -1 : 1;
            this.walkCells(cell, step);
          }.bindWithEvent(this));

          input.addEvent("enterkey", function(event){
            if(!event.shift && tag == "textarea") return;
            event.preventDefault();
            var perRow = this.options.editableCellsPerRow;
            var step = (event.shift) ? -perRow : perRow;
            this.walkCells(cell, step);
          }.bindWithEvent(this));

          if(tag != "select"){
            input.addEvent("arrowkeys", function(event){
              event.preventDefault();
              var perRow = this.options.editableCellsPerRow;
              switch(event.key){
                case "up": var step = -perRow; break;
                case "down": var step = perRow; break;
              }
              this.walkCells(cell, step);
            }.bindWithEvent(this));
          }
        }

      } else if(cell.getElement("input[name^=delete]")){

        var input = this.requireElement("input[type=checkbox]", cell, "An <input> checkbox element is required for deletable rows in cell " + cellID + '.\n(Name property should be "delete[]".)');
        if(this.options.hideInputs) input.setStyle("display", "none");

        var label = cell.getElement("label");
        if(label) label.setStyle("display", "block");
        cell.addEvent("click", function(event){

          event.preventDefault();
          this.deleteDataRow(row, input);

        }.bindWithEvent(this));

      }

    }, this);
  },

  addNewRow: function(event){
    if(this.options.limitAddedRows && this.addedRows.length >= this.options.limitAddedRows) return;
    var noDataRow = this.tbody.getElement("tr.noDataRow");
    if(noDataRow){
      noDataRow.dispose();
      this.rows.erase(noDataRow);
    }
    var newDataRow = this.emptyDataRow.clone();
    newDataRow.erase("style");
    newDataRow.inject(this.tbody);
    if(window.HotText) new HotText(".hotText", {scope: newDataRow});
    this.rows.push(newDataRow);
    this.addedRows.push(newDataRow);
    this.initializeRow(newDataRow, this.rows.length-1);
  },

  removeUnusedRows: function(current){

    var currentRow = this.rows.indexOf(current.getParent("tr"));
    var rowsReversed = $A(this.addedRows).reverse();
    rowsReversed.each(function(row){
      if(this.rows.indexOf(row) <= currentRow) return;
      this.addedRows.erase(row);
      this.removeRow(row);
    }, this);
  },

  protectRow: function(event){
    var input = event.target;
    if(input.get("value") == input.retrieve("currentValue")) return;
    var row = input.getParent("tr");
    this.addedRows.erase(row);
  },

  deleteDataRow: function(row, input){

    var key = input.get("value");
    if(!key){
      this.removeRow(row);
      return;
    }

    if(this.options.deletePrompt && !confirm(this.options.deletePrompt)) return;

    this.request = new Request({
      url: this.url,
      onComplete: function(response){
        var json = JSON.decode(response);
        row.removeClass("loading");
        if(json.action == "delete" && json.key == key){
          this.removeRow(row);
          if(json.totals) this.updateTotals(json.totals);
        }
      }.bind(this),
      data: {"table": this.tableID, 'delete[]': key}
    });
    row.addClass("loading");
    this.request.send();
  },

  removeRow: function(row){
    this.rows.erase(row);
    row.destroy();
    if(this.rows.length < 1){
      var colspan = this.headers.length;
      this.headers.each(function(header){
        if(header.getStyle("display") == "none") colspan--;
      });
      var noData = new Element("td", {"text": this.options.noDataMessage,"colspan": colspan,"align": "center"});
      var noDataRow = new Element("tr", {"class": "noDataRow odd"});
      noDataRow.adopt(noData);
      this.tbody.adopt(noDataRow);
      this.addRow.setStyle("display", "block");
    }
    this.editableCells.empty();
    this.tbody.getElements('td.editable').each(function(cell){
      this.editableCells.push(cell);
    }, this);
  },

  taskMaster: function(){

    if(!this.currentJob) return;
    if(!this.lastJob || this.lastJob != this.currentJob){
      this.lastJob = this.currentJob;
      return;
    }
    this.request.cancel();
    this.request.send(); // Get back to work!
  }
});


if(!Element.Events.tabkey){
  Element.Events.tabkey = {
    base: "keydown",
    condition: function(event){
      return (event.key == "tab");
    }
  }
}
if(!Element.Events.enterkey){
  Element.Events.enterkey = {
    base: "keydown",
    condition: function(event){
      return (event.key == "enter");
    }
  }
}

if(!Element.Events.esckey){
  Element.Events.esckey = {
    base: "keydown",
    condition: function(event){
      return (event.key == "esc");
    }
  }
}

if(!Element.Events.arrowkeys){
  Element.Events.arrowkeys = {
    base: "keydown",
    condition: function(event){
      var arrows = ["up", "down"];
      return (arrows.contains(event.key));
    }
  }
}

if(!Element.Events.pageUpKey){
  Element.Events.pageUpKey = {
    base: "keydown",
    condition: function(event){
      return (event.code == 33);
    }
  }
}

if(!Element.Events.pageDownKey){
  Element.Events.pageDownKey = {
    base: "keydown",
    condition: function(event){
      return (event.code == 34);
    }
  }
}
