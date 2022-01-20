/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Tinebase');

/**
 * MunicipalityKeyGridPanel
 * 
 * @namespace   Tine.Tinebase
 * @class       Tine.Tinebase.MunicipalityKeyGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>MunicipalityKey Grid Panel</p>
 *
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Tinebase.MunicipalityKeyGridPanel
 */
Tine.Tinebase.MunicipalityKeyGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    recordClass: Tine.Tinebase.Model.MunicipalityKey,
    copyEditAction: false,
    multipleEdit: false,
    moveAction: false,
    duplicateResolvable: false,

    /**
     * @cfg {Bool} hasDetailsPanel
     */
    hasDetailsPanel: false,

    /**
     * inits this cmp
     * @private
     */
    initComponent: function () {

        Tine.Tinebase.MunicipalityKeyGridPanel.superclass.initComponent.call(this);
    },
    
    initActions: function() {
        Tine.Tinebase.MunicipalityKeyGridPanel.superclass.initActions.apply(this, arguments);
        this.action_addInNewWindow.hide();
        this.action_editInNewWindow.hide();
        this.action_deleteRecord.hide();
        this.actions_import.hide();
    },
});
