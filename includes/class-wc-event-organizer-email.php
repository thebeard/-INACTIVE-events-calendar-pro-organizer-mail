<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * A custom Event Organizer Email
 *
 * @since 1.0.1
 * @extends \WC_Email
 */
class WC_Event_Organizer_Email extends WC_Email {

	/**
	 * Set Constructor Function
	 *
	 * @since 1.0.1
	 */
	public function __construct() {

		// Instantiating properties
		$this->id = 'wc_event_organizer_email_lmbk';
		$this->title = 'Event Organizer Email';
		$this->description = 'Sent to the organizer of an event';

		// Instatntiating default properties, overridden in WooCommerce Settings
		$this->heading = 'Ticket(s) purchased';
		$this->subject = 'Ticket(s) purchased';

		// Template locations defined
		$this->template_base = WC_EVENT_ORGANIZER_TEMPLATE_DIR;
		$this->template_html  = 'emails/event-organizer-notification.php';
		$this->template_plain = 'emails/plain/event-organizer-notification.php';

		// Adds an option to resend this mail from Order Edit Screen
		add_action( 'woocommerce_resend_order_emails_available', array( $this, 'add_resend_event_organizer_action' ) );

		// Trigger on newly paid orders
		//add_action( 'woocommerce_order_status_pending_to_processing_notification', array( $this, 'trigger' ) );
		//add_action( 'woocommerce_order_status_failed_to_processing_notification',  array( $this, 'trigger' ) );

		// Parent Constructor
		parent::__construct();
	}


	/**
	 * Determine if the email should actually be sent and setup email merge variables
	 *
	 * @since 1.0.1
	 * @param int $order_id
	 */
	public function trigger( $order_id ) {

		// Exit if no order id present
		if ( ! $order_id ) return;

		// If this order has no tickets, exit
		$has_tickets = get_post_meta( $order_id, '_tribe_has_tickets', true );
		if ( ! $has_tickets ) return $emails; 

		// Instantiate Order Object
		$this->object = new WC_Order();
		$this->object->get_order( $order_id );

		// Set up a few variables for use later
		$orderItems = $this->object->get_items();
		$organizersToNotify = array();
		$ticketLog = array();

		// Make sure Tribe Events Plugin is activated
		if ( class_exists( 'Tribe__Events__Tickets__Woo__Main' ) ) {
			$tribe_event = Tribe__Events__Tickets__Woo__Main::get_instance();

			// Loop over order items / tickets
			foreach( $orderItems as $orderID => $orderMeta ) {

				$productID = $orderMeta['product_id'];
				$event_id = get_post_meta( $productID, '_tribe_wooticket_for_event', true );

				// If this is not an event ticket, continue
				if ( !$event_id ) continue;

				// If this ticket has not been added to our custom array, add now
				if ( !isset($ticketLog[$productID]) ) {
					$ticketLog[$productID] = array (
						'name' => get_the_title( $productID  ),
						'qty' => 0,
					);
				}

				// Update this ticket's quantity ( In case multiple lines of the same ticket exists)
				$ticketLog[$productID]['qty'] = $ticketLog[$productID]['qty'] + $orderMeta['qty'];

				// Get Event Meta Array and all Organizers added for this event
				$eventMeta = tribe_get_event_meta( $event_id );
				$eventOrganizerIDs = $eventMeta['_EventOrganizerID'];

				// Loop over Organizers for this event
				foreach( $eventOrganizerIDs as $eventOrganizerID ) {

					// Collect this Organizer's email address
					$eventOrganizerEmail = tribe_get_organizer_email( $eventOrganizerID );

					// If the event, associated with this looping line item ( product ) has not yet been added to the array...
					if ( !isset($organizersToNotify[$eventOrganizerEmail][$event_id] ) )
						$organizersToNotify[$eventOrganizerEmail][$event_id] = array(
							'name' => get_the_title($event_id),
							'edit_post' => get_edit_post_link($event_id),
							'tickets' => array(
								$productID => $ticketLog[$productID]
							)
						);
					else { // ... just add the the ticket(s) to already existing events
						$organizersToNotify[$eventOrganizerEmail][$event_id]['tickets'][$productID] = $ticketLog[$productID];
					}
				}
			}

			// Replace variables in the subject/headings
			$this->find[] = '{order_date}';
			$this->replace[] = date_i18n( woocommerce_date_format(), strtotime( $this->object->order_date ) );

			$this->find[] = '{order_number}';
			$this->replace[] = $this->object->get_order_number();

			// Exit if his email is not enabled ( Could possible move to the top? )
			if ( ! $this->is_enabled() )
				return;

			// Now loop over all collected organizers and send each one email for all tickets assigned to this organizer
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
