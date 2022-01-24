/*
 * borrowed from https://github.com/annexare/toURI/blob/master/lib/toURI.js
 * @license MIT
 *
 * NOTE: this is just an ultraslim transliterate implementation for practical purpuses in client
 *       if you need mor exact transliteration do it on server side!
 */
const pairs = {
    // Cyrillic
    'а': 'a',   'б': 'b',   'в': 'v',   'г': 'g',
    'д': 'd',   'е': 'e',   'ё': 'yo',  'ж': 'zh',
    'з': 'z',   'и': 'y',   'й': 'j',
    'кс': 'x',  'к': 'k',
    'л': 'l',   'м': 'm',   'н': 'n',   'о': 'o',
    'п': 'p',   'р': 'r',   'с': 's',   'т': 't',
    'у': 'u',   'ф': 'f',   'х': 'h',   'ц': 'ts',
    'ч': 'ch',  'ш': 'sh',  'щ': 'shch','ъ': '',
    'ы': 'y',   'ь': '',    'э': 'e',
    // Part is done as: http://zakon4.rada.gov.ua/laws/show/55-2010-%D0%BF
    // But don't agree with "Гг | Hh", nobody talks like that.
    '^ю': 'yu', '\\s+ю': ' yu', 'ю': 'iu',
    '^я': 'ya', '\\s+я': ' ya', 'я': 'ia',
    '^є': 'ye', '\\s+є': ' ye', 'є': 'ie',
    'і': 'i',
    '^ї': 'yi', '\\s+ї': ' yi', 'ї': 'i',
    'ґ': 'gh',
    // Symbols & Accents
    '\\.': '_',
    '&': 'and',
    '∞': 'infinity',
    '♥': 'love',
    'ä|æ|ǽ': 'ae',
    'ö|œ': 'oe',
    'ü': 'ue',
    'à|á|â|ã|å|ǻ|ā|ă|ą|ǎ|ª': 'a',
    'ç|ć|ĉ|ċ|č': 'c',
    'ð|ď|đ': 'd',
    'è|é|ê|ë|ē|ĕ|ė|ę|ě': 'e',
    'ĝ|ğ|ġ|ģ': 'g',
    'ĥ|ħ': 'h',
    'ì|í|î|ï|ĩ|ī|ĭ|ǐ|į|ı': '',
    'ĵ': 'j',
    'ķ': 'k',
    'ĺ|ļ|ľ|ŀ|ł': 'l',
    'ñ|ń|ņ|ň|ŉ': 'n',
    'ò|ó|ô|õ|ō|ŏ|ǒ|ő|ơ|ø|ǿ|º': 'o',
    'ŕ|ŗ|ř': 'r',
    'ś|ŝ|ş|š|ſ': 's',
    'ţ|ť|ŧ': 't',
    'ù|ú|û|ũ|ū|ŭ|ů|ű|ų|ư|ǔ|ǖ|ǘ|ǚ|ǜ': 'u',
    'ý|ÿ|ŷ': 'y',
    'ŵ': 'w',
    'ź|ż|ž': 'z',
    'ß': 'ss',
    'ĳ': 'ij',
    'ƒ': 'f',
    // Currencies
    '\\$': 'USD',
    '€': 'EUR',
    '£': 'GBP',
    '₴': 'UAH',
    '¢': 'cent'
}
const isRU = /ы|ъ|э|ё|ъ|(\s|^)и|жь|чь|шь|иа|ие|ии|ио|иу|аи|еи|ои|уи|цк|ец(\s|$)/
const isUA = /є|i|ї|ґ|зьк|ськ|цьк|ць(\s|$)|(нн|тт|чч)[юя]/
const strtr = (string, pairs) => {
    // Just like this:
    // http://php.net/manual/en/function.strtr.php
    let str = string;
    for (var key in pairs) if (pairs.hasOwnProperty(key)) {
        str = str.replace(new RegExp(key, 'g'), pairs[key]);
    }
    return str;
}
const transliterate = (text) => {
    pairs['и'] = ((text.search(isRU) !== -1) && (text.search(isUA) === -1)) ? 'i' : 'y';

    return strtr(text, pairs);
}

export default transliterate
