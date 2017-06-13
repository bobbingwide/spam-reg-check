<?php // (C) Copyright Bobbing Wide 2017

spam_reg_check_loaded();


/**
 * Runs spam registration checks against users
 */
function spam_reg_check_loaded() {
	//echo "Too early?". PHP_EOL;
	oik_require( "class-spam-reg-check.php", "spam-reg-check" );
	$src = new spam_reg_check();
}
