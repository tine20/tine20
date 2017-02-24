/*
 * Tine 2.0
 *
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * auto add selection checkbox for touch devices
 */
if (Ext.isTouchDevice) {
    Ext.grid.GridPanel.prototype.initComponent = Ext.grid.GridPanel.prototype.initComponent.createSequence(function() {
        var cm = this.getColumnModel(),
            sm = this.getSelectionModel(),
            cols = cm.columns || cm.config || [];

        cols.unshift(sm);

        this.enableDragDrop = false;
        this.enableDrag = false;
    });

    Ext.grid.RowSelectionModel.prototype.initEvents = Ext.grid.RowSelectionModel.prototype.initEvents.createSequence(function() {
        this.grid.on('render', function() {
            var view = this.grid.getView();
            view.mainBody.on('mousedown', this.onMouseDown, this);
            Ext.fly(view.innerHd).on('mousedown', this.onHdMouseDown, this);
        }, this);
    });

    Ext.each(['onMouseDown', 'onHdMouseDown', 'renderer', 'header', 'width', 'sortable', 'menuDisabled',
        'fixed', 'dataIndex', 'id'], function(p) {
        Ext.grid.RowSelectionModel.prototype[p] = Ext.grid.CheckboxSelectionModel.prototype[p];
    });
}