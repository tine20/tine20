/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

/**
 * Employee grid panel
 * 
 * @namespace   Tine.HumanResources
 * @class       Tine.HumanResources.EmployeeGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Employee Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>    
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.HumanResources.EmployeeGridPanel
 */
Tine.HumanResources.EmployeeGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    evalGrants: false,
    renderSalutation: function(value, row, record) {
            if (! this.salutationRenderer) {
                this.salutationRenderer = Tine.Tinebase.widgets.keyfield.Renderer.get('Addressbook', 'contactSalutation');
            }
            return this.salutationRenderer(value, row, record);
    }
});

Tine.widgets.grid.RendererManager.register('HumanResources', 'Employee', 'salutation', Tine.HumanResources.EmployeeGridPanel.prototype.renderSalutation);

