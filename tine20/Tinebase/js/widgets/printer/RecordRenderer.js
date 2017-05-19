/*
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.widgets.printer');

/**
 * @namespace   Tine.widgets.printer
 * @class       Tine.widgets.printer.RecordRenderer
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */
Tine.widgets.printer.fieldRenderer = function(appName, record, fieldValue, label){
    var html = '',
        app = Tine.Tinebase.appMgr.get(appName),
        value = fieldValue ? fieldValue : '';

    html = '<span class="rp-print-single-label">' + app.i18n._(label)
        + '</span><span class="rp-print-single-value">' + value + '</span>';


    return html;
};

Tine.widgets.printer.headerRenderer = function() {
    return  '<div class="rp-print-single-logo"><img src="' + Tine.logo + '"></div>';
}
