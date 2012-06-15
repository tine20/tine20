<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  InputFilter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

/**
 * Implemetation of Zend_Filter_PregReplace
 * 
 * @package     Tinebase
 * @subpackage  InputFilter
 */
class Tinebase_Model_InputFilter_CrlfConvert extends Zend_Filter_PregReplace {

    /**
     * Pattern to match
     * @var mixed
     */
    protected $_matchPattern = '/\r\n/';

    /**
     * Replacement pattern
     * @var mixed
     */
    protected $_replacement = "\n";
} 