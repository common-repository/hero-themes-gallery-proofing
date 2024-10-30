<?php
class HT_Gallery_Proofing_Vote {
	/**
     * Constructor
     */
    public function __construct($direction) {
    	$this->direction=$direction;
    	

    	// Retrieve user IP address 
    	if(array_key_exists('REMOTE_ADDR', $_SERVER)) {
    		$ip = $_SERVER['REMOTE_ADDR']; 
        	$this->ip = $ip;
	    } else {
	    	$this->ip = '';
	    }

	    //vote time/date 
	    $this->time = time();

	    //user
	    $current_user = wp_get_current_user();
	    if( is_a($current_user, 'WP_User') ) {
    		$this->user_id = $current_user->ID;
	    } else {
	    	$this->user_id = '0';
	    }

        
    }


} //end class

class HT_Gallery_Proofing_Upvote extends HT_Gallery_Proofing_Vote {
 	/**
     * Constructor
     */
    public function __construct() {
    	parent::__construct('up');
    }

} 


class HT_Gallery_Proofing_Downvote extends HT_Gallery_Proofing_Vote {
 	/**
     * Constructor
     */
    public function __construct() {
    	parent::__construct('down');
    }

} 