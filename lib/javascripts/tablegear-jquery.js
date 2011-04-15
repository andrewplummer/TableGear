/*
 *
 *  TableGear (Dynamic table data in HTML)
 *
 *  Version: 1.6.2 for jQuery
 *  Documentation: AndrewPlummer.com (http://www.andrewplummer.com/code/tablegear/)
 *  Inspired by: TableKit for Prototype (http://www.millstream.com.au/view/code/tablekit/)
 *  Written for: jQuery 1.4
 *  License: MIT-style License
 *
 *  Copyright (c) 2010 Andrew Plummer
 *
 *
 */


(function($){

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
    setDefaults('taskMaster',     -1,                 options);

    initialize(this);

    var table;
    var tbody;
    var form;

    var url; // URL of the form
    var id;  // Id of the table

    var sortColumn;
    var sortAscending;
    var sortType;

    var rows;
    var perRow;
    var headers;
    var editableCells;
    var totals;
    var totalsRow;
    var emptyRow;

    var addRow;

    var keyCodes = {
      ENTER:    13,
      TAB:      9,
      ESC:      27,
      LEFT:     37,
      UP:       38,
      RIGHT:    39,
      DOWN:     40,
      PAGEUP:   33,
      PAGEDOWN: 34
    }

    function initialize(el){

      editableCells = [];
      rows = [];
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


      var tfoot = $('tfoot', table);
      if(tfoot.length > 0){
        var totalCells = $('td.total,th.total', tfoot);
        if(totalCells.length > 0){
          totals = {};
          totalsRow = totalCells.eq(0).parent();
          totalCells.each(function(){
            var cell = $(this);
            var field = cell.attr('class').match(/\w+/);
            if(!field) return;
            totals[field[0]] = cell;
          });
        }
      }


      if(options.addNewRows && form){
        $('#addNewRow_' + id).hide();
        emptyRow = $('#newDataRow_' + id);
        addRow = $('<p class="addRow"/>');
        if(rows.length > 0 && !options.showAddNewRow) addRow.hide();
        var addRowLink = $('<a href="#add_row">' + options.addRowLabel + '</a>');
        addRowLink.click(function(){
          var row = addNewRow();
          if(!options.showAddNewRow) addRow.hide();
          $('td:visible:first', row).click();
          return false;
        });
        addRow.append(addRowLink);
        table.after(addRow);
        if(!perRow) perRow = $('td.editable', emptyRow).length;
      }

       $('input[type=text]', table).attr('autoComplete', 'off');
       el.addClass('activated');

      if(options.paginated){
        var prevURL = $('#prevPage').attr('href');
        var nextURL = $('#nextPage').attr('href');
        $(document).keydown(function(event){
          if(event.keyCode == keyCodes.PAGEUP && prevURL){
            window.location = prevURL;
          }
          if(event.keyCode == keyCodes.PAGEDOWN && nextURL){
            window.location = nextURL;
          }
        });
      }

    }


    function initializeRow(row, rowIndex){
      rows.push(row);
      addStripe(row, rowIndex);
      var deleteCheckbox = $('input[name^=delete]', row);
      if(deleteCheckbox.length > 0){
        deleteCheckbox.parents('td').click(function(){
          sendRowDelete(row, deleteCheckbox);
          return false;
        });
      }

      var key = $('input[name^=edit]', row).val();

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
            updateField(row, cell, input, span, columnIndex, key);
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
      var newRow = emptyRow.clone().removeAttr('id');
      newRow.removeAttr('style');
      tbody.append(newRow);
      if(window.HotText) new HotText(".hotText", {scope: newDataRow});
      initializeRow(newRow, rows.length);
      return newRow;
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

    function sendRowDelete(row, input){
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
        addRow.show();
      }
      editableCells = [];
      $('td.editable', tbody).each(function(){ editableCells.push(this); });
      sortRows();
    }

    function updateField(row, cell, input, span, column){

      if(input.val() == input.data('currentValue')) return;

      row.removeClass('newRow');
      cell.addClass('loading');

      // Unset the sort on the table.
      if(column == sortColumn){
        $('.carat', headers[column]).empty();
        sortColumn = null;
      }

      var params = {
        table: id,
        data:  {},
        column: column
      }

      var field = getInputField(input);
      var value = input.val();
      var key = $('input[name^=edit]', row).val();

      if(key){
        params["edit[]"] = key;
        params.data[key] = {};
        params.data[key][field] = value;
      } else {
        params["insert"] = true;
        params.data[field] = value;
      }


      $.post(url, params, function(response){
        jobComplete(response, row, cell, input, span);
      });



    }

    function jobComplete(response, row, cell, input, span){
      var json = $.parseJSON(response);
      cell.removeClass("loading");
      if(!json || json.affected < 1){
        input.val(input.data('currentValue'));
        return;
      }
      span.html(json.formatted);
      input.val(json.value);
      input.data('currentValue', json.value);

      if(json.action == 'insert'){
        $('select,input,textarea', row).each(function(){
          var el = $(this);
          var name = el.attr('name');
          if(name == 'edit[]' || name == 'delete[]' || (json.auto && json.auto == field)){
            el.val(json.key);
          } else {
            var field = getInputField(el);
            el.attr('name', 'data['+json.key+']['+field+']');
          }
        });
        if(json.auto){
          $('.auto_increment', row).text(json.key);
        }
      } else if(json.action == 'edit'){
        span.html(json.formatted);
        input.val(json.value);
      }
      if(json.updatedOptions && input.is('select')){
        input.empty();
        $.each(json.updatedOptions, function(i, data){
          var option = $('<option value="'+ data.value +'"/>').html(data.formatted);
          if(data.value == json.value) option.attr('selected', 'selected');
          input.append(option);
        });
      }
      if(json.totals){
        updateTotals(json.totals);
      }
    }

    function updateTotals(updated){
      if(rows.length == 0) totalsRow.hide();
      else totalsRow.removeAttr('style');
      $.each(updated, function(i, obj){
        if(obj.column){
          var cell = totalsRow.cells[obj.column];
        } else if(obj.field){
          var cell = totals[obj.field];
        }
        if(cell) cell.text(obj.total);
      });
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

    function getDate(s, eur){
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
    }

    function getMemory(s){
      var match = s.match(/^([0-9.]+)\s*([a-z]+)$/i);
      if(!match) return null;
      var data = parseFloat(match[1].replace(/,/g, ""));
      var unit = match[2].toLowerCase();
      if(!unit) unit = "b";
      var units = ["b", "kb", "mb", "gb", "tb"];
      data = data * Math.pow(1000, $.inArray(unit, units));
      return data;
    }

    function findOrCreateElement(){
    }

  };

})(jQuery);

// Fix for jQuery 1.4.2 strangely not firing browser native focus event.
jQuery.fn.focus = function(){
  this.each(function(){
    if(this.focus) this.focus();
  });
};
