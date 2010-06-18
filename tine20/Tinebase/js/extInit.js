/*
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
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

/** ------------------------- Gears Initialisation ------------------------- **/
if (window.google && google.gears) {
    var permission = google.gears.factory.getPermission('Tine 2.0', 'images/oxygen/32x32/actions/dialog-information.png', 'Tine 2.0 detected that gears is installed on your computer. Permitting Tine 2.0 to store information on your computer, will increase speed of the software.');
    if (permission) {
        try {
            google.gears.localServer = google.gears.factory.create('beta.localserver');
            google.gears.localServer.store = google.gears.localServer.createManagedStore('tine20-store');
            google.gears.localServer.store.manifestUrl = 'Tinebase/js/tine20-manifest.js';
            google.gears.localServer.store.checkForUpdate();
            
            if (google.gears.localServer.store.updateStatus == 3) {
                console.info('gears localserver store failure: ' + google.gears.localServer.store.lastErrorMessage);
                google.gears.localServer.removeManagedStore('tine20-store');
            }
        } catch (e) {
            console.info("can't initialize gears: " + e);
        }
    }
}

/** -------------------- Extjs Framework Initialisation -------------------- **/

/**
 * don't fill the ext stats
 */
Ext.BLANK_IMAGE_URL = "library/ExtJS/resources/images/default/s.gif";

/**
 * don't fill yahoo stats
 */
Ext.chart.Chart.CHART_URL = 'library/ExtJS/resources/charts.swf';

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

/**
 * addidional formats
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