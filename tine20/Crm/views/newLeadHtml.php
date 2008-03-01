<h1><?php echo $this->lead->lead_name ?></h1>
<?php echo nl2br($this->lead->lead_description) ?>

<br>

<table>
  <tr>
    <td><?php echo $this->lang_state ?></td>
    <td><?php echo $this->leadState->lead_leadstate ?></td>
  </tr>
  <tr>
    <td><?php echo $this->lang_type ?></td>
    <td><?php echo $this->leadType->lead_leadtype ?></td>
  </tr>
  <tr>
    <td><?php echo $this->lang_source ?></td>
    <td><?php echo $this->leadSource->lead_leadsource ?></td>
  </tr>
  <tr>
    <td><?php echo $this->lang_start ?></td>
    <td><?php echo $this->leadStart ?></td>
  </tr>
  <tr>
    <td><?php echo $this->lang_scheduledEnd ?></td>
    <td><?php echo $this->leadScheduledEnd ?></td>
  </tr>
  <tr>
    <td><?php echo $this->lang_end ?></td>
    <td><?php echo $this->leadEnd ?></td>
  </tr>
  <tr>
    <td><?php echo $this->lang_turnover ?></td>
    <td><?php echo $this->lead->lead_turnover ?></td>
  </tr>
  <tr>
    <td><?php echo $this->lang_probability ?></td>
    <td><?php echo $this->lead->lead_probability ?>%</td>
  </tr>
  <tr>
    <td><?php echo $this->lang_folder ?></td>
    <td><?php echo $this->container->container_name ?></td>
  </tr>
  <tr>
    <td><?php echo $this->lang_updatedBy ?></td>
    <td><?php echo $this->updater->accountDisplayName ?></td>
  </tr>
</table>
