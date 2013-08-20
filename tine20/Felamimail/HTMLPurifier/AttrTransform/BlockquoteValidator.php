<?php
/**
 * Tine 2.0
 * 
 * blockquote validator/transformator for html purifier
 *
 * @package     Felamimail
 * @subpackage  HTMLPurifier
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Felamimail_HTMLPurifier_AttrTransform_BlockquoteValidator
 *
 */
class Felamimail_HTMLPurifier_AttrTransform_BlockquoteValidator extends HTMLPurifier_AttrTransform
{
    var $name = "Blockquote validation";
    
    /**
     * (non-PHPdoc)
     * @see HTMLPurifier_AttrTransform::transform()
     */
    function transform($attr, $config, $context)
    {
        $attr['class'] = 'felamimail-body-blockquote';
        return $attr;
    }
}
