<h1> Leadname: <?php echo $this->lead->lead_name ?> </h1>
<h2>Description:</h2>
<?php echo nl2br($this->lead->lead_description) ?>

<br><br>

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
    <td><?php echo $this->lead->lead_start ?></td>
  </tr>
  <tr>
    <td>Scheduled end</td>
    <td><?php echo $this->lead->lead_end_scheduled ?></td>
  </tr>
  <tr>
    <td>End</td>
    <td><?php echo $this->lead->lead_end ?></td>
  </tr>
  <tr>
    <td>Turnover</td>
    <td><?php echo $this->lead->lead_turnover ?></td>
  </tr>
  <tr>
    <td>Probability</td>
    <td><?php echo $this->lead->lead_probability ?></td>
  </tr>
  <tr>
    <td>Folder</td>
    <td><?php echo $this->lead->lead_container ?></td>
  </tr>
</table>
