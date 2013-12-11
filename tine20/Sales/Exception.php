<?php
/**
 * Tine 2.0
 * 
 * @package     Sales
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * Sales exception
 * 
 * @package     Sales
 * @subpackage  Exception
 */
class Sales_Exception extends Tinebase_Exception
{
    /**
     * the name of the application, this exception belongs to
     *
     * @var string
     */
    protected $_appName = 'Sales';
}
