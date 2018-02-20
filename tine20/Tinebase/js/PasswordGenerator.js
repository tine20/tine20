/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tinebase');

/**
 * PasswordGenerator.
 *
 * @param config
 * @constructor
 */
Tine.Tinebase.PasswordGenerator = function (config) {
    this.minLength = config.minLength || 12;
    this.minWordChars = config.minWordChars || 5;
    this.minUppercaseChars = config.minUppercaseChars || 1;
    this.minSpecialChars = config.minSpecialChars || 1;
    this.minNumericalChars = config.minNumericalChars || 0;
};

Tine.Tinebase.PasswordGenerator.prototype = {
    /**
     * Ascii range for uppercase characters
     * @private
     */
    uppercaseChars: [65, 90],
    /**
     * Ascii range for lowercase characters
     * @private
     */
    lowercaseChars: [97, 122],
    /**
     * Ascii range for special chars
     * @private
     */
    specialChars: [33, 46],
    /**
     * Ascii range for numerical chars
     * @private
     */
    numericalChars: [48, 57],

    /**
     * Characters to be used in password
     * @private
     */
    characters: [],

    /**
     * Generates a random password based on the minimal criteria defined in config
     */
    generatePassword: function () {
        this.characters = [];

        this.addCharsOfType(this.uppercaseChars, this.minUppercaseChars);
        this.addCharsOfType(this.specialChars, this.minSpecialChars);
        this.addCharsOfType(this.numericalChars, this.minNumericalChars);
        this.addCharsOfType(this.lowercaseChars, this.minWordChars);
        this.addCharsOfType(this.lowercaseChars, this.minLength - this.characters.length);

        var _ = window.lodash;

        return _.join(_.shuffle(this.characters), '');
    },

    /**
     * @private
     * @param range
     * @param min
     */
    addCharsOfType: function(range, min) {
        for(var i = 0; i < min; i++) {
            this.characters.push(this.getRandomCharOfRange(range));
        }
    },

    /**
     * @private
     * @param range
     * @return {string}
     */
    getRandomCharOfRange: function (range) {
        var _ = window.lodash;
        return String.fromCharCode(_.random(range[0], range[1]));
    }
};