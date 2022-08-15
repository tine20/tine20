/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.GDPR.Addressbook');

// NOTE: Contact model is still defined manually
Tine.Addressbook.Model.Contact.prototype.fields.add(new Ext.data.Field(
    {name: 'GDPR_DataIntendedPurposeRecord', label: 'GDPR Intended Purpose', group: 'GDPR', omitDuplicateResolving: true } // i18n._('GDPR Intended Purpose')
));

Tine.Addressbook.Model.Contact.prototype.fields.add(new Ext.data.Field(
    {name: 'GDPR_Blacklist', label: 'GDPR Blacklisted', group: 'GDPR' } // i18n._('GDPR Blacklisted')
));

Tine.Addressbook.Model.Contact.prototype.fields.add(new Ext.data.Field(
    {name: 'GDPR_DataProvenance', label: 'GDPR Data Provenance', group: 'GDPR' }, // i18n._('GDPR Data Provenance')
));

Tine.Addressbook.Model.Contact.prototype.fields.add(new Ext.data.Field(
    {name: 'GDPR_DataEditingReason', label: 'GDPR Data Editing Reason', group: 'GDPR' }, // i18n._('GDPR Data Editing Reason')
));

Tine.Addressbook.Model.Contact.prototype.fields.add(new Ext.data.Field(
    {name: 'GDPR_DataExpiryDate', label: 'GDPR Data Expiry Date', group: 'GDPR' }, // i18n._('GDPR Data Expiry Date')
));

// Tine.Addressbook.contactBackend is created on code include time
delete Tine.Addressbook.contactBackend.getReader().ef;
Tine.Addressbook.contactBackend.getReader().buildExtractors();

Tine.widgets.grid.FilterRegistry.register('Addressbook', 'Contact', {
    filtertype: 'foreignrecord',
    foreignRecordClass: 'GDPR.DataIntendedPurposeRecord',
    foreignRefIdField: 'intendedPurpose',
    linkType: 'foreignId',
    filterName: 'GDPRDataIntendedPurposeFilter',
    ownField: 'GDPR_DataIntendedPurposeRecord',
    // i18n._('GDPR Intended Purpose')
    label: 'GDPR Intended Purpose'
});

Tine.widgets.grid.FilterRegistry.register('Addressbook', 'Contact', {
    valueType: 'bool',
    filterName: 'GDPRDataBlacklist',
    field: 'GDPR_Blacklist',
    // i18n._('GDPR Blacklisted')
    label: 'GDPR Blacklisted'
});
