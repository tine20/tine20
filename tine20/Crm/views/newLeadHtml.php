<h1><?php echo $this->lead->lead_name ?></h1>
<?php echo nl2br($this->lead->lead_description) ?>

<br>

<table>
  <tr>
    <td>State</td>
    <td><?php echo $this->leadState->lead_leadstate ?></td>
  </tr>
  <tr>
    <td>Type</td>
    <td><?php echo $this->leadType->lead_leadtype ?></td>
  </tr>
  <tr>
    <td>Source</td>
    <td><?php echo $this->leadSource->lead_leadsource ?></td>
  </tr>
  <tr>
    <td>Start</td>
    <td><?php echo $this->leadStart ?></td>
  </tr>
  <tr>
    <td>Scheduled end</td>
    <td><?php echo $this->leadScheduledEnd ?></td>
  </tr>
  <tr>
    <td>End</td>
    <td><?php echo $this->leadEnd ?></td>
  </tr>
  <tr>
    <td>Turnover</td>
    <td><?php echo $this->lead->lead_turnover ?></td>
  </tr>
  <tr>
    <td>Probability</td>
    <td><?php echo $this->lead->lead_probability ?>%</td>
  </tr>
  <tr>
    <td>Folder</td>
    <td><?php echo $this->container->container_name ?></td>
  </tr>
</table>
