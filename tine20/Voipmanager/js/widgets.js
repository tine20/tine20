/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @baseVersion Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: widgets.js 2509 2008-07-09 17:44:50Z twadewitz $
 *
 */
 
Ext.namespace('Tine.Voipmanager', 'Tine.Voipmanager.widgets');

Tine.Voipmanager.widgets.ContextCombo = Ext.extend(Ext.ux.form.ClearableComboBox, {
    
    id: 'ContextCombo',
    emptyText: 'context',
    typeAhead: true,
    editable: false,
    mode: 'remote',
    triggerAction: 'all',
    displayField:'name',
    valueField:'id',
    width: 100,
    
    /**
     * @private
     */
    initComponent: function() {
        this.store = new Ext.data.JsonStore({
            id: 'id',
            root: 'results',
            totalProperty: 'totalCount',
            fields: ['id','name','description'],
            baseParams: {
                method: 'Voipmanager.getAsteriskContexts',
                sort: '',
                dir: 'ASC',
                query: ''
            }
        });
        Tine.Voipmanager.widgets.ContextCombo.superclass.initComponent.call(this);
        
        this.on('select', function(){
            var v = this.getValue();
            if(String(v) !== String(this.startValue)){
                this.fireEvent('change', this, v, this.startValue);
            }
        }, this);
    }
});