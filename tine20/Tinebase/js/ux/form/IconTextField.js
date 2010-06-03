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
 * Class for creating text-fields with an icon in front of the label.
 * <p>Example usage:</p>
 * <pre><code>
 var field =  new Ext.ux.form.IconTextField({
     labelIcon: 'images/oxygen/16x16/actions/mail.png'
     fieldLabel: 'email',
     name: 'email',
 });
 * </code></pre>
 * 
 * @namespace   Ext.ux.form
 * @class       Ext.ux.form.IconTextField
 * @extends     Ext.form.TextField
 */
Ext.ux.form.IconTextField = Ext.extend(Ext.form.TextField, {
    /**
     * @cfg {String} LabelIcon icon to be displayed in front of the label
     */
    labelIcon: '',
    /**
     * @private
     */
    initComponent: function(){
         Ext.ux.form.IconTextField.superclass.initComponent.call(this);
         if (this.labelIcon.length > 0){
            this.fieldLabel = '<img src="' + this.labelIcon + '" class="x-ux-form-icontextfield-labelicon">' + this.fieldLabel;
         }
    }
});
Ext.reg('icontextfield', Ext.ux.form.IconTextField);