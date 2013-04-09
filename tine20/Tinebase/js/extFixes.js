Ext.override(Ext.data.Store, {
    /**
     * String cast the id, as otherwise the entries are not found
     */
    indexOfId : function(id){
        return this.data.indexOfKey(String(id));
    }
});

/**
 * for some reasons the original fix insertes two <br>'s on enter for webkit. But this is one to much
 */
Ext.apply(Ext.form.HtmlEditor.prototype, {
        fixKeys : function(){ // load time branching for fastest keydown performance
        if(Ext.isIE){
            return function(e){
                var k = e.getKey(),
                    doc = this.getDoc(),
                        r;
                if(k == e.TAB){
                    e.stopEvent();
                    r = doc.selection.createRange();
                    if(r){
                        r.collapse(true);
                        r.pasteHTML('&nbsp;&nbsp;&nbsp;&nbsp;');
                        this.deferFocus();
                    }
                }else if(k == e.ENTER){
                    r = doc.selection.createRange();
                    if(r){
                        var target = r.parentElement();
                        if(!target || target.tagName.toLowerCase() != 'li'){
                            e.stopEvent();
                            r.pasteHTML('<br />');
                            r.collapse(false);
                            r.select();
                        }
                    }
                }
            };
        }else if(Ext.isOpera){
            return function(e){
                var k = e.getKey();
                if(k == e.TAB){
                    e.stopEvent();
                    this.win.focus();
                    this.execCmd('InsertHTML','&nbsp;&nbsp;&nbsp;&nbsp;');
                    this.deferFocus();
                }
            };
        }else if(Ext.isWebKit){
            return function(e){
                var k = e.getKey();
                if(k == e.TAB){
                    e.stopEvent();
                    this.execCmd('InsertText','\t');
                    this.deferFocus();
                }
             };
        }
    }()
});

/**
 * fix broken ext email validator
 * 
 * @type RegExp
 */
Ext.apply(Ext.form.VTypes, {
    // 2011-01-05 replace \w with [^\s,\x00-\x2F,\x3A-\x40,\x5B-\x60,\x7B-\x7F] to allow idn's
    emailFixed:  /^(("[\w-\s]+")|([\w-]+(?:\.[\w-]+)*)|("[\w-\s]+")([\w-]+(?:\.[\w-]+)*))(@((?:([^\s,\x00-\x2F,\x3A-\x40,\x5B-\x60,\x7B-\x7F]|-)+\.)*[^\s,\x00-\x2F,\x3A-\x40,\x5B-\x60,\x7B-\x7F]([^\s,\x00-\x2F,\x3A-\x40,\x5B-\x60,\x7B-\x7F]|-){0,66})\.([^\s,\x00-\x2F,\x3A-\x40,\x5B-\x60,\x7B-\x7F]{2,6}?)$)|(@\[?((25[0-5]\.|2[0-4][0-9]\.|1[0-9]{2}\.|[0-9]{1,2}\.))((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\.){2}(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\]?$)/i,
    
    urlFixed: /(((^https?)|(^ftp)):\/\/(([^\s,\x00-\x2F,\x3A-\x40,\x5B-\x60,\x7B-\x7F]|-)+\.)+[^\s,\x00-\x2F,\x3A-\x40,\x5B-\x60,\x7B-\x7F]{2,3}(\/([^\s,\x00-\x2F,\x3A-\x40,\x5B-\x60,\x7B-\x7F]|-|%)+(\.[^\s,\x00-\x2F,\x3A-\x40,\x5B-\x60,\x7B-\x7F]{2,})?)*((([^\s,\x00-\x2F,\x3A-\x40,\x5B-\x60,\x7B-\x7F]|[\-\.\?\\\/+@&#;`~=%!])*)(\.[^\s,\x00-\x2F,\x3A-\x40,\x5B-\x60,\x7B-\x7F]{2,})?)*\/?)/i,
    
    email:  function(v) {
        return this.emailFixed.test(v);
    },
    
    url: function(v) {
        return this.urlFixed.test(v);
    }
});


/**
 * fix textfield allowBlank validation
 */
Ext.override(Ext.form.TextField, {
    validator:function(text){
        return this.allowBlank!==false || Ext.util.Format.trim(text).length > 0;
    }
});

Ext.applyIf(Ext.tree.MultiSelectionModel.prototype, {
    /**
     * implement convinience function as expected from grid selection models
     * 
     * @namespace {Ext.tree}
     * @return {Node}
     */
    getSelectedNode: function() {
        var selection = this.getSelectedNodes();
        return Ext.isArray(selection) ? selection[0] : null
    }
});

/**
 * fix timezone handling for date picker
 * 
 * The getValue function always returns 00:00:00 as time. So if a form got filled
 * with a date like 2008-10-01T21:00:00 the form returns 2008-10-01T00:00:00 although 
 * the user did not change the fieled.
 * 
 * In a multi timezone context this is fatal! When a user in a certain timezone set
 * a date (just a date and no time information), this means in his timezone the 
 * time range from 2008-10-01T00:00:00 to 2008-10-01T23:59:59. 
 * _BUT_ for an other user sitting in a different timezone it means e.g. the 
 * time range from 2008-10-01T02:00:00 to 2008-10-02T21:59:59.
 * 
 * So on the one hand we need to make sure, that the date picker only returns 
 * changed datetime information when the user did a change. 
 * 
 * @todo On the other hand we
 * need adjust the day +/- one day according to the timeshift. 
 */
/**
 * @private
 */
 Ext.form.DateField.prototype.setValue = function(date){
    // get value must not return a string representation, so we convert this always here
    // before memorisation
    if (Ext.isString(date)) {
        var v = Date.parseDate(date, Date.patterns.ISO8601Long);
        if (Ext.isDate(v)) {
            date = v;
        } else {
            date = Ext.form.DateField.prototype.parseDate.call(this, date);
        }
    }
    
    // preserve original datetime information
    this.fullDateTime = date;
    
    Ext.form.DateField.superclass.setValue.call(this, this.formatDate(this.parseDate(date)));
};
/**
 * @private
 */
Ext.form.DateField.prototype.getValue = function(){
    //var value = this.parseDate(Ext.form.DateField.superclass.getValue.call(this));
    
    // return the value that was set (has time information when unchanged in client) 
    // and not just the date part!
    var value =  this.fullDateTime;
    return value || "";
};

/**
 * We need to overwrite to preserve original time information because 
 * Ext.form.TimeField does not support seconds
 * 
 * @param {} time
 * @private
 */
 Ext.form.TimeField.prototype.setValue = function(time){
    this.fullDateTime = time;
    Ext.form.TimeField.superclass.setValue.call(this, this.formatDate(this.parseDate(time)));
};
/**
 * @private
 */
Ext.form.TimeField.prototype.getValue = function(){
    // return the value that was set (has time information when unchanged in client) 
    // and not just the date part!
    var value =  this.fullDateTime;
    
    return value ? this.parseDate(value).dateFormat('H:i') : "";
};

/**
 * check if sort field is in column list to avoid server exceptions
 */
Ext.grid.GridPanel.prototype.applyState  = Ext.grid.GridPanel.prototype.applyState.createInterceptor(function(state){
    var cm = this.colModel,
        s = state.sort;
    
    if (s && cm.getIndexById(s.field) < 0) {
        delete state.sort;
    }
});

/**
 * fix interpretation of ISO-8601  formatcode (Date.patterns.ISO8601Long) 
 * 
 * Browsers do not support timezones and also javascripts Date object has no 
 * support for it.  All Date Objects are in _one_ timezone which may ore may 
 * not be the operating systems timezone the browser runs on.
 * 
 * parsing dates in ISO format having the timeshift appended (Date.patterns.ISO8601Long) lead to 
 * correctly converted Date Objects in the browsers timezone. This timezone 
 * conversion changes the the Date Parts and as such, javascipt widget 
 * representing date time information print values of the browsers timezone 
 * and _not_ the values send by the server!
 * 
 * So in a multi timezone envireonment, datetime information in the browser 
 * _must not_ be parsed including the offset. Just the values of the server 
 * side converted datetime information are allowed to be parsed.
 */
Date.parseIso = function(isoString) {
    return Date.parseDate(isoString.replace(/\+\d{2}\d{2}/, ''), 'Y-m-d\\Th:i:s');
};

/**
 * rename window
 */
Ext.Window.prototype.rename = function(newId) {
    // Note PopupWindows are identified by name, whereas Ext.windows
    // get identified by id this should be solved some time ;-)
    var manager = this.manager || Ext.WindowMgr;
    manager.unregister(this);
    this.id = newId;
    manager.register(this);
};

/**
 * utility class used by Button
 * 
 * Fix: http://yui-ext.com/forum/showthread.php?p=142049
 * adds the ButtonToggleMgr.getSelected(toggleGroup, handler, scope) function
 */
Ext.ButtonToggleMgr = function(){
   var groups = {};
   
   function toggleGroup(btn, state){
       if(state){
           var g = groups[btn.toggleGroup];
           for(var i = 0, l = g.length; i < l; i++){
               if(g[i] != btn){
                   g[i].toggle(false);
               }
           }
       }
   }
   
   return {
       register : function(btn){
           if(!btn.toggleGroup){
               return;
           }
           var g = groups[btn.toggleGroup];
           if(!g){
               g = groups[btn.toggleGroup] = [];
           }
           g.push(btn);
           btn.on("toggle", toggleGroup);
       },
       
       unregister : function(btn){
           if(!btn.toggleGroup){
               return;
           }
           var g = groups[btn.toggleGroup];
           if(g){
               g.remove(btn);
               btn.un("toggle", toggleGroup);
           }
       },
       
       getSelected : function(toggleGroup, handler, scope){
           var g = groups[toggleGroup];
           for(var i = 0, l = g.length; i < l; i++){
               if(g[i].pressed === true){
                   if(handler) {
                        handler.call(scope || g[i], g[i]);
                   }
                   return g[i];
               }
           }
           return;
       }
   };
}();

/**
 * add beforeloadrecords event
 */
Ext.data.Store.prototype.loadRecords = Ext.data.Store.prototype.loadRecords.createInterceptor(function(o, options, success) {
    return this.fireEvent('beforeloadrecords', o, options, success, this);
});

/**
 * state encoding converts null to empty object
 * 
 * -> take encoder/decoder from Ext 4.1 where this is fixed
 */
Ext.override(Ext.state.Provider, {
    /**
     * Decodes a string previously encoded with {@link #encodeValue}.
     * @param {String} value The value to decode
     * @return {Object} The decoded value
     */
    decodeValue : function(value){

        // a -> Array
        // n -> Number
        // d -> Date
        // b -> Boolean
        // s -> String
        // o -> Object
        // -> Empty (null)

        var me = this,
            re = /^(a|n|d|b|s|o|e)\:(.*)$/,
            matches = re.exec(unescape(value)),
            all,
            type,
            keyValue,
            values,
            vLen,
            v;
            
        if(!matches || !matches[1]){
            return; // non state
        }
        
        type = matches[1];
        value = matches[2];
        switch (type) {
            case 'e':
                return null;
            case 'n':
                return parseFloat(value);
            case 'd':
                return new Date(Date.parse(value));
            case 'b':
                return (value == '1');
            case 'a':
                all = [];
                if(value != ''){
                    values = value.split('^');
                    vLen   = values.length;

                    for (v = 0; v < vLen; v++) {
                        value = values[v];
                        all.push(me.decodeValue(value));
                    }
                }
                return all;
           case 'o':
                all = {};
                if(value != ''){
                    values = value.split('^');
                    vLen   = values.length;

                    for (v = 0; v < vLen; v++) {
                        value = values[v];
                        keyValue         = value.split('=');
                        all[keyValue[0]] = me.decodeValue(keyValue[1]);
                    }
                }
                return all;
           default:
                return value;
        }
    },

    /**
     * Encodes a value including type information.  Decode with {@link #decodeValue}.
     * @param {Object} value The value to encode
     * @return {String} The encoded value
     */
    encodeValue : function(value){
        var flat = '',
            i = 0,
            enc,
            len,
            key;
            
        if (value == null) {
            return 'e:1';    
        } else if(typeof value == 'number') {
            enc = 'n:' + value;
        } else if(typeof value == 'boolean') {
            enc = 'b:' + (value ? '1' : '0');
        } else if(Ext.isDate(value)) {
            enc = 'd:' + value.toGMTString();
        } else if(Ext.isArray(value)) {
            for (len = value.length; i < len; i++) {
                flat += this.encodeValue(value[i]);
                if (i != len - 1) {
                    flat += '^';
                }
            }
            enc = 'a:' + flat;
        } else if (typeof value == 'object') {
            for (key in value) {
                if (typeof value[key] != 'function' && value[key] !== undefined) {
                    flat += key + '=' + this.encodeValue(value[key]) + '^';
                }
            }
            enc = 'o:' + flat.substring(0, flat.length-1);
        } else {
            enc = 's:' + value;
        }
        return escape(enc);
    }
});
