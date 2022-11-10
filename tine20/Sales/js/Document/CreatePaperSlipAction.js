/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

// @see https://github.com/ericmorand/twing/issues/332
// #if process.env.NODE_ENV !== 'unittest'
import getTwingEnv from "twingEnv";
// #endif

Promise.all([Tine.Tinebase.appMgr.isInitialised('Sales'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {
    const app = Tine.Tinebase.appMgr.get('Sales')

    const getAction = (type, config) => {
        const recordClass = Tine.Tinebase.data.RecordMgr.get(`Sales.Document_${type}`)
        return new Ext.Action(Object.assign({
            text: config.text || app.formatMessage('Print Paper Slip'),
            iconCls: `action_print`,
            actionUpdater(action, grants, records, isFilterSelect, filteredContainers) {
                let enabled = records.length === 1
                action.setDisabled(!enabled)
                action.baseAction.setDisabled(!enabled) // WTF?
            },
            async handler(cmp) {
                let record = this.initialConfig.selections[0];
                let paperSlip; // @see contentPanelConstructorInterceptor

                const editDialog = cmp.findParentBy((c) => {return c instanceof Tine.widgets.dialog.EditDialog})
                const maskMsg = app.formatMessage('Creating {type} Paper Slip', { type: recordClass.getRecordName() })

                if (editDialog) {
                    try {
                        await editDialog.isValid()
                    } catch (e) {
                        return
                    }
                }

                const createPaperSlip = async (mask) => {
                    try {
                        mask.show()
                        record = editDialog ? Tine.Tinebase.data.Record.setFromJson(await editDialog.applyChanges(), recordClass) : record
                        record = Tine.Tinebase.data.Record.setFromJson(await Tine.Sales.createPaperSlip(recordClass.getPhpClassName(), record.id), recordClass)
                        editDialog ? await editDialog.loadRecord(record) : null
                        window.postal.publish({
                            channel: "recordchange",
                            topic: [app.appName, recordClass.getMeta('modelName'), 'update'].join('.'),
                            data: {...record.data}
                        });
                    } catch (e) {
                        await Ext.MessageBox.show({
                            buttons: Ext.Msg.OK,
                            icon: Ext.MessageBox.WARNING,
                            title: app.formatMessage('There where Errors:'),
                            msg: app.formatMessage('Cannot create { type } paper slip: ({e.code}) { e.message }', { type: record.getTitle(), e })
                        });
                    }
                    mask.hide()
                };

                const getMailAction = async (win, record) => {
                    const recipientData = _.get(record, 'data.recipient_id.data', _.get(record, 'data.recipient_id'));
                    paperSlip.attachment_type = 'attachment';

                    return new Ext.Button({
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top',
                        text: app.formatMessage('Send by E-Mail'),
                        iconCls: `action_composeEmail`,
                        disabled: !recipientData.email,
                        handler: () => {
                            const mailDefaults = win.Tine.Felamimail.Model.Message.getDefaultData();
                            const emailBoilerplate = _.find(record.get('boilerplates'), (bp) => { return bp.name === 'Email'});
                            let body = '';
                            if (emailBoilerplate) {
                                this.twingEnv = getTwingEnv();
                                const loader = this.twingEnv.getLoader();
                                loader.setTemplate(`${record.id}-email`, emailBoilerplate.boilerplate);
                                body = this.twingEnv.render(`${record.id}-email`, record.data);
                                if (mailDefaults.content_type === 'text/html') {
                                    body = Ext.util.Format.nl2br(body);
                                }
                            }

                            const mailRecord = new win.Tine.Felamimail.Model.Message(Object.assign(mailDefaults, {
                                subject: `${record.constructor.getRecordName()} ${record.get('document_number')}: ${record.get('document_title')}`,
                                // @TODO have a boilerplate here
                                body: body + win.Tine.Felamimail.getSignature(),
                                to: [`${recipientData.name} <${recipientData.email}>`],
                                attachments: [paperSlip]
                            }), 0);
                            win.Tine.Felamimail.MessageEditDialog.openWindow({
                                record: mailRecord,
                                // listeners: {
                                //     update: (mail) => {
                                //         const docType = editDialog.record.constructor.getMeta('recordName');
                                //         const currentStatus = editDialog.record.get(editDialog.statusFieldName);
                                //         let changeStatusTo = null;
                                //
                                //         if (docType === 'Invoice' && currentStatus === 'STATUS_BOOKED') {
                                //             changeStatusTo = 'SHIPPED';
                                //         } else if (docType === 'Offer' && currentStatus === 'DRAFT') {
                                //             // don't change status - might still be a draft!
                                //         }
                                //
                                //         editDialog.getForm().findField(editDialog.statusFieldName).set(changeStatusTo);
                                //
                                //         debugger
                                //     }
                                // }
                            });
                        },
                    });
                };

                if (Tine.OnlyOfficeIntegrator) {
                    Tine.OnlyOfficeIntegrator.OnlyOfficeEditDialog.openWindow({
                        id: record.id,
                        contentPanelConstructorInterceptor: async (config) => {
                            const isPopupWindow = config.window.popup
                            const win = isPopupWindow ? config.window.popup : window
                            const mainCardPanel = isPopupWindow ? win.Tine.Tinebase.viewport.tineViewportMaincardpanel : await config.window.afterIsRendered()
                            isPopupWindow ? mainCardPanel.get(0).hide() : null;

                            const mask = new win.Ext.LoadMask(mainCardPanel.el, { msg: maskMsg })
                            await createPaperSlip(mask)
                            const attachments = record.get('attachments')
                            const mdates = _.map(attachments, (attachment) => {return _.compact([attachment.last_modified_time, attachment.creation_time]).sort().pop()})
                            paperSlip = attachments[mdates.indexOf([...mdates].sort().pop())]
                            Object.assign(config, {
                                recordData: paperSlip,
                                id: paperSlip.id,
                                tbarItems: [await getMailAction(win, record)]
                            });
                        }
                    })
                } else {
                    const maskEl = cmp.findParentBy((c) => {return c instanceof Tine.widgets.dialog.EditDialog || c instanceof Tine.widgets.MainScreen }).getEl()
                    const mask = new Ext.LoadMask(maskEl, { msg: maskMsg })
                    await createPaperSlip(mask)
                    alert('OnlyOfficeIntegrator missing -> find paperSlip in attachments')
                }
            }

        }, config))
    }

    ['Offer', 'Order', 'Delivery', 'Invoice'].forEach((type) => {
        const action = getAction(type, {})
        const medBtnStyle = { scale: 'medium', rowspan: 2, iconAlign: 'top'}
        Ext.ux.ItemRegistry.registerItem(`Sales-Document_${type}-editDialog-Toolbar`, Ext.apply(new Ext.Button(action), medBtnStyle), 10)
    })
})

