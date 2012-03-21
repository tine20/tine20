/*
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * NOTE: init.js is included before the tine2.0 code!
 */
 
/** --------------------- Ultra Generic JavaScript Stuff --------------------- **/

/**
 * create console pseudo object when firebug is disabled/not installed
 */
if (! window.console) window.console = {};
for (fn in {
        // maximum possible console functions based on firebug
        log: null , debug: null, info: null, warn: null, error: null, assert: null, dir: null, dirxml: null, group: null,
        groupEnd: null, time: null, timeEnd: null, count: null, trace: null, profile: null, profileEnd: null
    }) {
    window.console[fn] = window.console[fn] || function() {};
}

/** -------------------- Extjs Framework Initialisation -------------------- **/

/**
 * don't fill the ext stats
 */
Ext.BLANK_IMAGE_URL = "library/ExtJS/resources/images/default/s.gif";

/**
 * use empty image as secure url
 */
Ext.SSL_SECURE_URL = "library/ExtJS/resources/images/default/s.gif";

/**
 * don't fill yahoo stats
 */
Ext.chart.Chart.CHART_URL = 'library/ExtJS/resources/charts.swf';

/**
 * use native json implementation because we had problems with utf8 linebreaks (\u2028 for example)
 * @see http://www.tine20.org/bugtracker/view.php?id=3356
 * @note IE is principally capable to use native json, but for *some reason* it's not working properly
 *       so we don't use it for IE
 * @type Boolean
 */
Ext.USE_NATIVE_JSON = !Ext.isIE;

/**
 * init ext quick tips
 */
Ext.QuickTips.init();

/**
 * html encode all grid columns per default and convert spaces to &nbsp;
 */
Ext.grid.ColumnModel.defaultRenderer = Ext.util.Format.htmlEncode;
Ext.grid.Column.prototype.renderer = function(value) {
    var result = Ext.util.Format.htmlEncode(value);
    return result;
};

Ext.apply(Ext.data.JsonStore.prototype, {
    url:  'index.php',
    root: 'results',
    idProperty: 'id',
    totalProperty: 'totalcount'
});

/**
 * add more options to Ext.form.ComboBox
 */
Ext.form.ComboBox.prototype.initComponent = Ext.form.ComboBox.prototype.initComponent.createSequence(function() {
    if (this.expandOnFocus) {
        this.lazyInit = false;
        this.on('focus', function(){
            this.onTriggerClick();
        });
    }
    
    if (this.blurOnSelect){
        this.on('select', function(){
            this.blur(true);
            this.fireEvent('blur', this);
        }, this);
    }
});
Ext.form.ComboBox.prototype.triggerAction = 'all';


/**
 * additional date patterns
 * @see{Date}
 */
Date.patterns = {
    ISO8601Long:"Y-m-d H:i:s",
    ISO8601Short:"Y-m-d",
    ISO8601Time:"H:i:s",
    ShortDate: "n/j/Y",
    LongDate: "l, F d, Y",
    FullDateTime: "l, F d, Y g:i:s A",
    MonthDay: "F d",
    ShortTime: "g:i A",
    LongTime: "g:i:s A",
    SortableDateTime: "Y-m-d\\TH:i:s",
    UniversalSortableDateTime: "Y-m-d H:i:sO",
    YearMonth: "F, Y"
};

Ext.util.JSON.encodeDate = function(o){
    var pad = function(n) {
        return n < 10 ? "0" + n : n;
    };
    return '"' + o.getFullYear() + "-" +
        pad(o.getMonth() + 1) + "-" +
        pad(o.getDate()) + " " +
        pad(o.getHours()) + ":" +
        pad(o.getMinutes()) + ":" +
        pad(o.getSeconds()) + '"';
};

Date.prototype.toJSON = function(key) {
    var pad = function(n) {
        return n < 10 ? "0" + n : n;
    };
    return this.getFullYear() + "-" +
        pad(this.getMonth() + 1) + "-" +
        pad(this.getDate()) + " " +
        pad(this.getHours()) + ":" +
        pad(this.getMinutes()) + ":" +
        pad(this.getSeconds());
};

/**
 * additional formats
 */
Ext.util.Format = Ext.apply(Ext.util.Format, {
    euMoney: function(v){
        v.toString().replace(/,/, '.');
        
        v = (Math.round(parseFloat(v)*100))/100;
        
        v = (v == Math.floor(v)) ? v + ".00" : ((v*10 == Math.floor(v*10)) ? v + "0" : v);
        v = String(v);
        var ps = v.split('.');
        var whole = ps[0];
        var sub = ps[1] ? '.'+ ps[1] : '.00';
        var r = /(\d+)(\d{3})/;
        while (r.test(whole)) {
            whole = whole.replace(r, '$1' + '.' + '$2');
        }
        v = whole + sub;
        if(v.charAt(0) == '-'){
            return v.substr(1) + ' -€';
        }  
        return v + " €";
    },
    percentage: function(v){
        if(v === null) {
            return 'none';
        }
        if(!isNaN(v)) {
            return v + " %";
        } 
    },
    pad: function(v,l,s){
        if (!s) {
            s = '&nbsp;';
        }
        var plen = l-v.length;
        for (var i=0;i<plen;i++) {
            v += s;
        }
        return v;
   }
});
