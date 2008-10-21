/*
 * Tine 2.0
 * 
 * @package     mobileClient
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Ext.ux');

/**
 * Ext.ux.tbarTitle
 * A simple plugin which displays a title into the panels tbar spacer
 * 
 * @cfg {String} tbarTitle
 * @plugin Ext.Panel
 */
Ext.ux.tbarTitle = {
    init: function init(panel) {
        panel.on('render', function(panel) {
            var tbarEl = Ext.get(panel.tbar);
            this.tbarTitleEl = tbarEl.insertFirst({
                tag: 'div',
                html: this.tbarTitle
            });
            this.tbarTitleEl.addClass('x-tbar-title');
        });
        
        panel.on('resize', function(panel) {
            var titleSize = Ext.util.TextMetrics.createInstance(this.tbarTitleEl, true).getSize(this.tbarTitle);
            var tbarSize  = Ext.get(panel.tbar).getSize();
            
            this.tbarTitleEl.setLeft(tbarSize.width/2 - titleSize.width/2);
            this.tbarTitleEl.setTop(tbarSize.height/2 - titleSize.height/2);
        });
    }
}

