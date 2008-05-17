/**
 * @license     public domain
 * @author      Koji Horaguchi <horaguchi@horaguchi.net>
 * @version     $Id$
 */
if (typeof Locale == 'undefined') {
  var Locale = function (category, locale) {
    this._instance = true;
    this.LC_ALL =      'C';
    this.LC_COLLATE =  'C';
    this.LC_CTYPE =    'C';
    this.LC_MESSAGES = 'C';
    this.LC_MONETARY = 'C';
    this.LC_NUMERIC =  'C';
    this.LC_TIME =     'C';
    this.setlocale(category, locale);
  };
}

Locale.VERSION = '0.0.3';
Locale.EXPORT = [
  'LC_ALL',
  'LC_COLLATE',
  'LC_CTYPE',
  'LC_MESSAGES',
  'LC_MONETARY',
  'LC_NUMERIC',
  'LC_TIME'
];
Locale.EXPORT_OK = [
  'setlocale'
];
Locale.EXPORT_TAGS = {
  ':common': Locale.EXPORT,
  ':all': Locale.EXPORT.concat(Locale.EXPORT_OK)
};

Locale.LC_ALL =      'LC_ALL';
Locale.LC_COLLATE =  'LC_COLLATE';
Locale.LC_CTYPE =    'LC_CTYPE';
Locale.LC_MESSAGES = 'LC_MESSAGES';
Locale.LC_MONETARY = 'LC_MONETARY';
Locale.LC_NUMERIC =  'LC_NUMERIC';
Locale.LC_TIME =     'LC_TIME';

Locale.setlocale = Locale.prototype.setlocale = function (category, locale) {
  return function () {
    if (locale === null || typeof locale == 'undefined') {
      return this[category];
    }

    if (locale == '') {
      locale = (window.navigator.browserLanguage || window.navigator.language || 'C')
        .replace(/^(.{2}).?(.{2})?.*$/, function (match, lang, terr) {
          return lang.toLowerCase() + (terr ? '_' + terr.toUpperCase() : '');
        });
    }

    switch (category) {
      case Locale.LC_ALL:
        this.LC_ALL      = locale;
        this.LC_COLLATE  = locale;
        this.LC_CTYPE    = locale;
        this.LC_MESSAGES = locale;
        this.LC_MONETARY = locale;
        this.LC_NUMERIC  = locale;
        this.LC_TIME     = locale;
        break;
      case Locale.LC_COLLATE:
      case Locale.LC_CTYPE:
      case Locale.LC_MESSAGES:
      case Locale.LC_MONETARY:
      case Locale.LC_NUMERIC:
      case Locale.LC_TIME:
        this[category] = locale;
        break;
      default:
        return false;
    }
    return locale;
  }.call(this._instance ? this : arguments.callee);
};

Locale.setlocale.LC_ALL =      'C';
Locale.setlocale.LC_COLLATE =  'C';
Locale.setlocale.LC_CTYPE =    'C';
Locale.setlocale.LC_MESSAGES = 'C';
Locale.setlocale.LC_MONETARY = 'C';
Locale.setlocale.LC_NUMERIC =  'C';
Locale.setlocale.LC_TIME =     'C';