/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.Filemanager');

/**
 * @namespace   Tine.Filemanager
 * @class       Tine.Filemanager.Application
 * @extends     Tine.Tinebase.Application
 * 
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
Tine.Filemanager.Application = Ext.extend(Tine.Tinebase.Application, {
    
    hasMainScreen: true,
    
    /**
     * Get translated application title of this application /test
     * 
     * @return {String}
     */
    getTitle: function() {
        return this.i18n.gettext('Filemanager');
    }
});

/**
 * @namespace   Tine.Filemanager
 * @class       Tine.Filemanager.MainScreen
 * @extends     Tine.widgets.MainScreen
 * 
 * @author      Martin Jatho <m.jatho@metaways.de>
 */
Tine.Filemanager.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    
    activeContentType: 'Node'
});

    
/**
 * @namespace   Tine.Filemanager
 * @class       Tine.Filemanager.TreePanel
 * @extends     Tine.widgets.container.TreePanel
 * 
 * @author      Martin Jatho <m.jatho@metaways.de>
 */
Tine.Filemanager.TreePanel = Ext.extend(Tine.widgets.container.TreePanel, {
    filterMode: 'filterToolbar',
    recordClass: Tine.Filemanager.Model.Node,
    plugins: [{
        ptype: 'ux.browseplugin',
        multiple: true,
        handler: function() {alert("tree drop");}
    }]
});
