/*
 * Ext JS Library 2.0 Beta 1
 * Copyright(c) 2006-2007, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://extjs.com/license
 */

/*
 * France (France) translation
 * By Perrich
 * 06-08-2007, 09:07 PM
 */

Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">En cours de chargement...</div>';

if(Ext.View){
   Ext.View.prototype.emptyText = "";
}

if(Ext.grid.Grid){
   Ext.grid.Grid.prototype.ddText = "{0} ligne(s) s�lectionn�(s)";
}

if(Ext.TabPanelItem){
   Ext.TabPanelItem.prototype.closeText = "Fermer cet onglet";
}

if(Ext.form.Field){
   Ext.form.Field.prototype.invalidText = "La valeur de ce champ est invalide";
}

if(Ext.LoadMask){
    Ext.LoadMask.prototype.msg = "En cours de chargement...";
}

Date.monthNames = [
   "Janvier",
   "F�vrier",
   "Mars",
   "Avril",
   "Mai",
   "Juin",
   "Juillet",
   "Ao�t",
   "Septembre",
   "Octobre",
   "Novembre",
   "D�cembre"
];

Date.dayNames = [
   "Dimanche",
   "Lundi",
   "Mardi",
   "Mercredi",
   "Jeudi",
   "Vendredi",
   "Samedi"
];

if(Ext.MessageBox){
   Ext.MessageBox.buttonText = {
      ok     : "OK",
      cancel : "Annuler",
      yes    : "Oui",
      no     : "Non"
   };
}

if(Ext.util.Format){
   Ext.util.Format.date = function(v, format){
      if(!v) return "";
      if(!(v instanceof Date)) v = new Date(Date.parse(v));
      return v.dateFormat(format || "d/m/Y");
   };
}

if(Ext.DatePicker){
   Ext.apply(Ext.DatePicker.prototype, {
      todayText         : "Aujourd'hui",
      minText           : "Cette date est plus petite que la date minimum",
      maxText           : "Cette date est plus grande que la date maximum",
      disabledDaysText  : "",
      disabledDatesText : "",
      monthNames	: Date.monthNames,
      dayNames		: Date.dayNames,
      nextText          : 'Prochain mois (CTRL+Fl�che droite)',
      prevText          : "Mois pr�c�dent (CTRL+Fl�che gauche)",
      monthYearText     : "Choisissez un mois (CTRL+Fl�che haut ou bas pour changer d\'ann�e.)",
      todayTip          : "{0} (Barre d'espace)",
      okText            : "&#160;OK&#160;",
      cancelText        : "Annuler",
      format            : "d/m/y",
      startDay          : 1
   });
}

if(Ext.PagingToolbar){
   Ext.apply(Ext.PagingToolbar.prototype, {
      beforePageText : "Page",
      afterPageText  : "sur {0}",
      firstText      : "Premi�re page",
      prevText       : "Page pr�c�dente",
      nextText       : "Page suivante",
      lastText       : "Derni�re page",
      refreshText    : "Actualiser la page",
      displayMsg     : "Page courante {0} - {1} sur {2}",
      emptyMsg       : 'Aucune donn�e � afficher'
   });
}

if(Ext.form.TextField){
   Ext.apply(Ext.form.TextField.prototype, {
      minLengthText : "La longueur minimum de ce champ est de {0} caract�res",
      maxLengthText : "La longueur maximum de ce champ est de {0} caract�res",
      blankText     : "Ce champ est obligatoire",
      regexText     : "",
      emptyText     : null
   });
}

if(Ext.form.NumberField){
   Ext.apply(Ext.form.NumberField.prototype, {
      minText : "La valeur minimum de ce champ doit �tre de {0}",
      maxText : "La valeur maximum de ce champ doit �tre de {0}",
      nanText : "{0} n'est pas un nombre valide"
   });
}

if(Ext.form.DateField){
   Ext.apply(Ext.form.DateField.prototype, {
      disabledDaysText  : "D�sactiv�",
      disabledDatesText : "D�sactiv�",
      minText           : "La date de ce champ doit �tre avant le {0}",
      maxText           : "La date de ce champ doit �tre apr�s le {0}",
      invalidText       : "{0} n'est pas une date valide - elle doit �tre au format suivant: {1}",
      format            : "d/m/y"
   });
}

if(Ext.form.ComboBox){
   Ext.apply(Ext.form.ComboBox.prototype, {
      loadingText       : "En cours de chargement...",
      valueNotFoundText : undefined
   });
}

if(Ext.form.VTypes){
   Ext.apply(Ext.form.VTypes, {
      emailText    : 'Ce champ doit contenir une adresse email au format: "usager@domaine.com"',
      urlText      : 'Ce champ doit contenir une URL au format suivant: "http:/'+'/www.domaine.com"',
      alphaText    : 'Ce champ ne peut contenir que des lettres et le caract�re soulign� (_)',
      alphanumText : 'Ce champ ne peut contenir que des caract�res alphanum�riques ainsi que le caract�re soulign� (_)'
   });
}

if(Ext.form.HtmlEditor){
   Ext.apply(Ext.form.HtmlEditor.prototype, {
      createLinkText : "Veuillez entrer l'URL pour ce lien:",
          buttonTips : {
              bold : {
                  title: 'Gras (Ctrl+B)',
                  text: 'Met le texte s�lectionn� en gras.',
                  cls: 'x-html-editor-tip'
              },
              italic : {
                  title: 'Italique (Ctrl+I)',
                  text: 'Met le texte s�lectionn� en italique.',
                  cls: 'x-html-editor-tip'
              },
              underline : {
                  title: 'Soulign� (Ctrl+U)',
                  text: 'Souligne le texte s�lectionn�.',
                  cls: 'x-html-editor-tip'
              },
              increasefontsize : {
                  title: 'Agrandir la police',
                  text: 'Augmente la taille de la police.',
                  cls: 'x-html-editor-tip'
              },
              decreasefontsize : {
                  title: 'R�duire la police',
                  text: 'R�duit la taille de la police.',
                  cls: 'x-html-editor-tip'
              },
              backcolor : {
                  title: 'Couleur de surbrillance',
                  text: 'Modifie la couleur de fond du texte s�lectionn�.',
                  cls: 'x-html-editor-tip'
              },
              forecolor : {
                  title: 'Couleur de police',
                  text: 'Modifie la couleur du texte s�lectionn�.',
                  cls: 'x-html-editor-tip'
              },
              justifyleft : {
                  title: 'Aligner � gauche',
                  text: 'Aligne le texte � gauche.',
                  cls: 'x-html-editor-tip'
              },
              justifycenter : {
                  title: 'Centrer',
                  text: 'Centre le texte.',
                  cls: 'x-html-editor-tip'
              },
              justifyright : {
                  title: 'Aligner � droite',
                  text: 'Aligner le texte � droite.',
                  cls: 'x-html-editor-tip'
              },
              insertunorderedlist : {
                  title: 'Liste � puce',
                  text: 'D�marre une liste � puce.',
                  cls: 'x-html-editor-tip'
              },
              insertorderedlist : {
                  title: 'Liste num�rot�e',
                  text: 'D�marre une liste num�rot�e.',
                  cls: 'x-html-editor-tip'
              },
              createlink : {
                  title: 'Lien hypertexte',
                  text: 'Transforme en lien hypertexte.',
                  cls: 'x-html-editor-tip'
              },
              sourceedit : {
                  title: 'Code source',
                  text: 'Basculer en mode �dition du code source.',
                  cls: 'x-html-editor-tip'
              }
        }
   });
}

if(Ext.grid.GridView){
   Ext.apply(Ext.grid.GridView.prototype, {
      sortAscText  : "Tri croissant",
      sortDescText : "Tri d�croissant",
      lockText     : "Verrouiller la colonne",
      unlockText   : "D�verrouiller la colonne",
      columnsText  : "Colonnes"
   });
}

if(Ext.grid.PropertyColumnModel){
   Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
      nameText   : "Propri�t�",
      valueText  : "Valeur",
      dateFormat : "d/m/Y"
   });
}

if(Ext.SplitLayoutRegion){
   Ext.apply(Ext.SplitLayoutRegion.prototype, {
      splitTip            : "Cliquer et glisser pour redimensionner le panneau.",
      collapsibleSplitTip : "Cliquer et glisser pour redimensionner le panneau. Double-cliquer pour le cacher."
   });
}
