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
		$this->heading = 'Ticket(s) purchased';
		$this->subject = 'Ticket(s) purchased';

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
		$this->object = new WC_Order();
		$this->object->get_order( $order_id );

		$orderItems = $this->object->get_items();
		$productIDs = array();

		$organizersToNotify = array();
		$ticketLog = array();

		if ( class_exists(Tribe__Events__Tickets__Woo__Main) ) {
			$tribe_event = Tribe__Events__Tickets__Woo__Main::get_instance();
			foreach( $orderItems as $orderID => $orderMeta ) {
				$productID = $orderMeta['product_id'];
				$event_id = get_post_meta( $productID, '_tribe_wooticket_for_event', true );
				if ( !isset($ticketLog[$productID]) ) {
					$ticketLog[$productID] = array (
						'name' => get_the_title( $productID  ),
						'qty' => 0,
					);
				}
				$ticketLog[$productID]['qty'] = $ticketLog[$productID]['qty'] + $orderMeta['qty'];

				$eventMeta = tribe_get_event_meta( $event_id );
				$eventOrganizerIDs = $eventMeta['_EventOrganizerID'];
				foreach( $eventOrganizerIDs as $eventOrganizerID ) {
					$eventOrganizer = get_post( $eventOrganizerID );
					$eventOrganizerEmail = tribe_get_organizer_email( $eventOrganizerID );

					if ( !isset($organizersToNotify[$eventOrganizerEmail][$event_id] ) )
						$organizersToNotify[$eventOrganizerEmail][$event_id] = array(
							'name' => get_the_title($event_id),
							'edit_post' => get_edit_post_link($event_id),
							'tickets' => array(
								$productID => $ticketLog[$productID]
							)
						);
					else {
						$organizersToNotify[$eventOrganizerEmail][$event_id]['tickets'][$productID] = $ticketLog[$productID];
					}
				}
			}

			// replace variables in the subject/headings
			$this->find[] = '{order_date}';
			$this->replace[] = date_i18n( woocommerce_date_format(), strtotime( $this->object->order_date ) );

			$this->find[] = '{order_number}';
			$this->replace[] = $this->object->get_order_number();

			if ( ! $this->is_enabled() )
				return;

			foreach( $organizersToNotify as $organizer_email => $event_info ) {
				$GLOBALS['lmbk_event_info'] = array (
					'order_link' => get_edit_post_link( $order_id ),
					'meta' => $event_info
				);
				$this->send( $organizer_email, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );	
			}
		}		
	}


	/**
	 * get_content_html function.
	 *
	 * @since 0.1
	 * @return string
	 */
	public function get_content_html() {
		ob_start();
		if ( function_exists( 'woocommerce_get_template') ) {
			woocommerce_get_template( $this->template_html, array(
				'order'         => $this->object,
				'email_heading' => $this->get_heading()
			), ' ', WC_EVENT_ORGANIZER_TEMPLATE_DIR );
		}
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
		if ( function_exists( 'woocommerce_get_template') ) {
			woocommerce_get_template( $this->template_plain, array(
				'order'         => $this->object,
				'email_heading' => $this->get_heading()
			),  ' ', WC_EVENT_ORGANIZER_TEMPLATE_DIR );
		}
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

		$has_tickets = get_post_meta( $order, '_tribe_has_tickets', true );

		if ( ! $has_tickets ) {
			return $emails;
		}

		$emails[] = $this->id;
		return $emails;
	}


} // end \WC_Event_Organizer_Email class
