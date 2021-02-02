(function( $ ) {
	'use strict';

	var _mu_cron_loading = false;

	// 排程作業進度更新
	function mu_cron_progress_load() {
		console.log('mu_cron_progress_load');
		_mu_cron_loading = true;
		$.ajax({
			async: true,
			type: 'POST',
			url: mu_cron_admin.ajax_url,
			data: {
				action: 'mu_cron_progress_load'
			},
			dataType: 'json',
			success: function(res) {
				if (res.status != 0) {
					setTimeout(function(){
						mu_cron_progress_load(); //call itself
						console.log('tick!');
					}, 6000);
					console.log('mu_cron_progress_load: go next round!');
				}else{
					_mu_cron_loading = false;
					console.log('mu_cron_progress_load: stop!');
					console.log(res);
				}
				// 畫出進度表
				$('#display_cron_progress').html(res.data);
			},
			error:function (xhr, ajaxOptions, thrownError){
				_mu_cron_loading = false;
				alert(ajaxOptions+':'+thrownError);
			}
		});
	}

	$(function() {
		$(document).on('click', '.clear_log', function( e ){
			const the_btn = $(this);
			the_btn.prop('disabled', true);
			e.preventDefault();
			$.ajax({
				async: true,
				type: 'POST',
				url: mu_cron_admin.ajax_url,
				data: {
					action: 'clear_log'
				},
				dataType: 'json',
				success: function(res) {
					// console.log(res);
					if (res.data.done == true) {
						$( '.logger_table tr.data' ).remove();
					}
					the_btn.prop('disabled', false);
				},
				error:function (xhr, ajaxOptions, thrownError){
					alert(ajaxOptions+':'+thrownError);
					the_btn.prop('disabled', false);
				}
			});
		});


		mu_cron_progress_load();

		$('#test_progress').on('click', function(e){
			let event_id = '1';
			let cron_purp = 'test';
			let m = new Date();
			let datetime = m.getFullYear() +"-"+ (m.getMonth()+1) +"-"+ m.getDate() + " " + m.getHours() + ":" + m.getMinutes() + ":" + m.getSeconds();
			// let datetime = '2020-08-09 00:58:00';
			$.ajax({
				async: true,
				type: 'POST',
				url: mu_cron_admin.ajax_url,
				data: {
					action: 'mu_cron_enqueue',
					cron_slug: event_id+'-'+cron_purp,
					datetime: datetime
				},
				dataType: 'json',
				success: function(res) {
					console.log(res.text);
					// alert(res.text);
					if (!_mu_cron_loading) {
						mu_cron_progress_load();
					}
				},
				error:function (xhr, ajaxOptions, thrownError){
					_mu_cron_loading = false;
					alert(ajaxOptions+':'+thrownError);
				}
			});
		});

	});

})( jQuery );
