<?php
/**
 * Tine 2.0
 *
 * @package     Projects
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Projects_Setup_Update_Release7 extends Setup_Update_Abstract
{
    /**
     * update to 7.1
     * - add seq
     * 
     * @see 0000554: modlog: records can't be updated in less than 1 second intervals
     */
    public function update_0()
    {
        $declaration = Tinebase_Setup_Update_Release7::getRecordSeqDeclaration();
        try {
            $this->_backend->addCol('projects_project', $declaration);
        } catch (Zend_Db_Statement_Exception $zdse) {
            // ignore
        }
        $this->setTableVersion('projects_project', 2);
        
        Tinebase_Setup_Update_Release7::updateModlogSeq('Projects_Model_Project', 'projects_project');
        
        $this->setApplicationVersion('Projects', '7.1');
    }
}
