/*
 * Tine 2.0
 * 
 * @package     Ext
 * @subpackage  ux
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
Ext.ns('Ext.ux', 'Ext.ux.form');

/**
 * Class for creating equal text-fields multiple times in a form.
 * If a value gets changed in one of the fields, all other will be updated
 * <p>Example usage:</p>
 * <pre><code>
 var dialog =  new Ext.Panel({
     layout: 'form',
     items: [
         {
             fieldLabel: 'First occurrence',
             xtype: 'mirrortextfield',
             name: 'themirrorfiled'
         },{
             fieldLabel: 'Normal field',
             xtype: 'textfield',
             name: 'someotherfield'
         },{
             fieldLabel: 'Second occurrence',
             xtype: 'mirrortextfield',
             name: 'themirrorfiled'
         },
     ]
 });
 * </code></pre>
 * 
 * @namespace   Ext.ux.form
 * @class       Ext.ux.form.MirrorTextField
 * @extends     Ext.ux.form.IconTextField
 */
Ext.ux.form.MirrorTextField = Ext.extend(Ext.ux.form.IconTextField, {
    /**
     * @private
     */
    initComponent: function(){
         Ext.ux.form.MirrorTextField.superclass.initComponent.call(this);
         Ext.ux.form.MirrorTextFieldManager.register(this);
    },
    /**
     * @private
     */
    setValue: function(value){
        Ext.ux.form.MirrorTextFieldManager.setAll(this, value);
    },
    /**
     * @private
     */
    onDestroy : function(){
        if(this.rendered){
            Ext.ux.form.MirrorTextFieldManager.unregister(this);
        }
    }
});
Ext.reg('mirrortextfield', Ext.ux.form.MirrorTextField);

/**
 * Helper for Ext.ux.form.MirrorTextField
 * @singleton
 */
Ext.ux.form.MirrorTextFieldManager = function() {
    var MirrorTextFields = {};
    
    function MirrorField(field, newValue, oldValue) {
        var m = MirrorTextFields[field.name];
        for(var i = 0, l = m.length; i < l; i++){
            m[i].setRawValue(newValue);
        }
        return true;
    }
    
    return {
        register: function(field) {
            var m = MirrorTextFields[field.name];
            if(!m){
                m = MirrorTextFields[field.name] = [];
            }
            m.push(field);
            field.on("change", MirrorField);
        },
        
        unregister: function(field) {
            var m = MirrorTextFields[field.name];
            if(m){
                m.remove(field);
                field.un("change", MirrorField);
            }
        },
        
        setAll: function(field, value) {
            var m = MirrorTextFields[field.name];
            if(m){
                MirrorField(field, value);
            }
        }
    };
}();