<?php
/**
 * Gravity Flow Step Feed Sprout Invoices
 *
 *
 * @package     GravityFlow
 * @subpackage  Classes/Gravity_Flow_Step_Feed_Sprout_Invoices
 * @copyright   Copyright (c) 2016-2017, Steven Henty S.L.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.4.3-dev
 */

if ( ! class_exists( 'GFForms' ) ) {
	die();
}


class Gravity_Flow_Step_Feed_Sprout_Invoices extends Gravity_Flow_Step_Feed_Add_On {
	public $_step_type = 'sprout_invoices';
	protected $_slug = 'gravityformssproutinvoices';

	/**
	 * The ID of the form the Form Integrations plugin is configured to work with.
	 *
	 * @var bool|int
	 */
	protected $_estimate_form_id = false;

	/**
	 * The ID of the form the Invoice Submissions plugin is configured to work with.
	 *
	 * @var bool|int
	 */
	protected $_invoice_form_id = false;

	public function get_label() {
		return 'Sprout Invoices';
	}

	public function get_icon_url() {
		return $this->get_base_url() . '/images/sproutapps-icon.png';
	}

	public function is_supported() {
		$form_id = $this->get_form_id();

		return $this->is_estimates_supported( $form_id ) || $this->is_invoices_supported( $form_id );
	}

	/**
	 * Check that the Form Integrations plugin is active and it is configured to work with the current form.
	 *
	 * @param int $form_id The ID of the current form.
	 *
	 * @return bool
	 */
	public function is_estimates_supported( $form_id ) {
		$is_supported = class_exists( 'SI_Form_Integrations' );

		if ( ! $is_supported ) {
			return false;
		}

		if ( ! $this->_estimate_form_id ) {
			$this->_estimate_form_id = get_option( SI_Form_Integrations::GRAVITY_FORM_ID );
		}

		return $form_id == $this->_estimate_form_id;
	}

	/**
	 * Check that the Invoice Submissions plugin is active and it is configured to work with the current form.
	 *
	 * @param int $form_id The ID of the current form.
	 *
	 * @return bool
	 */
	public function is_invoices_supported( $form_id ) {
		$is_supported = class_exists( 'SI_IS_Gravity_Forms' );

		if ( ! $is_supported ) {
			return false;
		}

		if ( ! $this->_invoice_form_id ) {
			$this->_invoice_form_id = get_option( SI_IS_Gravity_Forms::GRAVITY_FORM_ID );
		}

		return $form_id == $this->_invoice_form_id;
	}

	/**
	 * The Form Integrations and Invoice Submissions add-ons do not extend the GF add-on framework so lets return dummy feeds for them.
	 *
	 * @return array
	 */
	public function get_feeds() {
		$form_id = $this->get_form_id();

		return array(
			array(
				'id'         => 'estimate',
				'form_id'    => $form_id,
				'is_active'  => $this->is_estimates_supported( $form_id ),
				'meta'       => array(
					'feedName' => esc_html__( 'Create Estimate', 'gravityflow' ),
				),
				'addon_slug' => $this->_step_type,
			),
			array(
				'id'         => 'invoice',
				'form_id'    => $form_id,
				'is_active'  => $this->is_invoices_supported( $form_id ),
				'meta'       => array(
					'feedName' => esc_html__( 'Create Invoice', 'gravityflow' ),
				),
				'addon_slug' => $this->_step_type,
			),
		);
	}

	public function process_feed( $feed ) {
		$form  = $this->get_form();
		$entry = $this->get_entry();

		if ( $feed['id'] == 'estimate' && $this->is_estimates_supported( $form['id'] ) ) {
			SI_Form_Integrations::maybe_process_gravity_form( $entry, $form );
		}

		if ( $feed['id'] == 'invoice' && $this->is_invoices_supported( $form['id'] ) ) {
			SI_IS_Gravity_Forms::maybe_process_gravity_form( $entry, $form );
		}

		return true;
	}

	public function intercept_submission() {
		$form_id = $this->get_form_id();

		if ( $this->feed_estimate && $this->is_estimates_supported( $form_id ) ) {
			remove_action( 'gform_after_submission', array( 'SI_Form_Integrations', 'maybe_process_gravity_form' ) );
		}

		if ( $this->feed_invoice && $this->is_invoices_supported( $form_id ) ) {
			remove_action( 'gform_entry_created', array( 'SI_IS_Gravity_Forms', 'maybe_process_gravity_form' ) );
			remove_filter( 'gform_confirmation_' . $form_id, array( 'SI_IS_Gravity_Forms', 'maybe_redirect_after_submission' ) );
		}
	}

}

Gravity_Flow_Steps::register( new Gravity_Flow_Step_Feed_Sprout_Invoices() );
