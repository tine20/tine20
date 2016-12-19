<?php
/**
 * Tine 2.0
 * 
 * anchor validator/transformator for html purifier
 *
 * @package     Expressomail
 * @subpackage  HTMLPurifier
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Expressomail_HTMLPurifier_AttrTransform_AValidator
 *
 */
class Expressomail_HTMLPurifier_AttrTransform_AValidator extends HTMLPurifier_AttrTransform
{
    var $name = "Link validation";
    
    function transform($attr, $config, $context) {
        $attr['target'] = '_blank';
        return $attr;
    }
}
