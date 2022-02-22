const documentNumberRenderer = (value, metaData, record, rowIndex, colIndex, store) => {
    return value ? value : record.get('document_proforma_number') || ''
}

['Offer', 'Order', 'Delivery', 'Invoice'].forEach((type) => {
    Tine.widgets.grid.RendererManager.register('Sales', `Document_${type}`, 'document_number', documentNumberRenderer);
})


export {
    documentNumberRenderer
}
