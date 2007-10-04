/**
 * Greek Translations by Vagelis
 * 03-June-2007
 */

Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">Φόρτωση...</div>';

if(Ext.View){
   Ext.View.prototype.emptyText = "";
}

if(Ext.grid.Grid){
   Ext.grid.Grid.prototype.ddText = "{0} επιλεγμένη(ες) γραμμή(ές)";
}

if(Ext.TabPanelItem){
   Ext.TabPanelItem.prototype.closeText = "Κλείστε αυτή την καρτέλα";
}

if(Ext.form.Field){
   Ext.form.Field.prototype.invalidText = "Η τιμή στο πεδίο δεν είναι έγκυρη";
}

if(Ext.LoadMask){
    Ext.LoadMask.prototype.msg = "Φόρτωση...";
}

Date.monthNames = [
   "Ιανουάριος",
   "Φεβρουάριος",
   "Μάρτιος",
   "Απρίλιος",
   "Μάιος",
   "Ιούνιος",
   "Ιούλιος",
   "Αύγουστος",
   "Σεπτέμβριος",
   "Οκτώβριος",
   "Νοέμβριος",
   "Δεκέμβριος"
];

Date.dayNames = [
   "Κυριακή",
   "Δευτέρα",
   "Τρίτη",
   "Τετάρτη",
   "Πέμπτη",
   "Παρασκευή",
   "Σάββατο"
];

if(Ext.MessageBox){
   Ext.MessageBox.buttonText = {
      ok     : "Εντάξει",
      cancel : "Ακύρωση",
      yes    : "Ναι",
      no     : "Όχι"
   };
}

if(Ext.util.Format){
   Ext.util.Format.date = function(v, format){
      if(!v) return "";
      if(!(v instanceof Date)) v = new Date(Date.parse(v));
      return v.dateFormat(format || "μ/η/Ε");
   };
}

if(Ext.DatePicker){
   Ext.apply(Ext.DatePicker.prototype, {
      todayText         : "Σήμερα",
      minText           : "Η ημερομηνία αυτή είναι πριν την μικρότερη ημερομηνία",
      maxText           : "Η ημερομηνία αυτή είναι μετά την μεγαλύτερη ημερομηνία",
      disabledDaysText  : "",
      disabledDatesText : "",
      monthNames	: Date.monthNames,
      dayNames		: Date.dayNames,
      nextText          : 'Επόμενος Μήνας (Control+Right)',
      prevText          : 'Προηγούμενος Μήνας (Control+Left)',
      monthYearText     : 'Επιλέξτε Μήνα (Control+Up/Down για μετακίνηση στα έτη)',
      todayTip          : "{0} (Spacebar)",
      format            : "μ/η/Ε"
   });
}

if(Ext.PagingToolbar){
   Ext.apply(Ext.PagingToolbar.prototype, {
      beforePageText : "Σελίδα",
      afterPageText  : "από {0}",
      firstText      : "Πρώτη σελίδα",
      prevText       : "Προηγούμενη σελίδα",
      nextText       : "Επόμενη σελίδα",
      lastText       : "Τελευταία σελίδα",
      refreshText    : "Ανανέωση",
      displayMsg     : "Εμφάνιση {0} - {1} από {2}",
      emptyMsg       : 'Δεν βρέθηκαν εγγραφές για εμφάνιση'
   });
}

if(Ext.form.TextField){
   Ext.apply(Ext.form.TextField.prototype, {
      minLengthText : "Το ελάχιστο μέγεθος για αυτό το πεδίο είναι {0}",
      maxLengthText : "Το μέγιστο μέγεθος για αυτό το πεδίο είναι {0}",
      blankText     : "Το πεδίο αυτό είναι υποχρεωτοκό",
      regexText     : "",
      emptyText     : null
   });
}

if(Ext.form.NumberField){
   Ext.apply(Ext.form.NumberField.prototype, {
      minText : "Η ελάχιστη τιμή για αυτό το πεδίο είναι {0}",
      maxText : "Η μέγιστη τιμή για αυτό το πεδίο είναι {0}",
      nanText : "{0} δεν είναι έγκυρος αριθμός"
   });
}

if(Ext.form.DateField){
   Ext.apply(Ext.form.DateField.prototype, {
      disabledDaysText  : "Απενεργοποιημένο",
      disabledDatesText : "Απενεργοποιημένο",
      minText           : "Η ημερομηνία σ' αυτό το πεδίο πρέπει να είναι μετά από {0}",
      maxText           : "Η ημερομηνία σ' αυτό το πεδίο πρέπει να είναι πριν από {0}",
      invalidText       : "{0} δεν είναι έγκυρη ημερομηνία - πρέπει να είναι της μορφής {1}",
      format            : "μ/η/Ε"
   });
}

if(Ext.form.ComboBox){
   Ext.apply(Ext.form.ComboBox.prototype, {
      loadingText       : "Φόρτωση...",
      valueNotFoundText : undefined
   });
}

if(Ext.form.VTypes){
   Ext.apply(Ext.form.VTypes, {
      emailText    : 'Αυτό το πεδίο πρέπει να είναι e-mail address της μορφής "user@domain.com"',
      urlText      : 'Αυτό το πεδίο πρέπει να είναι μια διεύθυνση URL της μορφής "http:/'+'/www.domain.com"',
      alphaText    : 'Αυτό το πεδίο πρέπει να περιέχει γράμματα και _',
      alphanumText : 'Αυτό το πεδίο πρέπει να περιέχει γράμματα, αριθμούς και _'
   });
}

if(Ext.grid.GridView){
   Ext.apply(Ext.grid.GridView.prototype, {
      sortAscText  : "Αύξουσα Ταξινόμηση",
      sortDescText : "Φθίνουσα Ταξινόμηση",
      lockText     : "Κλείδωμα στήλης",
      unlockText   : "Ξεκλείδωμα στήλης",
      columnsText  : "Στήλες"
   });
}

if(Ext.grid.PropertyColumnModel){
   Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
      nameText   : "Όνομα",
      valueText  : "Τιμή",
      dateFormat : "μ/η/Ε"
   });
}

if(Ext.SplitLayoutRegion){
   Ext.apply(Ext.SplitLayoutRegion.prototype, {
      splitTip            : "Σύρετε για αλλαγή μεγέθους.",
      collapsibleSplitTip : "Σύρετε για αλλαγή μεγέθους. Double click για απόκρυψη."
   });
}
