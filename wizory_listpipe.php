<?php

// Joomla or DIE!
defined( '_JEXEC' ) or die( 'Restricted access' );

// TODO figure out how to load these using JLoader
require 'lib/Listpipe.php';
require 'lib/CmsInterface.php';
require 'lib/JoomlaCms.php';

use Wizory\Listpipe;
use Wizory\JoomlaCms;

class plgSystemWizory_Listpipe extends \JPlugin {

    // Plugin constructor
    function plgSystemWizory_Listpipe(&$subject, $params) {
        parent::__construct($subject, $params);

        $this->actions = Listpipe::ACTIONS;

        $this->params = $params;
    }

    // NOTE this needs to remain very lightweight since it's called on *every* request
    public function onAfterRoute() {
        // if a supported action was requested
        if (in_array(\JRequest::getVar('action'), $this->actions)) {

            $config = json_decode($this->params['params']);

            $cms = new JoomlaCms($config);

            $cms->log('init listpipe plugin...cms instantiated with config: ' . print_r($cms->config, true));

            $listpipe = new Listpipe($cms);

            $cms->log('sending request to listpipe handler: ' . print_r(\JRequest::get(), true));

            $listpipe->handleRequest(\JRequest::get());
        }
    }
}
