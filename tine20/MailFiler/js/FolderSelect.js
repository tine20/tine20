/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.MailFiler')

/**
 * Folder select ComboBox widget
 * 
 * @namespace   Tine.MailFiler
 * @class       Tine.MailFiler.folderSelectComboBox
 * @extends     Tine.widgets.container.SelectionComboBox
 *
 * TODO implement
 */
Tine.MailFiler.folderSelectComboBox = Ext.extend(Tine.widgets.container.SelectionComboBox, {
    /**
     * @cfg {Tine.data.Record} recordClass
     */
    recordClass: Tine.MailFiler.Model.Node
});

Ext.reg('foldertcombo', Tine.MailFiler.folderSelectComboBox);

