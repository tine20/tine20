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
 
/** --------------------- Ultra Geneirc Javacipt Stuff --------------------- **/

/**
 * create console pseudo object when firebug is disabled/not installed
 */
if (! ("console" in window) || !("firebug" in console)) {
    window.console = {
        log: null , debug: null, info: null, warn: null, error: null, assert: null, dir: null, dirxml: null, group: null,
        groupEnd: null, time: null, timeEnd: null, count: null, trace: null, profile: null, profileEnd: null
    };
    for (f in window.console) {
        window.console[f] = function() {};
    }
}

/** ------------------------- Gears Initialisation ------------------------- **/

if (window.google && google.gears) {
    google.gears.localServer = google.gears.factory.create('beta.localserver');
    google.gears.localServer.store = google.gears.localServer.createManagedStore('tine20-store');
    google.gears.localServer.store.manifestUrl = 'Tinebase/js/tine20-manifest.js';

    //google.gears.localServer.store.checkForUpdate();
    //console.log(google.gears.localServer.store.updateStatus);
    //console.log(google.gears.localServer.store.lastErrorMessage);
}

/** -------------------- Extjs Framework Initialisation -------------------- **/

/**
 * don't fill the ext stats
 */
Ext.BLANK_IMAGE_URL = "ExtJS/resources/images/default/s.gif";

/**
 * init ext quick tips
 */
Ext.QuickTips.init();

/**
 * additional date patterns
 * @see{Date}
 */
Date.patterns = {
    ISO8601Long:"Y-m-d H:i:s",
    ISO8601Short:"Y-m-d",
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
