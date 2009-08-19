/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * @namespace Ext.ux.form
 * @class Ext.ux.form.GridEditorComboBox
 * @extends Ext.form.ComboBox
 * Adoption of ComboBox as frequently used in edit grits
 */
Ext.ux.form.GridEditorComboBox = Ext.extend(Ext.form.ComboBox, {
    /**
     * @cfg {bool} blurOnSelect
     * blur comboBox on select
     */
    blurOnSelect: true,
     /**
      * @cfg {bool} expandOnFocus
      * Expand list on focus
      */
    expandOnFocus: true,
    
    
    typeAhead     : false,
    triggerAction : 'all',
    //lazyRender    : true,
    editable      : false,
    mode          : 'local',
    value         : null,
    forceSelection: true,
                
    initComponent: function(){
        this.supr().initComponent.call(this);
        
        if (this.expandOnFocus) {
            this.lazyInit = false;
            this.on('focus', function(){
                this.onTriggerClick();
            });
        }
        
        if (this.blurOnSelect){
            this.on('select', function(){
                this.blur(true);
                this.fireEvent('blur', this);
            }, this);
        }
     }
});