/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @todo        check if we need this
 */
 
Ext.ns('Tine.Voipmanager');

// default mainscreen
Tine.Voipmanager.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    
    activeContentType: 'Phone',
    activeContentGroup: 'Snom',

    /**
     * returns active content type
     * 
     * @return {String}
     */
    getActiveContentType: function() {
        var ac = (this.activeContentType) ? this.activeContentType : '';
        var ag = (this.activeContentGroup) ? this.activeContentGroup : '';
        return ag + ac;
    },    
    
    showCenterPanel: function() {
        
        // which content panel?
        var type = this.activeContentType;
        var group = this.activeContentGroup;
        
        //console.log(group +  '/' + type);
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
    showNorthPanel: function() {
        var type = this.activeContentType;
        var group = this.activeContentGroup;
              
        if (! this[group + type + 'ActionToolbar']) {
            this[group + type + 'ActionToolbar'] = this[group + type + 'GridPanel'].actionToolbar;
        }
        
        Tine.Tinebase.MainScreen.setActiveToolbar(this[group + type + 'ActionToolbar'], true);
    },
    
    /**
     * overwrite default
     * @return Tine.Voipmanager.PhoneTreePanel
     */
    getWestPanel: function() {

        if(!this.westPanel) {
            this.westPanel = new Tine.Voipmanager.PhoneTreePanel({app: this.app});
        }
        return this.westPanel;
    }
});
