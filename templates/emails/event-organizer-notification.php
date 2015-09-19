<?php
/**
 * Event Organizer Email Notification
 *
 * @author 		Theunis Cilliers
 * @package 	Woocommerce-events-calendar-pro-organizer-mail/Templates/Emails
 * @version     1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
	global $lmbk_event_info;

	// Get basic colors from WooCommerce Settings
	$base = get_option( 'woocommerce_email_base_color' );
	$base_text = '#ffffff';
	if ( function_exists( 'wc_light_or_dark' ) ) $base_text = wc_light_or_dark( $base, '#202020', '#ffffff' );

	// Ready some variables
	$order_edit_link = $lmbk_event_info['order_link'];
	$order_meta = $lmbk_event_info['meta'];
	$show_links = $lmbk_event_info['show_links'];
?>

<?php do_action( 'woocommerce_email_header', $email_heading ); ?>
<?php 
	if ( $show_links ) { ?>
		<p>Click below to view the new order in which your ticket(s) appear.</p>
		<p>
			<a href="<?php echo $order_edit_link; ?>" style="display: inline-block; text-decoration: none; padding: 4px 10px; background: <?php
				echo $base; ?>; color: <?php echo $base_text; ?>;">View order</a>
		</p>
	<?php } else { ?>
		<p>There has been new ticket activity for events hosted by you:</p>
	<?php }

	foreach( $order_meta as $event_id => $event_meta ) {
		// Ready some variables
		$tickets = $event_meta['tickets'];
		$event_edit_link = $event_meta['edit_post'];
		$event_name = $event_meta['name'];
	?>
		<p style="text-decoration: underline;">For the event "<?php
			if ( $show_links ) echo '<a href="' . $event_edit_link . '">';
			echo $event_name;
			if ( $show_links ) echo '</a>';
		?>" the following tickets were ordered:</p>
		<?php foreach( $tickets as $ticket ) { ?>
			<p><?php echo $ticket['name'];?>: <?php echo $ticket['qty']; ?> ticket(s)</p>
		<?php }
	}
?>

<?php do_action( 'woocommerce_email_footer' ); ?>
