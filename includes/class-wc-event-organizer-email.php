<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * A custom Event Organizer Email
 *
 * @since 0.1
 * @extends \WC_Email
 */
class WC_Event_Organizer_Email extends WC_Email {


	/**
	 * Set email defaults
	 *
	 * @since 0.1
	 */
	public function __construct() {

		// set ID, this simply needs to be a unique name
		$this->id = 'wc_event_organizer_email_lmbk';

		// this is the title in WooCommerce Email settings
		$this->title = 'Event Organizer Email';

		// this is the description in WooCommerce email settings
		$this->description = 'Sent to the organizer of an event';

		// these are the default heading and subject lines that can be overridden using the settings
		$this->heading = 'A ticket has just been purchased';
		$this->subject = 'A ticket has just been purchased';

		// these define the locations of the templates that this email should use		
		$this->template_base = WC_EVENT_ORGANIZER_TEMPLATE_DIR;
		$this->template_html  = 'emails/event-organizer-notification.php';
		$this->template_plain = 'emails/plain/event-organizer-notification.php';

		add_action( 'woocommerce_resend_order_emails_available', array( $this, 'add_resend_event_organizer_action' ) );

		// Trigger on new paid orders
		//add_action( 'woocommerce_order_status_pending_to_processing_notification', array( $this, 'trigger' ) );
		//add_action( 'woocommerce_order_status_failed_to_processing_notification',  array( $this, 'trigger' ) );

		// Call parent constructor to load any other defaults not explicity defined here
		parent::__construct();

	}


	/**
	 * Determine if the email should actually be sent and setup email merge variables
	 *
	 * @since 0.1
	 * @param int $order_id
	 */
	public function trigger( $order_id ) {

		// bail if no order ID is present
		if ( ! $order_id )
			return;

		// setup order object
		$this->object = new WC_Order( $order_id );
		// $this->object->get_order( $order_id );
		// $recipient = get_bloginfo( 'admin_email' );
		$recipient = 'theunis@limbik.co.za';

		// replace variables in the subject/headings
		$this->find[] = '{order_date}';
		$this->replace[] = date_i18n( woocommerce_date_format(), strtotime( $this->object->order_date ) );

		$this->find[] = '{order_number}';
		$this->replace[] = $this->object->get_order_number();

		if ( ! $this->is_enabled() )
			return;

		$this->send( $recipient, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}


	/**
	 * get_content_html function.
	 *
	 * @since 0.1
	 * @return string
	 */
	public function get_content_html() {
		ob_start();
		woocommerce_get_template( $this->template_html, array(
			'order'         => $this->object,
			'email_heading' => $this->get_heading()
		) );
		return ob_get_clean();
	}


	/**
	 * get_content_plain function.
	 *
	 * @since 0.1
	 * @return string
	 */
	public function get_content_plain() {
		ob_start();
		woocommerce_get_template( $this->template_plain, array(
			'order'         => $this->object,
			'email_heading' => $this->get_heading()
		) );
		return ob_get_clean();
	}


	/**
	 * Initialize Settings Form Fields
	 *
	 * @since 2.0
	 */
	public function init_form_fields() {

		$this->form_fields = array(
			'enabled'    => array(
				'title'   => 'Active',
				'type'    => 'checkbox',
				'label'   => 'Enable this email notification',
				'default' => 'yes'
			),			
			'subject'    => array(
				'title'       => 'Email subject',
				'type'        => 'text',
				'description' => 'Defaults to <code>' . $this->subject . '</code>.',
				'placeholder' => '',
				'default'     => ''
			),
			'heading'    => array(
				'title'       => 'Email heading',
				'type'        => 'text',
				'description' => 'Defaults to <code>' . $this->heading . '</code>.',
				'placeholder' => '',
				'default'     => ''
			),
			'email_type' => array(
				'title'       => 'Email type',
				'type'        => 'select',
				'description' => 'Choose which format of email to send.',
				'default'     => 'html',
				'class'       => 'email_type',
				'options'     => array(
					'plain'	    => __( 'Plain text', 'woocommerce' ),
					'html' 	    => __( 'HTML', 'woocommerce' )
				)
			)
		);
	}

	/**
	 * Adds the option to resend this email from the Edit Order Screen
	 *
	 * @since 1.0.1
	 */
	public function add_resend_event_organizer_action( $emails ) {

		if ( ! $this->is_enabled() )
			return $emails;

		$order = get_the_ID();

		if ( empty( $order ) ) {
			return $emails;
		}

		$has_tickets = get_post_meta( $order, $this->order_has_tickets, true );

		if ( ! $has_tickets ) {
			return $emails;
		}

		$emails[] = $this->id;
		return $emails;
	}


} // end \WC_Event_Organizer_Email class
