<?php
/**
 * Tine 2.0
 * 
 * @package     Projects
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Project Status Record Class
 * 
 * @package     Projects
 * @subpackage  Model
 */
class Projects_Model_Status extends Tinebase_Config_KeyFieldRecord
{
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Projects';
    
    /**
     * additional status specific validators
     * 
     * @var array
     */
    protected $_additionalValidators = array(
        'is_open'              => array('allowEmpty' => true,  'Int'  ),
    );
}
