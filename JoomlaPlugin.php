<?php

// Joomla or DIE!
defined( '_JEXEC' ) or die( 'Restricted access' );

use Wizory\Listpipe;
use Wizory\JoomlaCms;

class plgSystemWizory_ListPipe extends JPlugin {

    // Plugin constructor

    function plgSystemWizory_ListPipe(&$subject, $params) {
        parent::__construct($subject, $params);

        $this->actions = Listpipe::actions;
        $this->config = $params;
    }

    // NOTE this needs to remain very lightweight since it's called on *every* request
    public function onAfterRoute() {
        // if a supported action was requested
        if (in_array(JRequest::getVar('action'), $this->actions)) {
            $cms = new JoomlaCms();

            $listpipe = new Listpipe($cms);

            $listpipe->handleRequest($this->config, JRequest::get);
        }
    }
}
