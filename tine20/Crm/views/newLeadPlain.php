<?php
/**
 * new lead email text (plain)
 * 
 * @package     Crm
 * @subpackage  Views
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      ?
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
?>
**<?php echo $this->lead->lead_name . " **\n" ?>

<?php echo $this->lead->description ?>


<?php echo $this->lang_state ?>: <?php echo $this->leadState['leadstate'] . "\n"?>
<?php echo $this->lang_type ?>: <?php echo $this->leadType['leadtype']. "\n" ?>
<?php echo $this->lang_source ?>: <?php echo $this->leadSource['leadsource'] . "\n" ?>

<?php echo $this->lang_start ?>: <?php echo $this->start . "\n" ?>
<?php echo $this->lang_scheduledEnd ?>: <?php echo $this->ScheduledEnd . "\n" ?>
<?php echo $this->lang_end ?>: <?php echo $this->leadEnd . "\n" ?>

<?php echo $this->lang_turnover ?>: <?php echo $this->lead->turnover . "\n" ?>
<?php echo $this->lang_probability ?>: <?php echo $this->lead->probability . "%\n" ?>

<?php echo $this->lang_folder ?>: <?php echo $this->container->name . "\n" ?>

<?php echo $this->lang_updatedBy ?>: <?php echo $this->updater->accountDisplayName . "\n" ?>

<?php if (count($this->updates) > 0): ?>
<?php echo $this->lang_updatedFields . "\n" ?>
<?php foreach ($this->updates as $update): ?>
<?php echo '  ' . sprintf($this->lang_updatedFieldMsg, $update->modified_attribute, $update->old_value, $update->new_value) . "\n" ?>
<?php endforeach; ?>
<?php endif;?>