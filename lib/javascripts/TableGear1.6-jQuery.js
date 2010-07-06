/*
 *
 *  TableGear (Dynamic table data in HTML)
 *
 *  Version: 1.6 for jQuery
 *  Documentation: AndrewPlummer.com (http://www.andrewplummer.com/code/tablegear/)
 *  Inspired by: TableKit for Prototype (http://www.millstream.com.au/view/code/tablekit/)
 *  Written for: jQuery 1.4
 *  License: MIT-style License
 *
 *  Copyright (c) 2010 Andrew Plummer
 *
 *
 */


(function(){

  function setDefaults(name, value, hash){
    if(hash[name] === undefined) hash[name] = value;
  }

  jQuery.fn.tableGear = function(options){

    options = options || {};
    setDefaults('addNewRows',     true,               options);
    setDefaults('ascCarat',       '▲',              options);
    setDefaults('descCarat',      '▼',              options);
    setDefaults('deletePrompt',   'Delete this row?', options);
    setDefaults('noDataMessage',  '- No Data -',      options);
    setDefaults('addRowLabel',    'Add a Row',        options);
    setDefaults('limitAddedRows', 0,                  options);
    setDefaults('taskMaster',     -1,                 options);

    initialize(this);

    // Public

    var table;
    var tbody;
    var tfoot;
    var form;
    var url;
    var sortColumn;
    var sortAscending;
    var editableCells;
    var perRow;
    var rows;
    var addedRows;
    var emptyRow;
    var id;
    var queue;
    var currentJob;

    var keyCodes = {
      ENTER: 13,
      TAB:   9,
      ESC:   27,
      LEFT:  37,
      UP:    38,
      RIGHT: 39,
      DOWN:  40
    }

    // Private

    function initialize(el){

      editableCells = [];
      queue = [];
      rows = $();
      addedRows = $();
      id = el.selector.replace('#', '');


      if(el.is('form')){
        form = el;
        table = requireElement('table', form, 'A <table> element is required inside <form> "'+ el.selector +'".');
      } else if(el.is('table')){
        table = el;
        form = table.parents('form');
      } else {
        throwError("Element '"+id+"' must be a <table> or <form>.");
      }

      if(form){
        url = form.attr('action');
        $('input[type=submit]', form).hide();
      }

      requireElement('thead', table, '<thead> is required inside <table>');

      $('thead th', table).each(function(columnIndex){

        var header = $(this);

        if(header.hasClass('sortable')) return;

        if(header.hasClass('date'))            header.data('colType', 'date');
        else if(header.hasClass('eDate'))      header.data('colType', 'eDate');
        else if(header.hasClass('memory'))     header.data('colType', 'memory');
        else if(header.hasClass('alphabetic')) header.data('colType', 'string');
        else                                   header.data('colType', 'numeric');

        header.data('column', columnIndex);
        header.append('<span class="carat"/>');

        header.click(function(event){

          // does this really need data??
          sortAscending = (sortColumn == columnIndex && sortAscending) ? false : true;
          sortColumn = columnIndex;
          sortType = header.data('colType');

          sortRows();

        });

      });

      tbody = requireElement('tbody', table, '<tbody> is required inside <table>');
      $('tbody tr', table).each(function(rowIndex){
        var el = $(this);
        if(el.hasClass('noDataRow')) return;
        initializeRow(el, rowIndex);
      });

      if(options.addNewRows && form){
        $('#addNewRow_' + id).hide();
        emptyRow = $('#newDataRow_' + id);
        var addRow = $('<p class="addRow"/>');
        var addRowLink = $('<a href="#add_row">' + options.addRowLabel + '</a>');
        addRowLink.click(function(){
          walkCells(null, null, 1);
          addRow.hide();
          return false;
        });
        addRow.append(addRowLink);
        table.after(addRow);
        if(!perRow) perRow = $('td.editable', emptyRow).length;
      }

       $('input[type=text]', table).attr('autoComplete', 'off');
       el.addClass('activated');
    }











    function initializeRow(row, rowIndex){
      rows = rows.add(row);
      //addedRows.add(row);
      addStripe(row, rowIndex);
      var deleteCheckbox = $('input[name^=delete]', row);
      if(deleteCheckbox.length > 0){
        deleteCheckbox.parents('td').click(function(){
          beginRowDelete(row, deleteCheckbox);
          return false;
        });
      }

      row.data('keyInput', $('input[name^=edit]', row));
      if(!perRow) perRow = $('td.editable', row).length;

      $('td', row).each(function(columnIndex){
        var cell = $(this);
        if(cell.hasClass('editable')){
          editableCells.push(cell);
          if(!form) throwError('Cells require a <form> to be editable.');
          var span = $('span', cell);
          var input = $('input,select,textarea', cell);
          var inputIsSelect   = input.is('select');
          var inputIsTextarea = input.is('textarea');
          span.show();
          input.hide();
          input.data('currentValue', input.val());
          input.data('column', columnIndex);
          var focused = false;
          cell.click(function(event){
            if(!focused){
              span.hide();
              input.show();
              input.focus();
              input.select();
              focused = true;
            }
          });

          input.blur(function(event){
            span.show();
            input.hide();
            focused = false;
          });

          input.change(function(event){
            addJob(row, cell, input, span);
          });
          input.keydown(function(event){
            switch(event.keyCode){
              case keyCodes.TAB:
                walkCells(input, cell, (event.shiftKey) ? -1 : 1);
              break;
              case keyCodes.ESC:
                input.val(input.data('currentValue'));
                input.select();
              break;
              case keyCodes.ENTER:
                if(inputIsTextarea && !event.shiftKey) return;
                var step = (event.shiftKey) ? -perRow : perRow;
                walkCells(input, cell, step);
                return false;
              break;
              case keyCodes.UP:
                if(inputIsSelect) return;
                walkCells(input, cell, -perRow);
              break;
              case keyCodes.DOWN:
                if(inputIsSelect) return;
                walkCells(input, cell, perRow);
              break;
              default: return true;
            }
            return false;
          });

        }


//  
//  
//        } else if(cell.getElement("input[name^=delete]")){
//  
//          var input = this.requireElement("input[type=checkbox]", cell, "An <input> checkbox element is required for deletable rows in cell " + cellID + '.\n(Name property should be "delete[]".)');
//          if(this.options.hideInputs) input.setStyle("display", "none");
//  
//          var label = cell.getElement("label");
//          if(label) label.setStyle("display", "block");
//          cell.addEvent("click", function(event){
//  
//            event.preventDefault();
//            this.deleteDataRow(row, input);
//  
//          }.bindWithEvent(this));
//  
//        }
//  
//      }, this);
//    },

      });

    }

    function walkCells(input, cell, step){
      var index = (cell) ? $.inArray(cell, editableCells) : 0;
      var nextIndex = index + step;
      if(options.addNewRows && nextIndex >= editableCells.length){
        addNewRow();
      }
      var target = editableCells[nextIndex];
      if(target){
        target.trigger('click');
      }
    }

    function addStripe(row, index){
      row.attr('class', (index % 2 == 0) ? 'odd' : 'even');
    }

    function requireElement(selector, el, error){
      var found = el.find(selector);
      if(found.length == 0) throwError(error);
      return found;
    }

    function throwError(error){
      var exception = "TableGear Error: " + error;
      alert(exception);
      throw new Error(exception);
    }

    function addNewRow(){
      $('.noDataRow', tbody).remove();
      var newRow = emptyRow.clone();
      newRow.removeAttr('style');
      tbody.append(newRow);
      if(window.HotText) new HotText(".hotText", {scope: newDataRow});
      initializeRow(newRow, rows.length - 1);
    }

    function beginRowDelete(row, input){
      var key = input.val();
      if(!key){
        removeRow();
        return;
      }
      if(options.deletePrompt && !confirm(options.deletePrompt)) return;
      row.addClass('loading');
      $.post(url, {
        'table': id,
        'delete[]': key
      }, function(response){
        var json = $.parseJSON(response);
        row.removeClass('loading');
        if(json.action == 'delete' && json.key == key){
          removeRow(row);
          if(json.totals) updateTotals(json.totals);
        }
      });
    }

    function removeRow(row){
      rows = rows.not(row);
      row.remove();
      if(rows.length < 1){
        var message = options.noDataMessage;
        var colspan = $('thead th:visible', table).length;
        var noDataRow = $('<tr class="noDataRow odd"><td align="center" colspan="'+colspan+'">'+message+'</td></tr>');
        tbody.append(noDataRow);
      }
//    removeRow: function(row){
//      this.rows.erase(row);
//      row.destroy();
//      if(this.rows.length < 1){
//        var colspan = this.headers.length;
//        this.headers.each(function(header){
//          if(header.getStyle("display") == "none") colspan--;
//        });
//        var noData = new Element("td", {"text": this.options.noDataMessage,"colspan": colspan,"align": "center"});
//        var noDataRow = new Element("tr", {"class": "noDataRow odd"});
//        noDataRow.adopt(noData);
//        this.tbody.adopt(noDataRow);
//        this.addRow.setStyle("display", "block");
//      }
//      this.sortRows();
//    },
    }

    function addJob(row, cell, input, span){
      if(input.val() == input.data('currentValue')) return;

      /*
      if(input.data('column') == sortColumn){
        $('.carat', headers[sortColumn]).remove();
        sortColumn = null;
      }
      */
      var job = {
        row: row,
        input: input,
        span: span,
        cell: cell
      }
      cell.addClass('loading');
      addToQueue(job);
    }

    function addToQueue(job){
      queue.push(job);
      if(!currentJob) nextRequest();
    }

    function nextRequest(){
      if(queue.length <= 0) return;
      currentJob = queue.shift();
      var params = {
        table: id,
        data:  {},
        column: currentJob.input.data('column')
      }
      var field = getInputField(currentJob.input);
      var key = currentJob.row.data('keyInput').val();
      if(key){
        params["edit[]"] = key;
        params.data[key] = {};
        params.data[key][field] = currentJob.input.val();
      } else {
        params["insert"] = true;
        params.data[field] = currentJob.input.val();
      }
      $.post(url, params, jobComplete);
    }

    function jobComplete(response){
      var json = $.parseJSON(response);
      currentJob.cell.removeClass("loading");
      if(!json || json.affected < 1){
        currentJob.input.val(currentJob.data('currentValue'));
        return;
      }
      currentJob.span.html(json.formatted);
      currentJob.input.val(json.value);
      currentJob.input.data('currentValue', json.value);

      if(json.action == 'insert'){
        $('select,input,textarea', currentJob.row).each(function(){
          var el = $(this);
          var name = el.attr('name');
          if(name == 'edit[]' || name == 'delete[]' || (json.auto && json.auto == field)){
            el.val(json.key);
          } else {
            var field = getInputField(currentJob.input);
            el.attr('name', 'data['+json.key+']['+field+']');
          }
        });
        if(json.auto){
          $('.auto_increment', currentJob.row).text(json.key);
        }
      } else if(json.action == 'edit'){
        currentJob.span.text(json.formatted);
        currentJob.input.val(json.value);
      }
      if(json.updatedOptions && currentJob.input.is('select')){
        input.empty();
        $.each(json.updatedOptions, function(i, data){
          var option = $('<option value="'+ data.value +'">'+ data.formatted +'</option');
          if(data.value == json.value) option.attr('selected', 'selected');
          input.append(option);
        });
      }
      if(json.totals){
        updateTotals(json.totals);
      }
      currentJob = null;
      nextRequest();
    }

    function getInputField(input){
      var name  = input.attr('name');
      var match = name.match(/^data(?:\[.+?\])?\[(.+?)\]$/);
      return match[1];
    }

  };

})();

//  
//      if(this.options.taskMaster > 1000) this.taskMaster.periodical(this.options.taskMaster, this);
//  
//      if(this.options.paginated){
//        if($('prevPage')){
//          this.prevPage = $('prevPage');
//          document.addEvent('pageUpKey', function(event){
//            window.location = this.prevPage.href;
//          }.bind(this));
//        }
//        if($('nextPage')){
//          this.nextPage = $('nextPage');
//          document.addEvent('pageDownKey', function(event){
//            window.location = this.nextPage.href;
//          }.bind(this));
//        }
//      }
//  
//  
//  
//    },
//  
//    queue: new Array(),
//  
//    requireElement: function(css, parent, error, exclude){
//  
//      //var elements = parent.getChildren(css);
//  
//      /* This is a workaround for lack of "," support in getChildren... change to getChildren when fixed */
//      var split = css.split(",");
//      var elements = [];
//      for(i=0;i<split.length;i++){
//        elements.combine(parent.getChildren(split[i]));
//      }
//      /* End workaround */
//  
//      if(!elements) this.throwError(error);
//      var match;
//      elements.each(function(element){
//        if(element.hasClass(exclude)) return;
//        else match = element;
//      });
//      return match;
//    },
//  
//    throwError: function(error){
//  
//      var exception = "TableGear Error: " + error;
//      alert(exception);
//      throw new Error(exception);
//    },
//  
//    addJob: function(row, cell, input, span){
//  
//      if(input.get("value") == input.retrieve("currentValue")) return;
//  
//      if(input.retrieve("column") == this.currentSortIndex){
//        this.headers[this.currentSortIndex].carat.empty();
//        this.currentSortIndex = null;
//      }
//  
//      var job = {
//        row: row,
//        input: input,
//        span: span,
//        cell: cell
//      }
//      if(this.addedRows.contains(row))  this.addedRows.erase(row);
//      cell.addClass("loading");
//      this.addToQueue(job);
//    },
//  
//    getInputField: function(input){
//      var name  = input.get("name");
//      var match = name.match(/^data(?:\[.+?\])?\[(.+?)\]$/);
//      return match[1];
//    },
//  
//    addToQueue: function(job){
//  
//      this.queue.push(job);
//      if(!this.request || !this.request.running) this.nextRequest();
//    },
//  
//    nextRequest: function(){
//  
//      if(this.queue.length <= 0) return;
//      this.currentJob = this.queue.shift();
//  
//      var input = this.currentJob.input;
//      var params = {
//        "table": this.tableID,
//        "data": {},
//        "column": input.retrieve("column")
//      }
//      var field = this.getInputField(input);
//      var keyInput = this.currentJob.row.retrieve("keyInput");
//      if(keyInput.value){
//        params["edit[]"] = keyInput.value;
//        params.data[keyInput.value] = {};
//        params.data[keyInput.value][field] = input.value;
//      } else {
//        params["insert"] = true;
//        params.data[field] = input.value;
//      }
//  
//      this.request = new Request({
//        url: this.url,
//        onComplete: this.onComplete.bind(this),
//        data: params
//      });
//      this.request.send();
//    },
//  
//    onComplete: function(response){
//  
//      var json = JSON.decode(response);
//  
//      this.currentJob.cell.removeClass("loading");
//  
//      if(!json || json.affected <= 0){
//  
//        this.currentJob.input.set("value", this.currentJob.input.retrieve("currentValue"));
//        return;
//  
//      } else {
//  
//        this.currentJob.span.set("html", json.formatted);
//        this.setValue(this.currentJob.input, json.value);
//        this.currentJob.input.store("currentValue", json.value);
//        this.currentJob.row.store("rowUnchanged", true);
//  
//        if(json.action == "insert" || json.action == "edit"){
//          var formElements = this.currentJob.row.getElements("select,input,textarea");
//          formElements.each(function(el){
//            var name = el.get("name");
//            if(name == "edit[]" || name == "delete[]") el.value = json.key;
//            else {
//              var field = this.getInputField(el);
//              el.set("name", "data["+json.key+"]["+field+"]");
//              if(json.auto && json.auto == field){
//                el.set("value", json.key);
//              }
//            }
//          }.bind(this));
//          if(json.auto){
//            this.currentJob.row.getElement('.auto_increment').set("text", json.key);
//          }
//          if(json.updatedOptions && this.currentJob.input.match('select')){
//            var select = this.currentJob.input;
//            select.empty();
//            json.updatedOptions.each(function(data){
//              var option = new Element("option", {"text": data.formatted, "value": data.value})
//              if(data.value == json.value) option.set("selected", "selected");
//              select.adopt(option);
//            });
//          }
//        }
//        if(json.totals) this.updateTotals(json.totals);
//      }
//      this.currentJob = null;
//      this.nextRequest();
//    },
//  
//    sortRows: function(){
//  
//      if($defined(this.currentSortIndex)){
//        this.rows = this.rows.sort(this.sortData.bind(this));
//        if(this.currentDesc) this.rows = this.rows.reverse();
//      }
//  
//      this.editableCells.empty();
//  
//      this.rows.each(function(row, index){
//  
//        this.addStripe(row, index+1);
//        var cells = row.getChildren("td.editable");
//        cells.each(function(cell){
//          this.editableCells.push(cell);
//        }, this);
//      }, this);
//  
//      this.tbody.adopt(this.rows);
//      var carat = (this.currentDesc) ? this.options.descCarat : this.options.ascCarat;
//  
//      this.headers.each(function(header){
//  
//        if(header.carat){
//          if(header == this.headers[this.currentSortIndex]) header.carat.set("html", carat);
//          else header.carat.empty();
//        }
//      }, this);
//  
//    },
//  
//    update: function(){
//  
//      this.rows = this.tbody.getChildren("tr");
//      this.sortRows();
//    },
//  
//    addStripe: function(row, index){
//  
//      if(!this.options.rowStriping) return;
//      var css = ((index + 1) % 2) ? "even" : "odd";
//      row.erase("class");
//      row.addClass(css);
//    },
//  
//    sortData: function(row1, row2){
//  
//      var data1 = this.getData(row1, this.currentSortIndex);
//      var data2 = this.getData(row2, this.currentSortIndex);
//  
//      if(this.currentType == "numeric"){
//  
//        data1 = data1.replace(/[,:]/g, "");
//        data2 = data2.replace(/[,:]/g, "");
//        data1 = data1.match(/[-+]?\d*\.?\d+/);
//        data2 = data2.match(/[-+]?\d*\.?\d+/);
//        if(data1) data1 = data1[0].toFloat();
//        else data1 = -Infinity;
//        if(data2) data2 = data2[0].toFloat();
//        else data2 = -Infinity;
//  
//      } else if(this.currentType == "date" || this.currentType == "eDate"){
//  
//        var eur = (this.currentType == "eDate") ? true : false;
//        data1 = this.getDate(data1, eur);
//        data2 = this.getDate(data2, eur);
//  
//      } else if(this.currentType == "memory"){
//  
//        data1 = this.getMemory(data1);
//        data2 = this.getMemory(data2);
//  
//      } else if (this.currentType == "string"){
//        data1 = data1.toLowerCase();
//        data2 = data2.toLowerCase();
//      }
//  
//      if(data1 == data2) return 0;
//      return (data1 < data2) ? -1 : 1;
//    },
//  
//    getData: function(row, index){
//  
//      if(index == null) return;
//      var cell = row.cells[index];
//      var span = cell.getElement("span");
//      var text = (span) ? span.get("text") : cell.get("text");
//      return text.trim();
//    },
//  
//    getDate: function(s, eur){
//  
//      if(eur){
//        s = s.replace(/(\d+)\/(\d+)\/(\d+)/g, "$2/$1/$3");
//      }
//      var date = new Date(s);
//      if(!date.getYear()){
//  
//        /* Get dates formatted 10*23*2003 (or some other strange delimiter) */
//        var match = s.match(/^(\d{1,2})\D(\d{1,2})\D(\d{2,4})$/);
//        if(match) date = new Date(match[1] + "/" + match[2] + "/" + match[3]);
//        else {
//          /* Get foreign dates: 2003年10月23日 */
//          match = s.match(/^(\d{4})\D(\d{1,2})\D(\d{1,2})\D$/g);
//          if(match) date = new Date(match[1] + "/" + match[2] + "/" + match[3]);
//        }
//      }
//  
//      return date.getYear() ? date : s;
//    },
//  
//    getMemory: function(s){
//  
//      var match = s.match(/^([0-9.]+)\s*([a-z]+)$/i);
//      if(!match) return null;
//      var data = match[1].replace(/,/g, "").toFloat();
//      var unit = match[2].toLowerCase();
//      if(!unit) unit = "b";
//      var units = ["b", "kb", "mb", "gb", "tb"];
//      data = data * Math.pow(1000, units.indexOf(unit));
//      return data;
//    },
//  
//    setValue: function(input, value){
//      var tag = input.get("tag");
//      if(tag == "select") input.selectedIndex = input.getElement("option[value="+value+"]").index;
//      else input.set("value", value);
//    },
//  
//    walkCells: function(current, step){
//  
//      var index = this.editableCells.indexOf(current);
//      if(this.options.addNewRows && (index + step >= this.editableCells.length)) this.addNewRow();
//      var target = this.editableCells[index + step];
//      if(step < 0 && target) this.removeUnusedRows(target);
//      if(target) target.fireEvent("click");
//    },
//  
//    updateTotals: function(totals){
//  
//      if(this.rows.length == 0) this.totalsRow.setStyle("display", "none");
//      else this.totalsRow.removeProperty("style");
//  
//      totals.each(function(obj){
//        if(obj.column){
//          var editOffset = this.hasKeyInput ? 1 : 0;
//          var cell = this.totalsRow.cells[(obj.column - 1) + editOffset];
//        } else if(obj.field){
//          var cell = this.totals[obj.field];
//        }
//        if(cell) cell.set("text", obj.total);
//      }, this);
//    },
//  
//  
//    addNewRow: function(event){
//      if(this.options.limitAddedRows && this.addedRows.length >= this.options.limitAddedRows) return;
//      var noDataRow = this.tbody.getElement("tr.noDataRow");
//      if(noDataRow){
//        noDataRow.dispose();
//        this.rows.erase(noDataRow);
//      }
//      var newDataRow = this.emptyDataRow.clone();
//      newDataRow.erase("style");
//      newDataRow.inject(this.tbody);
//      if(window.HotText) new HotText(".hotText", {scope: newDataRow});
//      this.rows.push(newDataRow);
//      this.addedRows.push(newDataRow);
//      this.initializeRow(newDataRow, this.rows.length-1);
//    },
//  
//    removeUnusedRows: function(current){
//  
//      var currentRow = this.rows.indexOf(current.getParent("tr"));
//      var rowsReversed = $A(this.addedRows).reverse();
//      rowsReversed.each(function(row){
//        if(this.rows.indexOf(row) <= currentRow) return;
//        this.addedRows.erase(row);
//        this.removeRow(row);
//      }, this);
//    },
//  
//    protectRow: function(event){
//      var input = event.target;
//      if(input.get("value") == input.retrieve("currentValue")) return;
//      var row = input.getParent("tr");
//      this.addedRows.erase(row);
//    },
//  
//    deleteDataRow: function(row, input){
//  
//      var key = input.get("value");
//      if(!key){
//        this.removeRow(row);
//        return;
//      }
//  
//      if(this.options.deletePrompt && !confirm(this.options.deletePrompt)) return;
//  
//      this.request = new Request({
//        url: this.url,
//        onComplete: function(response){
//          var json = JSON.decode(response);
//          row.removeClass("loading");
//          if(json.action == "delete" && json.key == key){
//            this.removeRow(row);
//            if(json.totals) this.updateTotals(json.totals);
//          }
//        }.bind(this),
//        data: {"table": this.tableID, 'delete[]': key}
//      });
//      row.addClass("loading");
//      this.request.send();
//    },
//  
//    removeRow: function(row){
//      this.rows.erase(row);
//      row.destroy();
//      if(this.rows.length < 1){
//        var colspan = this.headers.length;
//        this.headers.each(function(header){
//          if(header.getStyle("display") == "none") colspan--;
//        });
//        var noData = new Element("td", {"text": this.options.noDataMessage,"colspan": colspan,"align": "center"});
//        var noDataRow = new Element("tr", {"class": "noDataRow odd"});
//        noDataRow.adopt(noData);
//        this.tbody.adopt(noDataRow);
//        this.addRow.setStyle("display", "block");
//      }
//      this.sortRows();
//    },
//  
//    taskMaster: function(){
//  
//      if(!this.currentJob) return;
//      if(!this.lastJob || this.lastJob != this.currentJob){
//        this.lastJob = this.currentJob;
//        return;
//      }
//      this.request.cancel();
//      this.request.send(); // Get back to work!
//    }
//  });
//  
//  
//  if(!Element.Events.tabkey){
//    Element.Events.tabkey = {
//      base: "keydown",
//      condition: function(event){
//        return (event.key == "tab");
//      }
//    }
//  }
//  if(!Element.Events.enterkey){
//    Element.Events.enterkey = {
//      base: "keydown",
//      condition: function(event){
//        return (event.key == "enter");
//      }
//    }
//  }
//  
//  if(!Element.Events.esckey){
//    Element.Events.esckey = {
//      base: "keydown",
//      condition: function(event){
//        return (event.key == "esc");
//      }
//    }
//  }
//  
//  if(!Element.Events.arrowkeys){
//    Element.Events.arrowkeys = {
//      base: "keydown",
//      condition: function(event){
//        var arrows = ["up", "down"];
//        return (arrows.contains(event.key));
//      }
//    }
//  }
//  
//  if(!Element.Events.pageUpKey){
//    Element.Events.pageUpKey = {
//      base: "keydown",
//      condition: function(event){
//        return (event.code == 33);
//      }
//    }
//  }
//  
//  if(!Element.Events.pageDownKey){
//    Element.Events.pageDownKey = {
//      base: "keydown",
//      condition: function(event){
//        return (event.code == 34);
//      }
//    }
//  }
//
//

// Fix for jQuery 1.4.2 strangely not firing browser native focus event.
$.fn.focus = function(){
  this.each(function(){
    if(this.focus) this.focus();
  });
};
