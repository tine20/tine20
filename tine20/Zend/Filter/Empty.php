<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Filter
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
 * @see Zend_Filter_Interface
 */
require_once 'Zend/Filter/Interface.php';

/**
 * @category   Zend
 * @package    Zend_Filter
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Filter_Empty implements Zend_Filter_Interface
{

    /**
     * Replacement pattern
     * @var mixed
     */
    protected $_replacement = null;
    
    /**
     * Constructor
     *
     * @param  string $replace
     * @return void
     */
    public function __construct($replacement)
    {
        $this->setReplacement($replacement);
    }
    
    /**
     * Set the Replacement pattern/string for the preg_replace called in filter
     *
     * @param mixed $replacement - same as the second argument of preg_replace
     * @return Zend_Filter_PregReplace
     */
    public function setReplacement($replacement)
    {
        $this->_replacement = $replacement;
        return $this;
    }
    
    /**
     * Get currently set replacement value
     *
     * @return string
     */
    public function getReplacement()
    {
        return $this->_replacement;
    }

    /**
     * Perform regexp replacement as filter
     *
     * @param  string $value
     * @return string
     */
    public function filter($value)
    {
        return empty($value) ? $this->_replacement : $value;
    }
}