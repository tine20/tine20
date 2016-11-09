/*
 * Tine 2.0
 * 
 * @package     Events
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Events');

/**
 * @namespace Tine.Calendar
 * @class Tine.Events.CalendarEventEditDialog
 * @extends Tine.Calendar.CalendarEventEditDialog
 * Calendar Edit Dialog <br>
 * 
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
Tine.Events.CalendarEventEditDialog = Ext.extend(Tine.Calendar.EventEditDialog, {

    /**
     * @private
     */
    onRender : function(ct, position){
        Tine.Events.CalendarEventEditDialog.superclass.onRender.call(this, ct, position);

        // TODO add more fields
        Ext.each(['container_id'], function(field) {
            this.getForm().findField(field).setReadOnly(true);
        }, this);
    }
});

/**
 * Opens a new event edit dialog window
 *
 * @return {Ext.ux.Window}
 */
Tine.Events.CalendarEventEditDialog.openWindow = function (config) {
    // record is JSON encoded here...
    var id = config.recordId ? config.recordId : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 505,
        name: Tine.Events.CalendarEventEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Events.CalendarEventEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
