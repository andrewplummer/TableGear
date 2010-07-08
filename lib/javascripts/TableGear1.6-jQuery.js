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
    var sortType;
    var editableCells;
    var perRow;
    var rows;
    var headers;
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
      rows = [];
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

      headers = $('thead th', table)
      headers.each(function(columnIndex){

        var header = $(this);

        if(!header.hasClass('sortable')) return;

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
          walkCells(null, null, null, 1);
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
      rows.push(row);
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
        var column = $(headers[columnIndex]);
        var columnType = column.data('colType');

        if(column.hasClass("sortable")){
          if(columnType == 'numeric' && !column.hasClass('numeric')){
            var text = cell.text();
            if(text && !text.match(/[-+]?\d*\.?\d+/)) column.data('colType', 'string');
          }
        }

        if(cell.hasClass('editable')){
          editableCells.push(this);
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
                walkCells(row, cell, input, (event.shiftKey) ? -1 : 1);
              break;
              case keyCodes.ESC:
                input.val(input.data('currentValue'));
                input.select();
              break;
              case keyCodes.ENTER:
                if(inputIsTextarea && !event.shiftKey) return;
                var step = (event.shiftKey) ? -perRow : perRow;
                walkCells(row, cell, input, step);
                return false;
              break;
              case keyCodes.UP:
                if(inputIsSelect) return;
                walkCells(row, cell, input, -perRow);
              break;
              case keyCodes.DOWN:
                if(inputIsSelect) return;
                walkCells(row, cell, input, perRow);
              break;
              default: return true;
            }
            return false;
          });

        }

      });

    }

    function walkCells(row, cell, input, step){
      var index = (cell) ? $.inArray(cell.get(0), editableCells) : 0;
      var nextIndex = index + step;
      console.info(nextIndex, editableCells.length);
      if(options.addNewRows && nextIndex >= editableCells.length){
        addNewRow();
      }
      if(step < 0) removeUnusedRows(row);
      var target = editableCells[nextIndex];
      if(target){
        $(target).click();
      }
    }

    function addStripe(row, index){
      if(index % 2 == 0){
        row.addClass('odd');
        row.removeClass('even');
      } else {
        row.addClass('even');
        row.removeClass('odd');
      }
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

    function removeUnusedRows(currentRow){
      var index = $.inArray(currentRow, rows);
      for(var i = rows.length - 1; i >= index; i--){
        var row = rows[i];
        if(row.hasClass('newRow')){
          removeRow(row);
        }
      }
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
      rows = $.grep(rows, function(r){ return r !== row; });
      row.remove();
      if(rows.length < 1){
        var message = options.noDataMessage;
        var colspan = $('thead th:visible', table).length;
        var noDataRow = $('<tr class="noDataRow odd"><td align="center" colspan="'+colspan+'">'+message+'</td></tr>');
        tbody.append(noDataRow);
      }
      editableCells = [];
      $('td.editable', tbody).each(function(){ editableCells.push(this); });
    }

    function addJob(row, cell, input, span){
      if(input.val() == input.data('currentValue')) return;
      row.removeClass('newRow');

      // Unset the sort on the table.
      if(input.data('column') == sortColumn){
        $('.carat', headers[sortColumn]).empty();
        sortColumn = null;
      }

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

    function sortRows(){
      if(sortColumn !== undefined){
        rows = rows.sort(sortData);
        if(sortAscending === false) rows.reverse();
      }

      editableCells = [];

      $.each(rows, function(index){
        var row = $(this);
        addStripe(row, index + 1);
        $('td.editable', row).each(function(){
          editableCells.push(this);
        });
        tbody.append(row);
      });

      headers.each(function(index, header){
        var carat = $('.carat', header);
        if(header == headers[sortColumn]){
          carat.html(sortAscending ? options.ascCarat : options.descCarat);
        } else {
          carat.empty();
        }
      });

    }

    function sortData(row1, row2){

      var data1 = getData(row1, sortColumn);
      var data2 = getData(row2, sortColumn);

      if(sortType == 'numeric'){

        data1 = data1.replace(/[,:]/g, "");
        data2 = data2.replace(/[,:]/g, "");
        data1 = data1.match(/[-+]?\d*\.?\d+/);
        data2 = data2.match(/[-+]?\d*\.?\d+/);
        if(data1) data1 = parseFloat(data1[0]);
        else      data1 = -Infinity;
        if(data2) data2 = parseFloat(data2[0]);
        else      data2 = -Infinity;

      } else if(sortType == 'date' || sortType == 'eDate'){

        var eur = (sortType == "eDate") ? true : false;
        data1 = getDate(data1, eur);
        data2 = getDate(data2, eur);

      } else if(sortType == 'memory'){

        data1 = getMemory(data1);
        data2 = getMemory(data2);

      } else if(sortType == 'string'){
        data1 = data1.toLowerCase();
        data2 = data2.toLowerCase();
      }
      console.info(data1, data2);

      if(data1 == data2) return 0;
      return (data1 < data2) ? -1 : 1;

    }

    function getData(row, index){
      if(index == null) return;
      var cell = row.get(0).cells[index];
      var span = $('span', cell);
      var text = (span) ? span.text() : cell.text();
      return text;
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

// Fix for jQuery 1.4.2 strangely not firing browser native focus event.
$.fn.focus = function(){
  this.each(function(){
    if(this.focus) this.focus();
  });
};
