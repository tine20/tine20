/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

/**
 * FreeTime grid panel
 * 
 * @namespace   Tine.HumanResources
 * @class       Tine.HumanResources.SicknessGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>FreeTime Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>    
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.HumanResources.SicknessGridPanel
 */
Tine.HumanResources.SicknessGridPanel = Ext.extend(Tine.HumanResources.FreeTimeGridPanel, {
    /**
     * record class
     * @cfg {Tine.HumanResources.Model.Sickness} recordClass
     */
    recordClass: Tine.HumanResources.Model.Sickness,
    recordProxy: Tine.HumanResources.sicknessBackend,
    newRecordIcon: 'HumanResourcesSickness',
    /**
     * inits this cmp
     * @private
     */
    initComponent: function() {
        Tine.HumanResources.SicknessGridPanel.superclass.initComponent.call(this);
    }
});
