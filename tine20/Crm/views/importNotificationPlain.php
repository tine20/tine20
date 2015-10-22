<?php
/**
 * lead import notification for responsibles (plain)
 * 
 * @package     Crm
 * @subpackage  Views
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
?>
<?php if (count($this->importedLeads) > 0): ?>
<?php echo $this->lang_importedLeads . ":\n" ?>
<?php foreach ($this->importedLeads as $lead): ?>
<?php echo '  ' . $lead->lead_name . "()" . "\n" ?>
<?php endforeach; ?>
<?php endif;?>
