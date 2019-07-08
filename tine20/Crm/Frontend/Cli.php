<?php
/**
 * Tine 2.0
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Cli frontend for Crm
 *
 * This class handles cli requests for the Crm
 *
 * @package     Crm
 */
class Crm_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     * 
     * @var string
     */
    protected $_applicationName = 'Crm';
    /**
     * import demodata default definitions
     *
     * @var array
     */
    protected $_defaultDemoDataDefinition = [
        'Crm_Model_Lead' => 'crm_demo_import_csv'
    ];

    /**
     * usage: tine20-cli --method=Crm.migrateProjectsToLeads [-d] [-v] -- container_id=abcde124345
     *
     * @param Zend_Console_Getopt $opts
     * @return integer
     */
    public function migrateProjectsToLeads(Zend_Console_Getopt $opts)
    {
        $args = $this->_parseArgs($opts, ['container_id']);

        $dryRun = $opts->d;
        $verbose = $opts->v;
        if ($dryRun) {
            echo "Dry run activated\n";
        }

        // fetch all projects
        $projects = Projects_Controller_Project::getInstance()->search();
        echo "Got " . count($projects) . " projects to migrate.\n";

        foreach ($projects as $project) {
            $project = Projects_Controller_Project::getInstance()->get($project->getId());
            if ($verbose) {
                echo "Migrating project: " . print_r($project->toArray(), true) . "\n";
            }

            // create leads with project data
            $lead = new Crm_Model_Lead([
                'lead_name' => empty($project->number) ? $project->title : $project->number . ' - ' . $project->title,
                'description' => $project->description,
                'container_id' => $args['container_id'],
                'start' => $project->creation_time,
                'leadtype_id' => 1, // Customer
                'leadsource_id' => 4, // Website
                'leadstate_id' => 1, // open
            ]);

            // TODO might need to be adjusted
            switch ($project->status) {
                case 'NEEDS-ACTION':
                    $lead->leadstate_id = 3;
                    break;
                case 'COMPLETED':
                    $lead->leadstate_id = 2;
                    break;
                case 'CANCELLED':
                    $lead->leadstate_id = 4;
                    break;
                case 'IN-PROCESS':
                    $lead->leadstate_id = 1;
                    break;
            }

            $lead->relations = new Tinebase_Record_RecordSet(Tinebase_Model_Relation::class);
            // convert project members to lead contacts (relations)
            foreach ($project->relations as $relation) {
                if ($relation->type == 'COWORKER' || $relation->type == 'RESPONSIBLE') {
                    $leadRelation = $relation;
                    unset($leadRelation->id);
                    unset($leadRelation->own_id);
                    $leadRelation->own_model = 'Crm_Model_Lead';
                    $leadRelation->type = $relation->type != 'RESPONSIBLE' ? 'CUSTOMER' : $relation->type;
                    $lead->relations->addRecord($leadRelation);
                }
            }

            // copy attachments
            $lead->attachments = new Tinebase_Record_RecordSet(Tinebase_Model_Tree_Node::class);
            foreach ($project->attachments as $attachment) {
                $tempFile = Tinebase_TempFile::getInstance()->createTempFileFromNode($attachment);
                $leadAttachment = new Tinebase_Model_Tree_Node([
                    'name'      => $attachment->name,
                    'tempFile'  => $tempFile
                ], true);
                $lead->attachments->addRecord($leadAttachment);
            }

            // tags
            $leadTags = [];
            foreach ($project->tags as $tag) {
                $leadTags[] = $tag['id'];
            }
            $lead->tags = $leadTags;

            if ($verbose) {
                echo "Create lead: " . print_r($lead->toArray(), true);
            }
            if (! $dryRun) {
                $migratedLead = Crm_Controller_Lead::getInstance()->create($lead);

                // set notes
                $notes = Tinebase_Notes::getInstance()->getNotesOfRecord(
                    'Projects_Model_Project',
                    $project->getId(),
                    'Sql',
                    false
                );
                foreach ($notes as $note) {
                    unset($note->id);
                    $note->record_id = $migratedLead->getId();
                    $note->record_model = 'Crm_Model_Lead';
                    Tinebase_Notes::getInstance()->addNote($note, true);
                }

                // set lead creator + creation time
                $leadBE = Crm_Controller_Lead::getInstance()->getBackend();
                $migratedLead->created_by = $project->created_by;
                $migratedLead->creation_time = $project->creation_time;
                $leadBE->update($migratedLead);
            }
        }
        echo "Migration complete.\n";
        return 0;
    }
}
