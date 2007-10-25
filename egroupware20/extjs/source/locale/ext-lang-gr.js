/*
 * Ext JS Library 2.0 Beta 1
 * Copyright(c) 2006-2007, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://extjs.com/license
 */

/**
 * Greek Translations by Vagelis
 * 03-June-2007
 */

Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">�������...</div>';

if(Ext.View){
   Ext.View.prototype.emptyText = "";
}

if(Ext.grid.Grid){
   Ext.grid.Grid.prototype.ddText = "{0} ����������(��) ������(��)";
}

if(Ext.TabPanelItem){
   Ext.TabPanelItem.prototype.closeText = "������� ���� ��� �������";
}

if(Ext.form.Field){
   Ext.form.Field.prototype.invalidText = "� ���� ��� ����� ��� ����� ������";
}

if(Ext.LoadMask){
    Ext.LoadMask.prototype.msg = "�������...";
}

Date.monthNames = [
   "����������",
   "�����������",
   "�������",
   "��������",
   "�����",
   "�������",
   "�������",
   "���������",
   "�����������",
   "���������",
   "���������",
   "����������"
];

Date.dayNames = [
   "�������",
   "�������",
   "�����",
   "�������",
   "������",
   "���������",
   "�������"
];

if(Ext.MessageBox){
   Ext.MessageBox.buttonText = {
      ok     : "�������",
      cancel : "�������",
      yes    : "���",
      no     : "���"
   };
}

if(Ext.util.Format){
   Ext.util.Format.date = function(v, format){
      if(!v) return "";
      if(!(v instanceof Date)) v = new Date(Date.parse(v));
      return v.dateFormat(format || "�/�/�");
   };
}

if(Ext.DatePicker){
   Ext.apply(Ext.DatePicker.prototype, {
      todayText         : "������",
      minText           : "� ���������� ���� ����� ���� ��� ��������� ����������",
      maxText           : "� ���������� ���� ����� ���� ��� ���������� ����������",
      disabledDaysText  : "",
      disabledDatesText : "",
      monthNames	: Date.monthNames,
      dayNames		: Date.dayNames,
      nextText          : '�������� ����� (Control+Right)',
      prevText          : '������������ ����� (Control+Left)',
      monthYearText     : '�������� ���� (Control+Up/Down ��� ���������� ��� ���)',
      todayTip          : "{0} (Spacebar)",
      format            : "�/�/�"
   });
}

if(Ext.PagingToolbar){
   Ext.apply(Ext.PagingToolbar.prototype, {
      beforePageText : "������",
      afterPageText  : "��� {0}",
      firstText      : "����� ������",
      prevText       : "����������� ������",
      nextText       : "������� ������",
      lastText       : "��������� ������",
      refreshText    : "��������",
      displayMsg     : "�������� {0} - {1} ��� {2}",
      emptyMsg       : '��� �������� �������� ��� ��������'
   });
}

if(Ext.form.TextField){
   Ext.apply(Ext.form.TextField.prototype, {
      minLengthText : "�� �������� ������� ��� ���� �� ����� ����� {0}",
      maxLengthText : "�� ������� ������� ��� ���� �� ����� ����� {0}",
      blankText     : "�� ����� ���� ����� �����������",
      regexText     : "",
      emptyText     : null
   });
}

if(Ext.form.NumberField){
   Ext.apply(Ext.form.NumberField.prototype, {
      minText : "� �������� ���� ��� ���� �� ����� ����� {0}",
      maxText : "� ������� ���� ��� ���� �� ����� ����� {0}",
      nanText : "{0} ��� ����� ������� �������"
   });
}

if(Ext.form.DateField){
   Ext.apply(Ext.form.DateField.prototype, {
      disabledDaysText  : "����������������",
      disabledDatesText : "����������������",
      minText           : "� ���������� �' ���� �� ����� ������ �� ����� ���� ��� {0}",
      maxText           : "� ���������� �' ���� �� ����� ������ �� ����� ���� ��� {0}",
      invalidText       : "{0} ��� ����� ������ ���������� - ������ �� ����� ��� ������ {1}",
      format            : "�/�/�"
   });
}

if(Ext.form.ComboBox){
   Ext.apply(Ext.form.ComboBox.prototype, {
      loadingText       : "�������...",
      valueNotFoundText : undefined
   });
}

if(Ext.form.VTypes){
   Ext.apply(Ext.form.VTypes, {
      emailText    : '���� �� ����� ������ �� ����� e-mail address ��� ������ "user@domain.com"',
      urlText      : '���� �� ����� ������ �� ����� ��� ��������� URL ��� ������ "http:/'+'/www.domain.com"',
      alphaText    : '���� �� ����� ������ �� �������� �������� ��� _',
      alphanumText : '���� �� ����� ������ �� �������� ��������, �������� ��� _'
   });
}

if(Ext.grid.GridView){
   Ext.apply(Ext.grid.GridView.prototype, {
      sortAscText  : "������� ����������",
      sortDescText : "�������� ����������",
      lockText     : "�������� ������",
      unlockText   : "���������� ������",
      columnsText  : "������"
   });
}

if(Ext.grid.PropertyColumnModel){
   Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
      nameText   : "�����",
      valueText  : "����",
      dateFormat : "�/�/�"
   });
}

if(Ext.SplitLayoutRegion){
   Ext.apply(Ext.SplitLayoutRegion.prototype, {
      splitTip            : "������ ��� ������ ��������.",
      collapsibleSplitTip : "������ ��� ������ ��������. Double click ��� ��������."
   });
}
