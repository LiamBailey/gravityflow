<?php

/**
 * Class Gravity_Flow_Web_API
 *
 * @copyright   Copyright (c) 2015-2017, Steven Henty S.L.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */
class Gravity_Flow_Web_API {

	public function __construct() {
		add_action( 'gform_webapi_get_entries_assignees', array( $this, 'get_entries_assignees' ), 10, 2 );
		add_action( 'gform_webapi_post_entries_assignees', array( $this, 'post_entries_assignees' ), 10, 2 );
		add_action( 'gform_webapi_get_forms_steps', array( $this, 'get_forms_steps' ) );
		add_action( 'gform_webapi_get_entries_steps', array( $this, 'get_entries_steps' ) );
	}

	public function get_entries_steps( $entry_id ) {

		$capability = apply_filters( 'gravityflow_web_api_capability_get_entries_steps', 'gravityflow_create_steps' );
		$this->authorize( $capability );

		$entry   = GFAPI::get_entry( $entry_id );
		$form_id = absint( $entry['form_id'] );
		$api     = new Gravity_Flow_API( $form_id );

		$form_steps = $api->get_steps();

		$current_step    = $api->get_current_step( $entry );
		$current_step_id = $current_step->get_id();
		$response        = array();

		foreach ( $form_steps as $form_step ) {
			$step            = $api->get_step( $form_step->get_id(), $entry );
			$is_current_step = ( $current_step_id == $step->get_id() );
			$response[]      = array(
				'id'                   => $step->get_id(),
				'type'                 => $step->get_type(),
				'label'                => $step->get_label(),
				'name'                 => $step->get_name(),
				'is_current_step'      => $is_current_step,
				'is_active'            => $step->is_active(),
				'supports_expiration'  => $step->supports_expiration(),
				'assignees'            => $this->get_assignees_array( $step ),
				'settings'             => $step->get_feed_meta(),
				'status'               => $is_current_step ? $step->evaluate_status() : rgar( $entry, 'workflow_step_status_' . $step->get_id() ),
				'expiration_timestamp' => $step->get_expiration_timestamp(),
				'is_expired'           => $step->is_expired(),
				'is_queued'            => $step->is_queued(),
				'entry_count'          => $step->entry_count(),
			);
		}

		$this->end( 200, $response );
	}

	public function get_forms_steps( $form_id ) {

		$capability = apply_filters( 'gravityflow_web_api_capability_get_forms_steps', 'gravityflow_create_steps' );
		$this->authorize( $capability );

		$api      = new Gravity_Flow_API( $form_id );
		$steps    = $api->get_steps();
		$response = array();
		foreach ( $steps as $step ) {
			$response[] = array(
				'id'                  => $step->get_id(),
				'type'                => $step->get_type(),
				'label'               => $step->get_label(),
				'name'                => $step->get_name(),
				'is_active'           => $step->is_active(),
				'entry_count'         => $step->entry_count(),
				'supports_expiration' => $step->supports_expiration(),
				'assignees'           => $this->get_assignees_array( $step ),
				'settings'            => $step->get_feed_meta(),
			);
		}
		$this->end( 200, $response );
	}

	public function get_entries_assignees( $entry_id, $assignee_key = null ) {

		$capability = apply_filters( 'gravityflow_web_api_capability_get_entries_assignees', 'gravityflow_create_steps' );
		$this->authorize( $capability );

		$entry   = GFAPI::get_entry( $entry_id );
		$form_id = absint( $entry['form_id'] );
		$api     = new Gravity_Flow_API( $form_id );

		$step = $api->get_current_step( $entry );
		if ( empty( $assignee_key ) ) {
			$response = $this->get_assignees_array( $step );
		} else {
			$assignee = new Gravity_Flow_Assignee( $assignee_key, $step );
			$response = $this->get_assignee_array( $assignee );
		}

		$this->end( 200, $response );
	}

	/**
	 * Processes a status update for a specified assignee of the current step of the specified entry.
	 *
	 * @param $entry_id
	 * @param string $assignee_key
	 */
	public function post_entries_assignees( $entry_id, $assignee_key = null ) {
		global $HTTP_RAW_POST_DATA;

		$capability = apply_filters( 'gravityflow_web_api_capability_post_entries_assignees', 'gravityflow_create_steps' );
		$this->authorize( $capability );

		$assignee_key = urldecode( $assignee_key );

		if ( empty( $assignee_key ) ) {
			$this->end( 400, 'Bad request' );
		}

		$entry = GFAPI::get_entry( $entry_id );

		if ( empty( $entry ) ) {
			$this->end( 404, 'Entry not found' );
		}

		$form_id = absint( $entry['form_id'] );
		$api     = new Gravity_Flow_API( $form_id );

		$step     = $api->get_current_step( $entry );
		$assignee = new Gravity_Flow_Assignee( $assignee_key, $step );

		if ( ! isset( $HTTP_RAW_POST_DATA ) ) {
			$HTTP_RAW_POST_DATA = file_get_contents( 'php://input' );
		}

		$data = json_decode( $HTTP_RAW_POST_DATA, true );

		$new_status = $data['status'];

		$form = GFAPI::get_form( $form_id );

		$step->process_assignee_status( $assignee, $new_status, $form );

		$api->process_workflow( $entry_id );

		$response = 'Status updated successfully';

		$this->end( 200, $response );
	}

	/**
	 * @param Gravity_Flow_Step|bool $step
	 *
	 * @return array
	 */
	public function get_assignees_array( $step ) {
		$assignees = $step && $step instanceof Gravity_Flow_Step ? $step->get_assignees() : array();

		$response = array();
		foreach ( $assignees as $assignee ) {
			$response[] = $this->get_assignee_array( $assignee );
		}

		return $response;
	}

	/**
	 * @param Gravity_Flow_Assignee $assignee
	 *
	 * @return array
	 */
	public function get_assignee_array( $assignee ) {
		return array(
			'key'          => $assignee->get_key(),
			'id'           => $assignee->get_id(),
			'type'         => $assignee->get_type(),
			'display_name' => $assignee->get_display_name(),
			'status'       => $assignee->get_status(),
		);
	}

	/**
	 * @param $status
	 * @param $response
	 */
	public function end( $status, $response ) {
		GFWebAPI::end( $status, $response );
	}


	/**
	 * @param array $caps
	 *
	 * @return bool
	 */
	public function authorize( $caps = array() ) {

		if ( GFCommon::current_user_can_any( $caps ) ) {
			return true;
		}

		$this->die_forbidden();
	}

	public function die_forbidden() {
		$this->end( 403, __( 'Forbidden', 'gravityforms' ) );
	}
}

new Gravity_Flow_Web_API();
