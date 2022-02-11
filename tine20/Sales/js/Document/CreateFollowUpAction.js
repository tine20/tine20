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
            Order: {}
        },
        Order: {
            Delivery: {},
            Invoice: {},
            // reversals?
        },
        // Delivery: {Delivery: {}}, // collection, reversal
    }

    const getFollowUpAction = (sourceType, targetType, config) => {
        const sourceRecordClass = Tine.Tinebase.data.RecordMgr.get(`Sales.Document_${sourceType}`)
        const targetRecordClass = Tine.Tinebase.data.RecordMgr.get(`Sales.Document_${targetType}`)
        return new Ext.Action(Object.assign({
            text: config.text || app.formatMessage('Create { targetType }', { targetType: targetRecordClass.getRecordName() }),
            iconCls: `SalesDocument_${targetType}`,
            actionUpdater(action, grants, records, isFilterSelect, filteredContainers) {
                let enabled = records.length
                    // && records[0].get()
                action.setDisabled(!enabled)
                action.baseAction.setDisabled(!enabled) // WTF?
            },
            async handler(cmp) {
                const selections = [...this.initialConfig.selections]
                const errorMsgs = []
                // const grid = Tine.widgets.grid.GridPanel.getByChildCmp(cmp)
                // const editDialog = grid ? null : cmp.findParentBy((c) => {return c instanceof Tine.widgets.dialog.EditDialog})
                const maskEl = cmp.findParentBy((c) => {return c instanceof Tine.widgets.dialog.EditDialog || c instanceof Tine.widgets.MainScreen }).getEl()
                const mask = new Ext.LoadMask(maskEl, { msg: app.formatMessage('Creating { targetTypes })', { targetTypes: targetRecordClass.getRecordsName() }) })

                const statusFieldName = `${sourceType.toLowerCase()}_status`
                const statusDef = Tine.Tinebase.widgets.keyfield.getDefinitionFromMC(sourceRecordClass, statusFieldName)
                const unbooked = selections.reduce((unbooked, record) => {
                    const status = record.get(statusFieldName)
                    return unbooked.concat(statusDef.records.find((r) => { return r.id === status })?.booked ? [] : [record])
                }, [])

                if (unbooked.length) {
                    if (await Ext.MessageBox.confirm(
                        app.formatMessage('Book unbooked { sourceTypes }', { sourceTypes: sourceRecordClass.getRecordsName() }),
                        app.formatMessage('Creating followup { targetTypes } is allowed for booked { sourceTypes } only. Book selected { sourceTypes } now?', { sourceTypes: sourceRecordClass.getRecordsName(), targetTypes: targetRecordClass.getRecordsName() })
                    ) !== 'yes') { return false }

                    // @TODO: maybe we should define default booked state somehow? e.g. offer should be accepted (not only send) or let the user select?
                    const bookedState = statusDef.records.find((r) => { return r.booked })
                    const proxy = sourceRecordClass.getProxy()
                    mask.show()
                    await unbooked.asyncForEach(async (record) => {
                        record.noProxy = true // kill grid autoSave
                        record.set(statusFieldName, bookedState.id)
                        let updatedRecord
                        try {
                            updatedRecord = await proxy.promiseSaveRecord(record)
                        } catch (e) {
                            record.reject()
                            errorMsgs.push(app.formatMessage('Cannot book { sourceDocument }: ({e.code}) { e.message }', { sourceDocument: record.getTitle(), e }))
                        }
                        selections.splice.apply(selections, [selections.indexOf(record), 1].concat(updatedRecord ? [updatedRecord] : []))
                    })
                }

                mask.show()
                // @TODO: have all docs into one followUp vs. each doc gets an individual followUp
                await selections.asyncForEach(async (record) => {
                    try {
                        Tine.Sales.createFollowupDocument({
                            sourceDocuments: [{sourceDocumentModel: sourceRecordClass.getPhpClassName(), sourceDocument: record.id}],
                            targetDocumentType: targetRecordClass.getPhpClassName()
                        })
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
                // @TODO if single record, open followup or show btn to open it

            }
            // split: true,
            // iconCls: 'action_follwup',
            // menu: allowedTransitions[type].map((followUpType) => {
            //     const recordClass = Tine.Tinebase.data.RecordMgr.get(`Sales.Document_${followUpType}`)
            //     return {
            //         text: recordClass.getRecordName(),
            //         iconCls: `SalesDocument_${followUpType}`,
            //     }
            // })
        }, config))
    }

    Object.keys(allowedTransitions).forEach((sourceType) => {
        // const startPos = 30
        Object.keys(allowedTransitions[sourceType]).forEach((targetType) => {
            const action = getFollowUpAction(sourceType, targetType, allowedTransitions[sourceType][targetType])
            const medBtnStyle = { scale: 'medium', rowspan: 2, iconAlign: 'top'}
            Ext.ux.ItemRegistry.registerItem(`Sales-Document_${sourceType}-GridPanel-ContextMenu`, action, 2)
            Ext.ux.ItemRegistry.registerItem(`Sales-Document_${sourceType}-GridPanel-ActionToolbar-leftbtngrp`, Ext.apply(new Ext.Button(action), medBtnStyle), 30)
            Ext.ux.ItemRegistry.registerItem(`Sales-Document_${sourceType}-editDialog-Toolbar`, Ext.apply(new Ext.Button(action), medBtnStyle), 10)
        })
    })

})

