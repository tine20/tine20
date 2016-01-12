/*!
 * Expresso Lite
 * A text field which create badges for comma-separated strings.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

define(['jquery',
    'common-js/App'
],
function($, App) {
App.LoadCss('common-js/TextBadges.css');
var TextBadges = function(options) {
    var userOpts = $.extend({
        $target: null, // DIV where our field will be rendered
        animationTime: (App.IsPhone() ? 250 : 200),
        inputType: 'text',
        inputSize: 20
    }, options);

    var THIS       = this;
    var $ul        = null;
    var $input     = null;
    var onBlurCB   = null; // user callbacks
    var onRemoveCB = null;

    (function _Constructor() {
        $ul = $('#TextBadges_template > .TextBadges_ul').clone();
        userOpts.$target.append($ul);

        var $inputLi = $('#TextBadges_template > .TextBadges_inputLi').clone();
        $input = $inputLi.find('input:first');
        $input.attr('type', userOpts.inputType); // can be text, email, etc.
        $input.attr('size', userOpts.inputSize);
        $ul.append($inputLi);

        _SetEvents();
    })();

    function _SetEvents() {
        userOpts.$target.on('click', function() {
            $input.focus();
        });

        $ul.on('click', '.TextBadges_badgeLi', function() {
            var $badge = $(this);
            $badge.slideUp(userOpts.animationTime, function() {
                $badge.remove();
                $input.focus();
                if (onRemoveCB !== null) {
                    onRemoveCB(); // invoke user callback
                }
            });
        });

        $input.on('focus', function() {
            if (!$input.val().length) {
                $input.attr('size', userOpts.inputSize); // reset to default
            }
        });

        $input.on('blur', function() {
            if (!$input.val().length) {
                $input.attr('size', userOpts.inputSize); // reset to default
            }
            if (onBlurCB !== null) {
                onBlurCB($input.val()); // invoke user callback
            }
        });
    }

    THIS.addBadge = function(text, fullVal) {
        var defer = $.Deferred();
        var $badge = $('#TextBadges_template > .TextBadges_badgeLi')
            .clone()
            .text(text)
            .attr('title', fullVal)
            .insertBefore($input.parent())
            .hide()
            .slideDown(userOpts.animationTime, function() {
                defer.resolve();
            });
        return defer.promise();
    };

    THIS.getBadgeValues = function() {
        var ret = [];
        $ul.find('.TextBadges_badgeLi').each(function(idx, elem) {
            ret.push($(elem).attr('title'));
        });
        return ret;
    };

    THIS.removeLastBadge = function() {
        // Intended to be used when a badge is about to be removed with backspace.
        // After the badge goes away, the text within the badge fills the input field.
        var $badge = $ul.find('.TextBadges_badgeLi:last');
        if ($badge.length) {
            var txtVal = $badge.attr('title');
            $badge.remove();
            $input.val(txtVal);
            if (txtVal.length > $input.attr('size')) {
                $input.attr('size', txtVal.length); // text may be too long, stretch input
            }
            $input.focus();
            if (onRemoveCB !== null) {
                onRemoveCB(); // invoke user callback
            }
        }
        return THIS;
    };

    THIS.setFocus = function() {
        $input.focus();
        return THIS;
    };

    THIS.getInputField = function() {
        return $input; // direct reference to input, use with care!
    };

    THIS.onBlur = function(callback) {
        onBlurCB = callback; // onBlur(text)
        return THIS;
    };

    THIS.onRemove = function(callback) {
        onRemoveCB = callback; // onRemove()
        return THIS;
    };
};

TextBadges.Load = function() {
    // Static method, since this class can be instantiated ad-hoc.
    return $('#TextBadges_template').length ?
        $.Deferred().resolve().promise() :
        App.LoadTemplate('../common-js/TextBadges.html');
};

return TextBadges;
});
