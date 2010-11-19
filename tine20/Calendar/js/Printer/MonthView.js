Tine.Calendar.Printer.MonthViewRenderer = Ext.extend(Tine.Calendar.Printer.BaseRenderer, {
    return view.numOfDays === 1 ? days[0] : String.format('<table>{0}</table>', this.generateCalRows(days, view.numOfDays < 9 ? 2 : 7));
});