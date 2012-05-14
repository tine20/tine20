/**
 * @license     http://creativecommons.org/licenses/publicdomain Public Domain
 * @author      Koji Horaguchi <horaguchi@horaguchi.net>
 */
 
/* We don't support dynamic inclusion of po files any longer!
if (typeof ActiveXObject != 'undefined' && typeof XMLHttpRequest == 'undefined') {
  XMLHttpRequest = function () { try { return new ActiveXObject("Msxml2.XMLHTTP");
  } catch (e) { return new ActiveXObject("Microsoft.XMLHTTP"); } };
}
*/

/**
 * @constuctor
 */
Locale.Gettext = function (locale) {
    this.locale = typeof locale == 'string' ? new Locale(Locale.LC_ALL, locale) : locale || Locale;
    this.domain = 'messages';
    this.category = Locale.LC_MESSAGES;
    this.suffix = 'po';
    this.dir = '.';
};

Locale.Gettext.prototype.bindtextdomain = function (domain, dir) {
  this.dir = dir;
  this.domain = domain;
};

Locale.Gettext.prototype.textdomain = function (domain) {
  this.domain = domain;
};

Locale.Gettext.prototype.getmsg = function (domain, category, reload) {
  var key = this._getkey(category, domain);
  return Locale.Gettext.prototype._msgs[key];
};

Locale.Gettext.prototype._msgs = {};

Locale.Gettext.prototype._getkey = function(category, domain) {
    return this.dir + '/' + category + '/' + domain; // expect category is str
};

/*
Locale.Gettext.prototype._url = function (category, domain) {
 try {
    var req = new XMLHttpRequest;

    req.open('POST', 'index.php', false);
    req.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    req.setRequestHeader('X-Tine20-Request-Type', 'JSON');
    req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    req.send('method=Tinebase.getTranslations&requestType=JSON&application=' + domain + '&jsonKey=' + Tine.Tinebase.registry.get('jsonKey'));
    if (req.status == 200 || req.status == 304 || req.status == 0 || req.status == null) {
      return Ext.util.JSON.decode(req.responseText);
    }
  } catch (e) {
    return '';
  }
};
*/

Locale.Gettext.prototype.dcgettext = function (domain, msgid, category) {
  var msg = this.getmsg(domain, category);
  
  return msg ? msg.get(msgid) || msgid : msgid;
};

Locale.Gettext.prototype.dcngettext = function (domain, msgid, msgid_plural, n, category) {
  var msg = this.getmsg(domain, category);
  
  if (msg) {
    return (msg.get(msgid, msgid_plural) || [msgid, msgid_plural])[msg.plural(n)] || (n > 1 ? msgid_plural : msgid);
  } else {
    // fallback if cataloge is not available
    return n > 1 ? msgid_plural : msgid;
  }
};

Locale.Gettext.prototype.dgettext = function (domain, msgid) {
  return this.dcgettext(domain, msgid, this.category);
};

Locale.Gettext.prototype.dngettext = function (domain, msgid, msgid_plural, n) {
  return this.dcngettext(domain, msgid, msgid_plural, n, this.category);
};

Locale.Gettext.prototype.gettext = Locale.Gettext.prototype._ = Locale.Gettext.prototype._hidden = function (msgid) {
  return this.dcgettext(this.domain, msgid, this.category);
};

Locale.Gettext.prototype.ngettext = Locale.Gettext.prototype.n_ = Locale.Gettext.prototype.n_hidden = function (msgid, msgid_plural, n) {
  return this.dcngettext(this.domain, msgid, msgid_plural, n, this.category);
};

Locale.Gettext.prototype.gettext_noop = Locale.Gettext.prototype.N_ = function (msgid) {
  return msgid;
};

// extend object
(function () {
  for (var i in Locale.Gettext.prototype) {
    Locale.Gettext[i] = function (func) {
      return function () {
        return func.apply(Locale.Gettext, arguments);
      };
    }(Locale.Gettext.prototype[i]);
  }
})();

// Locale.Gettext.PO

if (typeof Locale.Gettext.PO == 'undefined') {
  Locale.Gettext.PO = function (object) {
    if (typeof object == 'string' || object instanceof String) {
      this.msg = Locale.Gettext.PO.po2object(object);
    } else if (object instanceof Object) {
      this.msg = object;
    } else {
      this.msg = {};
    }
  };
}

/*
Locale.Gettext.PO.VERSION = '0.0.4';
Locale.Gettext.PO.EXPORT_OK = [
  'po2object',
  'po2json'
];

Locale.Gettext.PO.po2object = function (po) {
  return eval(Locale.Gettext.PO.po2json(po));
};


Locale.Gettext.PO.po2json = function (po) {
  var first = true, plural = false;
  return '({\n' + po
    .replace(/\r?\n/g, '\n')
    .replace(/#.*\n/g, '')
    .replace(/"(\n+)"/g, '')
    .replace(/msgid "(.*?)"\nmsgid_plural "(.*?)"/g, 'msgid "$1, $2"')
    .replace(/msg(\S+) /g, function (match, op) {
      switch (op) {
      case 'id':
        return first
          ? (first = false, '')
          : plural
            ? (plural = false, ']\n, ')
            : ', ';
      case 'str':
        return ': ';
      case 'str[0]':
        return plural = true, ': [\n  ';
      default:
        return ' ,';
      }
    }) + (plural ? ']\n})' : '\n})');
};
*/

Locale.Gettext.PO.prototype.get = function (msgid, msgid_plural) {
  // for msgid_plural == ""
  return typeof msgid_plural != 'undefined' ? this.msg[msgid + ', ' + msgid_plural] : this.msg[msgid];
};

Locale.Gettext.PO.prototype.plural = function (n) {
  var nplurals, plural;
  eval((this.msg[''] + 'Plural-Forms: nplurals=2; plural=n != 1\n').match(/Plural-Forms:(.*)\n/)[1]);
  return plural === true
    ? 1
    : plural === false
      ? 0
      : plural;
};

// create dummy domain
Locale.Gettext.prototype._msgs.emptyDomain = new Locale.Gettext.PO(({}));
