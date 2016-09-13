<?php

use Phalcon\Mvc\Model;
use Phalcon\Validation;
use Phalcon\Validation\Validator\PresenceOf;

class inventorylinux extends Model 
{
	public $hostname;		// Hostname of the server
	public $ip_addr; 		// IP Address of the server
	public $os_release;		// Release of Linux OS

	// Validations
    public function validation(){

		$validation = new Validation();

		$validation->add(
	    	'hostname',
		    new PresenceOf(
		        array(
		            'hostname' => 'The hostname is required'
		        )
		    )
		);

		$validation->add(
	        'ip_addr',
	        new PresenceOf(
	            array(
	                'ip_addr' => 'The IP Address is required'
	            )
	        )
	    );

		$validation->add(
	        'os_release',
	        new PresenceOf(
	            array(
	                'os_release' => 'The OS Relase is required'
	            )
	        )
	    );

	    // Check if any messages have been produced
        if ($this->validationHasFailed() == true) {
            return false;
        }
	}
}
