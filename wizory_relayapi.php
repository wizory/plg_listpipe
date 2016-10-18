<?php

// Joomla or DIE!
defined( '_JEXEC' ) or die( 'Restricted access' );

// TODO figure out how to load these using JLoader
require 'lib/RelayApi.php';
require 'lib/CmsInterface.php';
require 'lib/JoomlaCms.php';

use Wizory\RelayApi;
use Wizory\JoomlaCms;

class plgSystemWizory_Relayapi extends \JPlugin {

    // Plugin constructor
    function plgSystemWizory_Relayapi(&$subject, $params) {
        parent::__construct($subject, $params);

        #$this->actions = Listpipe::ACTIONS;

        $this->params = $params;
    }

    // NOTE this needs to remain very lightweight since it's called on *every* request
    public function onAfterRender() {

        # TODO determine a way to lock so only one simultaneous request is allowed to actually pull articles...
        # alternatively just check for articles by title before creating to avoid dupes

        # load our plugin's extension table data
        $table = new JTableExtension(JFactory::getDbo());
        $table->load(array('element' => 'wizory_relayapi'));

        $custom_data = json_decode($table->custom_data, true);  # true yields an associative array vs. object

        if (! isset($custom_data)) { $custom_data = array(); }

        # lock to prevent more than one simultaneous relayapi update process
        if ($table->get('checked_out_time') != '0000-00-00 00:00:00') {  # Joomla 3.6.2 isn't setting checked_out :(
            return;
        } else {
            $table->checkOut($this->params->user_id);
        }

        $config = json_decode($this->params['params']);

        $now = new Datetime();

        if (isset($custom_data['last_updated']['date'])) {
            $last_updated = DateTime::createFromFormat('Y-m-d H:i:s.000000', $custom_data['last_updated']['date']);

            $elapsed = $now->diff($last_updated);

            # if we haven't surpassed the update interval, bail
            if ($elapsed->h < $config->update_interval) {
                $table->checkIn();

                return;
            }
        }

        $cms = new JoomlaCms($config);

        $cms->log('init wizory_replayapi plugin...');

        $relayapi = new RelayApi($cms);

        $relayapi->update($custom_data['last_updated']['date']);

        $custom_data['last_updated'] = $now;

        $table->set('custom_data', json_encode($custom_data));

        $table->store();

        $table->checkIn();
    }
}
