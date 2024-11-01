(function( bbytes_support, $, undefined ) {

	bbytes_support.update_olark_online_status = function(status) {
		if(typeof status !== 'undefined') {

			var is_online = status == 'online';
			var $link = $("#bbytes-support-footer .bbytes-lf-slider .bbytes-chat");
			$link.attr('title', is_online ? 'Agent is online' : 'Chat is not currently available');

			$link.off('click.chat-link');
			$link.on('click.chat-link', function(e) {
				if( is_online ) {
					window.open(bbytes_support_cfg['chat-url'], 'bbytes-chat', 'height=600px,width=600px,menubar=no,resizable=yes,scrollbars=no,status=no,toolbar=no');
				}
				e.preventDefault();
				e.stopPropagation();
				return;
			});

			var $chat_status = $("#bbytes-support-footer .bbytes-lf-slider .chat-status");
			$chat_status.removeClass('chat-online chat-offline');
			$chat_status.addClass('chat-' + status);

			var $chat_status_text = $("#bbytes-support-footer .bbytes-lf-slider .chat-status-text");
			$chat_status_text.text( (is_online ? 'Online' : status.charAt(0).toUpperCase() + status.substr(1)).toUpperCase() );
		}
	}

	bbytes_support.check_olark_online_status = function() {
		var data = {
			'action' : 'check_olark_status'
		};
		$.post(ajaxurl, data, function(response) {
			bbytes_support.update_olark_online_status(response.status);
		}, 'json');
	}
})( window.bbytes_support = window.bbytes_support || {}, jQuery );

jQuery(document).ready(function($) {
	setInterval(bbytes_support.check_olark_online_status, bbytes_support_cfg['check_online_poll_interval']);

	$("#bbytes-support-footer .bbytes-lf-grabber").mouseover(function() {
		if($(this).hasClass('active')) {
			var on = '3px';
		} else {
			var on = '7px';
		}
		$(this).find("i").stop().animate({bottom: on},'fast');
	}).mouseout(function() {
		if($(this).hasClass('active')) {
			var off = '7px';
		} else {
			var off = '3px';
		}
		$(this).find("i").stop().animate({bottom: off},'fast');
	});

	$("#bbytes-support-footer .bbytes-lf-grabber").click(function() {
		if($(this).hasClass('active')) {
			$("#bbytes-support-footer .bbytes-lf-slider").slideUp();
			$(this).find("i").each(function() {
				$(this).removeClass('fa-chevron-down');
				$(this).addClass('fa-chevron-up');
			});
			$(this).removeClass('active');
		} else {
			$("#bbytes-support-footer .bbytes-lf-slider").slideDown();
			$(this).find("i").each(function() {
				$(this).removeClass('fa-chevron-up');
				$(this).addClass('fa-chevron-down');
			});
			$(this).addClass('active');
		}
	});

	$("#bbytes-support-footer .bbytes-lf-slider .bbytes-lf-hideme a").click(function(e) {
		$("#bbytes-support-footer").hide();
		e.preventDefault();
	});
});
