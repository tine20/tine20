/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Promise.all([Tine.Tinebase.appMgr.isInitialised('Sales'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {
    const app = Tine.Tinebase.appMgr.get('Sales')

    // from -> to
    const allowedTransitions = {
        Offer: {
            Offer: {isReversal: true},
            Order: {}
        },
        Order: {
            Order: {isReversal: true},
            Delivery: {},
            Invoice: {},
        },
        Delivery: {
            Delivery: {isReversal: true},
        },
        Invoice: {
            Invoice: {isReversal: true},
        }
    }

    const getFollowUpAction = (sourceType, targetType, config) => {
        const isReversal = !!config.isReversal
        const sourceRecordClass = Tine.Tinebase.data.RecordMgr.get(`Sales.Document_${sourceType}`)
        if (!sourceRecordClass) return null
        const sourceRecordsName = sourceRecordClass.getRecordsName()
        const targetRecordClass = Tine.Tinebase.data.RecordMgr.get(`Sales.Document_${targetType}`)
        const targetRecordName = isReversal ? app.i18n._('Reversal') : targetRecordClass.getRecordName()
        const targetRecordsName = isReversal ? app.i18n._('Reversals') : targetRecordClass.getRecordsName()
        const sharedTransitionFlag = `shared_${targetRecordClass.getMeta('recordName').toLowerCase()}`
        const recipientField = `${targetRecordClass.getMeta('recordName').toLowerCase()}_recipient_id`
        const supportsSharedTransition = sourceRecordClass.hasField(sharedTransitionFlag)
        return new Ext.Action(Object.assign({
            text: config.text || app.formatMessage('Create { targetRecordName }', { targetRecordName }),
            iconCls: `SalesDocument_${targetType} ${isReversal ? 'SalesDocument_Reversal' : ''}`,
            actionUpdater(action, grants, records, isFilterSelect, filteredContainers) {
                let enabled = records.length

                if (isReversal) {
                    // reversals are allowed for booked documents only
                    const statusFieldName = `${sourceType.toLowerCase()}_status`
                    const statusDef = Tine.Tinebase.widgets.keyfield.getDefinitionFromMC(sourceRecordClass, statusFieldName)
                    enabled = records.reduce((enabled, record) => {
                        return enabled && _.find(statusDef.records, {id: record.get(statusFieldName) }).booked
                    }, enabled)
                }

                action.setDisabled(!enabled)
                action.baseAction.setDisabled(!enabled) // WTF?
            },
            async handler(cmp) {
                const selections = [...this.initialConfig.selections]
                const errorMsgs = []
                const editDialog = cmp.findParentBy((c) => {return c instanceof Tine.widgets.dialog.EditDialog})
                const maskEl = cmp.findParentBy((c) => {return c instanceof Tine.widgets.dialog.EditDialog || c instanceof Tine.widgets.MainScreen }).getEl()
                const mask = new Ext.LoadMask(maskEl, { msg: app.formatMessage('Creating { targetRecordsName }', { targetRecordsName }) })

                const statusFieldName = `${sourceType.toLowerCase()}_status`
                const statusDef = Tine.Tinebase.widgets.keyfield.getDefinitionFromMC(sourceRecordClass, statusFieldName)
                const unbooked = selections.reduce((unbooked, record) => {
                    const status = record.get(statusFieldName)
                    return unbooked.concat(statusDef.records.find((r) => { return r.id === status })?.booked ? [] : [record])
                }, [])

                if (unbooked.length) {
                    if (await Ext.MessageBox.confirm(
                        app.formatMessage('Book unbooked { sourceRecordsName }', { sourceRecordsName }),
                        app.formatMessage('Creating followup { targetRecordsName } is allowed for booked { sourceRecordsName } only. Book selected { sourceRecordsName } now?', { sourceRecordsName, targetRecordsName })
                    ) !== 'yes') { return false }

                    // @TODO: maybe we should define default booked state somehow? e.g. offer should be accepted (not only send) or let the user select?
                    const bookedState = statusDef.records.find((r) => { return r.booked })
                    mask.show()
                    await unbooked.asyncForEach(async (record) => {
                        record.noProxy = true // kill grid autoSave
                        record.set(statusFieldName, bookedState.id)
                        let updatedRecord
                        try {
                            updatedRecord = await sourceRecordClass.getProxy().promiseSaveRecord(record)
                            selections.splice.apply(selections, [selections.indexOf(record), 1].concat(updatedRecord ? [updatedRecord] : []))
                            editDialog ? await editDialog.loadRecord(updatedRecord) : null
                        } catch (e) {
                            record.reject()
                            errorMsgs.push(app.formatMessage('Cannot book { sourceDocument }: ({e.code}) { e.message }', { sourceDocument: record.getTitle(), e }))
                        }
                    })
                }

                await mask.show()

                if (editDialog && !unbooked.length && _.keys(selections[0].getChanges()).length) {
                    selections[0] = await sourceRecordClass.getProxy().promiseSaveRecord(selections[0])
                    await editDialog.loadRecord(selections[0])
                }

                const followUpDocuments = [];
                let processedSourceIds = [];
                // @TODO: have all docs into one followUp vs. each doc gets an individual followUp
                // allow 'ad-hoc' shared followups? -> no :-)
                // check if document is 'shared' -> getSharedOrderDocumentTransition
                // NOTE: the selection might contain other documents which are part of the shared followup
                //       those docs must not be processed individually
                //       unbooked documents are not included -> inform user about this?
                await selections.asyncForEach(async (record) => {
                    try {
                        if (processedSourceIds.indexOf(record.id) < 0) {
                            let transition = {
                                sourceDocuments: [{
                                    sourceDocumentModel: sourceRecordClass.getPhpClassName(),
                                    sourceDocument: record.id,
                                    isReversal
                                }],
                                targetDocumentType: targetRecordClass.getPhpClassName()
                            }

                            if (supportsSharedTransition && !!+record.get(sharedTransitionFlag)) {
                                // group by address?
                                const recipientId = _.get(record, `data.${recipientField}.original_id`)
                                const category = record.get('document_category')
                                transition = await Tine.Sales.getSharedOrderDocumentTransition(recipientId, category, transition.targetDocumentType)

                                if (! transition?.sourceDocuments?.length) {
                                    return await Ext.MessageBox.show({
                                        buttons: Ext.Msg.OK,
                                        icon: Ext.MessageBox.WARNING,
                                        title: app.formatMessage('Nothing to do'),
                                        msg: app.formatMessage('{ document } has no open positions left.', { document: record.getTitle() })
                                    });
                                }
                            }

                            if (transition.sourceDocuments.length > 1) {
                                transition.sourceDocuments = _.map(await Tine.widgets.dialog.MultiOptionsDialog.getOption({
                                    title: app.formatMessage('Choose { sourceRecordsName }', { sourceRecordsName }),
                                    questionText: app.formatMessage('Please choose which { sourceRecordsName } should be included in shared { targetRecordName }', { sourceRecordsName, targetRecordName}),
                                    allowMultiple: true,
                                    allowCancel: false,
                                    height: transition.sourceDocuments.length * 30 + 100,
                                    options: transition.sourceDocuments.map((source) => {
                                        const sourceDocument = Tine.Tinebase.data.Record.setFromJson(source.sourceDocument, sourceRecordClass)
                                        return { text: sourceDocument.getTitle(), name: sourceDocument.id, checked: true, source }
                                    })
                                }), 'source');
                            }

                            const followUpDocumentData = await Tine.Sales.createFollowupDocument(transition)
                            window.postal.publish({
                                channel: "recordchange",
                                topic: [app.appName, targetRecordClass.getMeta('modelName'), 'create'].join('.'),
                                data: followUpDocumentData
                            })
                            followUpDocuments.push(Tine.Tinebase.data.Record.setFromJson(followUpDocumentData, targetRecordClass))
                            processedSourceIds = processedSourceIds.concat(_.map(transition.sourceDocuments, 'sourceDocument.id'))
                        }
                    } catch (e) {
                        errorMsgs.push(app.formatMessage('Cannot create { targetType } from { sourceDocument }: ({e.code}) { e.message }', { sourceDocument: record.getTitle(), targetType: targetRecordClass.getRecordName(), e }))
                    }
                })

                mask.hide()
                if (errorMsgs.length) {
                    await Ext.MessageBox.show({
                        buttons: Ext.Msg.OK,
                        icon: Ext.MessageBox.WARNING,
                        title: app.formatMessage('There where Errors:'),
                        msg: errorMsgs.join('<br />')
                    });
                }

                if (followUpDocuments.length) {
                    await Ext.MessageBox.show({
                        buttons: Ext.Msg.OK,
                        icon: Ext.MessageBox.INFO,
                        title: app.formatMessage('Documents Created:'),
                        msg: followUpDocuments.map((document) => {
                            return `${targetRecordName}: <a href="#" data-record-class="${targetRecordClass.getPhpClassName()}" data-record-id="${document.id}">${document.getTitle()}</a>`
                        }).join('<br />')
                    });
                }

            }
        }, config))
    }

    Object.keys(allowedTransitions).forEach((sourceType) => {
        // const startPos = 30
        Object.keys(allowedTransitions[sourceType]).forEach((targetType) => {
            const action = getFollowUpAction(sourceType, targetType, allowedTransitions[sourceType][targetType])
            const medBtnStyle = { scale: 'medium', rowspan: 2, iconAlign: 'top'}
            Ext.ux.ItemRegistry.registerItem(`Sales-Document_${sourceType}-GridPanel-ContextMenu`, action, 2)
            Ext.ux.ItemRegistry.registerItem(`Sales-Document_${sourceType}-GridPanel-ActionToolbar-leftbtngrp`, Ext.apply(new Ext.Button(action), medBtnStyle), 30)
            Ext.ux.ItemRegistry.registerItem(`Sales-Document_${sourceType}-editDialog-Toolbar`, Ext.apply(new Ext.Button(action), medBtnStyle), 40)
        })
    })

})

