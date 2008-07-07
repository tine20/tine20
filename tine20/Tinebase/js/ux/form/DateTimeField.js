/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Ext.ux', 'Ext.ux.form');

/**
 * A DateTimeField
 * This class simplifies i18n a lot
 */
Ext.ux.form.DateField = Ext.extend(Ext.form.DateField, {
    /**
     * @cfg {string} format @see{Ext.util.Format}
     */
});
Ext.reg('datetimefield', Ext.ux.form.DateField);