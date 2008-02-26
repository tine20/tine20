** <?php echo $this->lead->lead_name . " **\n" ?>

<?php echo $this->lead->lead_description ?>


State: <?php echo $this->leadState->lead_leadstate . "\n"?>
Type: <?php echo $this->leadType->lead_leadtype . "\n" ?>
Source: <?php echo $this->leadSource->lead_leadsource . "\n" ?>

Start: <?php echo $this->leadStart . "\n" ?>
Scheduled end: <?php echo $this->leadScheduledEnd . "\n" ?>
End: <?php echo $this->leadEnd . "\n" ?>

Turnover: <?php echo $this->lead->lead_turnover . "\n" ?>
Probability: <?php echo $this->lead->lead_probability . "%\n" ?>

Folder: <?php echo $this->container->container_name . "\n" ?>
