<?php // (C) Copyright Bobbing Wide 2017

spam_reg_check_loaded();


/**
 * Runs spam registration checks against users
 */
function spam_reg_check_loaded() {

  //add_action( 'init', 'spam_reg_check_init' );
	add_action( "run_spam-reg-check.php", "spam_reg_check_run" );
}

function spam_reg_check_init() {
	gob();
}
	
function spam_reg_check_run() {
	oik_require( "class-spam-reg-check.php", "spam-reg-check" );
	$src = new spam_reg_check();
}
