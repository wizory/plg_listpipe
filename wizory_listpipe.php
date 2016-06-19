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
        $this->config = $params;
    }

    // NOTE this needs to remain very lightweight since it's called on *every* request
    public function onAfterRoute() {
        // if a supported action was requested
        if (in_array(\JRequest::getVar('action'), $this->actions)) {
            $cms = new JoomlaCms($this->config);

            $listpipe = new Listpipe($cms);

            $listpipe->handleRequest(\JRequest::get());
        }
    }
}
