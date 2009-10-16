<?php
/**
 * new lead html mail
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
<h1><?php echo $this->lead->lead_name ?></h1>
<?php echo nl2br($this->lead->description) ?>

<br>

<table>
  <tr>
    <td><?php echo $this->lang_state ?></td>
    <td><?php echo $this->leadState['leadstate'] ?></td>
  </tr>
  <tr>
    <td><?php echo $this->lang_type ?></td>
    <td><?php echo $this->leadType['leadtype'] ?></td>
  </tr>
  <tr>
    <td><?php echo $this->lang_source ?></td>
    <td><?php echo $this->leadSource['leadsource'] ?></td>
  </tr>
  <tr>
    <td><?php echo $this->lang_start ?></td>
    <td><?php echo $this->start ?></td>
  </tr>
  <tr>
    <td><?php echo $this->lang_scheduledEnd ?></td>
    <td><?php echo $this->ScheduledEnd ?></td>
  </tr>
  <tr>
    <td><?php echo $this->lang_end ?></td>
    <td><?php echo $this->leadEnd ?></td>
  </tr>
  <tr>
    <td><?php echo $this->lang_turnover ?></td>
    <td><?php echo $this->lead->turnover ?></td>
  </tr>
  <tr>
    <td><?php echo $this->lang_probability ?></td>
    <td><?php echo $this->lead->probability ?>%</td>
  </tr>
  <tr>
    <td><?php echo $this->lang_folder ?></td>
    <td><?php echo $this->container->name ?></td>
  </tr>
  <tr>
    <td><?php echo $this->lang_updatedBy ?></td>
    <td><?php echo $this->updater->accountDisplayName ?></td>
  </tr>
</table>

<?php if (count($this->updates) > 0): ?>
  <b><?php echo $this->lang_updatedFields ?></b><br />
  <?php foreach ($this->updates as $update): ?>
    <?php echo sprintf($this->lang_updatedFieldMsg, $update->modified_attribute, $update->old_value, $update->new_value) ?><br />
  <?php endforeach; ?>
<?php endif;?>
  
        


