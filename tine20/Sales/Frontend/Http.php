<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * This class handles all Http requests for the Sales application
 *
 * @package     Sales
 * @subpackage  Frontend
 */
class Sales_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    protected $_applicationName = 'Sales';
    
    /**
     * Returns all JS files which must be included for this app
     *
     * @return array Array of filenames
     */
    public function getJsFilesToInclude()
    {
        return array(
            'Sales/js/Sales.js',
            'Sales/js/ContractGridPanel.js',
            'Sales/js/ContractEditDialog.js',
        );
    }
}
