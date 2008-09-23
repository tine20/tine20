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
            var spacer = panel.tbar.child('div[class=ytb-spacer]');
            spacer.addClass('x-tbar-title');
            spacer.dom.innerHTML = this.tbarTitle;
        });
    }
}

