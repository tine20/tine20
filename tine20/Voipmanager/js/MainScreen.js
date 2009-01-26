/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:$
 *
 */
 
Ext.ns('Tine.Voipmanager');

// default mainscreen
Tine.Voipmanager.MainScreen = Ext.extend(Tine.Tinebase.widgets.app.MainScreen, {
    
    activeContentType: 'Context',
    activeContentGroup: 'Asterisk',
    
    
    show: function() {
        if(this.fireEvent("beforeshow", this) !== false){
            this.setTreePanel();
            this.setContentPanel();
            this.setToolbar();
            this.updateMainToolbar();
            
            this.fireEvent('show', this);
        }
        return this;
    },
    
    
    
    
    setContentPanel: function() {
        
        // which content panel?
        var type = this.activeContentType;
        var group = this.activeContentGroup;
		
console.log(new Tine[this.app.appName][group + type + 'GridPanel']({
                app: this.app
            }));		
		

         if (! this[group + type  + 'GridPanel']) {        
            this[group + type + 'GridPanel'] = new Tine[this.app.appName][group + type + 'GridPanel']({
                app: this.app
            });
           
        }

        
        Tine.Tinebase.MainScreen.setActiveContentPanel(this[group + type + 'GridPanel'], true);
        this[group + type + 'GridPanel'].store.load();
    },
    
    /**
     * sets toolbar in mainscreen
     */
    setToolbar: function() {
        var type = this.activeContentType;
        var group = this.activeContentGroup;
        
        if (! this[group + type + 'ActionToolbar']) {
            this[group + type + 'ActionToolbar'] = this[group + type + 'GridPanel'].actionToolbar;
        }
        
        Tine.Tinebase.MainScreen.setActiveToolbar(this[group + type + 'ActionToolbar'], true);
    }
});
