<?php
/**
 * Tine 2.0
 *
 * @package     RedisWorker
 * @subpackage  Redis
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * redis worker
 *
 * @package     RedisWorker
 * @subpackage  Redis
 */
class RedisWorker extends Tinebase_Redis_Worker_Abstract
{
    protected function _runCondition()
    {
        return ($this->_jobsHandled === 0);
    }
    
    public function doJob($job)
    {
        echo 'handled ' . $job->action . " job.";
    }
}
