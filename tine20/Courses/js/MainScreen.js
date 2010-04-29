/**
 * Tine 2.0
 * 
 * @package     Courses
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:MainScreen.js 7169 2009-03-05 10:37:38Z p.schuele@metaways.de $
 *
 */
 
Ext.ns('Tine.Courses');

// default mainscreen
Tine.Courses.MainScreen = Ext.extend(Tine.Tinebase.widgets.app.MainScreen, {
    
    activeContentType: 'Course',
    
    showCenterPanel: function() {
        
        // which content panel?
        var type = this.activeContentType;
        
        if (! this[type + 'GridPanel']) {
            this[type + 'GridPanel'] = new Tine[this.app.appName][type + 'GridPanel']({
                app: this.app,
                plugins: [this.treePanel.getFilterPlugin()]
            });
            
        }
        
        Tine.Tinebase.MainScreen.setActiveContentPanel(this[type + 'GridPanel'], true);
        this[type + 'GridPanel'].store.load();
    },
    
    getContentPanel: function() {
        // which content panel?
        //var type = this.activeContentType;
        
        // we always return record grid panel as a quick hack for saving filters
        return this['Course' + 'GridPanel'];
    },
    
    /**
     * sets toolbar in mainscreen
     */
    showNorthPanel: function() {
        var type = this.activeContentType;
        
        if (! this[type + 'ActionToolbar']) {
            this[type + 'ActionToolbar'] = this[type + 'GridPanel'].actionToolbar;
        }
        
        Tine.Tinebase.MainScreen.setActiveToolbar(this[type + 'ActionToolbar'], true);
    }
});
