/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Tine.Tinebase.appMgr.isInitialised('Sales').then(() => {
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
            text: config.text || formatMessage('Create { targetType }', { targetType: targetRecordClass.getRecordName() }),
            iconCls: `SalesDocument_${targetType}`,
            actionUpdater(action, grants, records, isFilterSelect, filteredContainers) {
                let enabled = records.length
                    // && records[0].get()
                action.setDisabled(!enabled)
                action.baseAction.setDisabled(!enabled) // WTF?
            },
            async handler() {
                const selections = this.initialConfig.selections
                const statusFieldName = `${sourceType.toLowerCase()}_status`
                const statusDef = Tine.Tinebase.widgets.keyfield.getDefinitionFromMC(sourceRecordClass, statusFieldName)
                const unbooked = selections.reduce((unbooked, record) => {
                    const status = record.get(statusFieldName)
                    return unbooked.concat(statusDef.records.find((r) => { return r.id === status })?.booked ? [] : [record])
                }, [])

                if (unbooked.length) {
                    if (await Ext.MessageBox.confirm(
                        formatMessage('Book unbooked { sourceTypes }', { sourceTypes: sourceRecordClass.getRecordsName() }),
                        formatMessage('Creating followup { targetTypes } is allowed for booked { sourceTypes } only. Book selected { sourceTypes } now?', { sourceTypes: sourceRecordClass.getRecordsName(), targetTypes: targetRecordClass.getRecordsName() })
                    ) !== 'yes') { return false }

                    // @TODO: maybe we should define default booked state somehow? e.g. offer should be accepted (not only send) or let the user select?
                    const bookedState = statusDef.records.find((r) => { return r.booked })
                    // const proxy = sourceRecordClass.getProxy()
                    // @TODO saveMask in grid/dlg but how to find grid/editDialog??
                    await unbooked.asyncForEach(async (record) => {
                        // note: autoSave by grid recordProxy @todo: kill it, it might trow exceptions we need to fetch them
                        record.set(statusFieldName, bookedState.id)
                        // selections.splice(selections.indexOf(record), 1, await proxy.promiseSaveRecord(record))
                    })
                }

                // @TODO saveMask again

                // @TODO: have all docs into one vs. each doc indiviual
                await selections.asyncForEach(async (record) => {
                    Tine.Sales.createFollowupDocument({
                        sourceDocuments: [{sourceDocumentModel: sourceRecordClass.getPhpClassName(), sourceDocument: record.id}],
                        targetDocumentType: targetRecordClass.getPhpClassName()
                    })
                })

                // @TODO how do i know that a followup already exists? // do i get an exception?
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
            Ext.ux.ItemRegistry.registerItem(`Sales-Document_${sourceType}-GridPanel-ContextMenu`, action, 2)
            Ext.ux.ItemRegistry.registerItem(`Sales-Document_${sourceType}-GridPanel-ActionToolbar-leftbtngrp`, Ext.apply(new Ext.Button(action), {
                scale: 'medium',
                rowspan: 2,
                iconAlign: 'top'
            }), 30)
        })
    })

})

