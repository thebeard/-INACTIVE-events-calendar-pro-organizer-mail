(function($) {
	$(document).ready( function() {
		otherState.init();
	});

	var otherState = {
		init : function() {
			this.showOrHide();
			$('#woocommerce_wc_event_organizer_email_lmbk_recipient').change( function() {
				otherState.update();
			});
		},
		is : function () {
			return $('#woocommerce_wc_event_organizer_email_lmbk_recipient').hasClass('other');
		},
		setParentState : function( showState ) {
			if ( showState == 'undefined' || showState == true ) $('#woocommerce_wc_event_organizer_email_lmbk_recipient').closest('tr').removeClass('hide-next');
			else $('#woocommerce_wc_event_organizer_email_lmbk_recipient').closest('tr').addClass('hide-next');
		},
		showOrHide: function() {
			this.setParentState( this.is() );
		},
		update: function() {
			var selectElement = $('#woocommerce_wc_event_organizer_email_lmbk_recipient');
			if ( selectElement.val() == 'recipient_other' ) selectElement.addClass('other');
			else selectElement.removeClass('other');
			this.showOrHide();
		}		
	}

} )( jQuery);