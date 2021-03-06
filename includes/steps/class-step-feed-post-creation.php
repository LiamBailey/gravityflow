<?php
/**
 * Gravity Flow Step Feed Post Creation
 *
 *
 * @package     GravityFlow
 * @subpackage  Classes/Gravity_Flow_Step_Feed_Breeze
 * @copyright   Copyright (c) 2016-2017, Steven Henty S.L.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.4.3-dev
 */

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

class Gravity_Flow_Step_Feed_Post_Creation extends Gravity_Flow_Step_Feed_Add_On {
	public $_step_type = 'post_creation';

	protected $_class_name = 'GF_Post_Creation';

	public function get_label() {
		return 'Post Creation';
	}
}

Gravity_Flow_Steps::register( new Gravity_Flow_Step_Feed_Post_Creation() );
