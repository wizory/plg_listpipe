<?php

namespace Wizory;

class Listpipe {

    const FAIL = 'fail';
    const OK = 'success';

    protected $is_draft;

    public function __construct() {
        $this->is_draft = False;
    }

    public function get_draft($draft_key, $approval_key, $blog_posting_id, $approve_type='', $debug=False) {
        // require all args to be set to non-empty values
        if (empty($draft_key) || empty($approval_key) || empty($blog_posting_id)) { return Listpipe::FAIL; }

        if ($approve_type == 'draft') {
            $this->is_draft = True;
        }
    }

    public function is_draft() {
        return $this->is_draft;
    }

}
