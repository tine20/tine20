/**
 * Tine 2.0
 * 
 * @package     Example
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine', 'Tine.Example');

/**
 * @namespace   Tine.Example
 * @class       Tine.Example.Application
 * @extends     Tine.Tinebase.Application
 * Example Application Object <br>
 * 
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 */
Tine.Example.Application = Ext.extend(Tine.Tinebase.Application, {
    
    /**
     * auto hook text _('New Example')
     */
    addButtonText: 'New Example'
});

// default mainscreen
Tine.Example.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    activeContentType: 'Example'
});

Tine.Example.ExampleTreePanel = function(config) {
    Ext.apply(this, config);
    
    this.id = 'ExampleTreePanel';
    this.recordClass = Tine.Example.Model.Example;
    
    this.filterMode = 'filterToolbar';
    Tine.Example.ExampleTreePanel.superclass.constructor.call(this);
};
Ext.extend(Tine.Example.ExampleTreePanel, Tine.widgets.container.TreePanel, {
    afterRender: function() {
        this.supr().afterRender.apply(this, arguments);
    }
});

Tine.Example.ExampleFilterPanel = function(config) {
    Ext.apply(this, config);
    Tine.Example.ExampleFilterPanel.superclass.constructor.call(this);
};
Ext.extend(Tine.Example.ExampleFilterPanel, Tine.widgets.persistentfilter.PickerPanel, {
    filter: [{field: 'model', operator: 'equals', value: 'Example_Model_ExampleFilter'}]
});
