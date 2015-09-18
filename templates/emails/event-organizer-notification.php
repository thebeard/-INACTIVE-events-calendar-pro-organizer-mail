<?php
/**
 * Event Organizer Email Notification
 *
 * @author 		Limbik
 * @package 	Woocommerce-events-calendar-pro-organizer-mail/Templates/Emails
 * @version     1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
	global $lmbk_event_info;
	$base            = get_option( 'woocommerce_email_base_color' );
	$base_text       = '#ffffff';
	if ( function_exists( 'wc_light_or_dark' ) ) $base_text       = wc_light_or_dark( $base, '#202020', '#ffffff' );
?>

<?php do_action( 'woocommerce_email_header', $email_heading ); ?>

<p>One of your events have just sold a ticket. Click below to view the order in which these tickets appear.</p>
<p>
	<a href="<?php echo $lmbk_event_info['order_link']; 
	?>" style="display: inline-block; text-decoration: none; padding: 4px 10px; background: <?php echo $base; ?>; color: <?php echo $base_text; ?>;">View order</a>
</p>
<?php
	foreach( $lmbk_event_info['meta'] as $event_id => $event_meta ) { ?>
		<p style="text-decoration: underline;">For the event <a href="<?php echo $event_meta['edit_post']; ?>">"<?php echo $event_meta['name']; ?>"</a> the following tickets were sold:</p>
		<?php foreach( $event_meta['tickets'] as $ticket ) { ?>
			<p><?php echo $ticket['name'];?>: <?php echo $ticket['qty']; ?> ticket(s)</p>
		<?php }
	}
?>

<?php do_action( 'woocommerce_email_footer' ); ?>
