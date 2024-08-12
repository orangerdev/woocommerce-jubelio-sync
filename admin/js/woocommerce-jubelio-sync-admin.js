function bulk_all_sync_per_product() {

	jQuery(document).ready( function ($) {

		$('.bulk-all-sync-result-message').html('<b>Generate Per Product...</b>');

		var current_row = $(".current_row").val();
		// $(".sync-product-wr:nth-child("+current_row+")").show();
		var sync_status = '<span class="ui blue label">In Progress</span>';
		$(".sync-product-wr:nth-child("+current_row+") .sync-product-status").html(sync_status);
		var product_id = $(".sync-product-wr:nth-child("+current_row+") .sync-product").attr("data-product_id");

		console.log(current_row);
		console.log(product_id);

		if ( product_id ) {

			var formData = $('#bulk-all-sync-per-product-form').serialize();
			$.getJSON(ajaxurl, formData)
				.done(function(data) {	
					switch (data.status) {
						case 'COMPLETE':
							var current_row = $(".current_row").val();
							var sync_status = '<span class="ui green label">Success</span>';
							$(".sync-product-wr:nth-child("+current_row+") .sync-product-status").html(sync_status);
							$('.bulk-all-sync-result-message').html('<b>Done!</b>');
							$('.bulk-all-sync-now-btn').prop('disabled', false);

							var table_button_html = '<tr><td><p><a class="ui primary button" href="'+woo_jb_vars.product_sync_process_url+'">Kembali ke Table Jubelio Product Sync</a></p></td></tr>';
							$('.bulk-all-sync-result tbody').append(table_button_html);
							
							break;
						case 'NEXT':
							var current_row = $(".current_row").val();
							var sync_status = '<span class="ui green label">Success</span>';
							$(".sync-product-wr:nth-child("+current_row+") .sync-product-status").html(sync_status);
							var next_row = parseInt(current_row)+1;
							if ( $(".sync-product-wr:nth-child("+next_row+")").length > 0 ) {
								var product_id = $(".sync-product-wr:nth-child("+next_row+") .sync-product").attr("data-product_id");
							} else {
								var product_id = 0;
							}
							$(".current_row").val(next_row);
							$(".product_id").val(product_id);
							if ( product_id ) {
								// setTimeout(function(){
									bulk_all_sync_per_product();
								// }, 2000);
							} else {
								// setTimeout(function(){
									// bulk_all_sync_get_products();
								// }, 2000);

								var current_row = $(".current_row").val();
								var sync_status = '<span class="ui green label">Success</span>';
								$(".sync-product-wr:nth-child("+current_row+") .sync-product-status").html(sync_status);
								$('.bulk-all-sync-result-message').html('<b>Done!</b>');
								$('.bulk-all-sync-now-btn').prop('disabled', false);

								var table_button_html = '<tr><td><p><a class="ui primary button" href="'+woo_jb_vars.product_sync_process_url+'">Kembali ke Table Jubelio Product Sync</a></p></td></tr>';
								$('.bulk-all-sync-result tbody').append(table_button_html);
								
							}
							break;
						case 'FAIL':
							var current_row = $(".current_row").val();
							var sync_status = '<span class="ui red label">Error</span>';
							$(".sync-product-wr:nth-child("+current_row+") .sync-product-status").html(sync_status);
							$('.bulk-all-sync-result-message').html('<b>Fail!</b>');
							$('.bulk-all-sync-now-btn').prop('disabled', false);

							var table_button_html = '<p><a class="ui primary button" href="'+woo_jb_vars.product_sync_process_url+'">Kembali ke Table Jubelio Product Sync</a></p>';
							$('.bulk-all-sync-result tbody').append(table_button_html);

							break;
					}
			}).fail(function(data, status, error) {
				var current_row = $(".current_row").val();
				var sync_status = '<span class="ui red label">Error</span>';
				$(".sync-product-wr:nth-child("+current_row+") .sync-product-status").html(sync_status);
				$('.bulk-all-sync-result-message').html('<b>jQuery AJAX Failed!</b>');

				var table_button_html = '<tr><td><p><a class="ui primary button" href="'+woo_jb_vars.product_sync_process_url+'">Kembali ke Table Jubelio Product Sync</a></p></td></tr>';
				$('.bulk-all-sync-result tbody').append(table_button_html);

			});

		} else {

			var current_row = $(".current_row").val();
			var sync_status = '<span class="ui red label">Error</span>';
			$(".sync-product-wr:nth-child("+current_row+") .sync-product-status").html(sync_status);

			var table_button_html = '<p><a class="ui primary button" href="'+woo_jb_vars.product_sync_process_url+'">Kembali ke Table Jubelio Product Sync</a></p>';
			$('.bulk-all-sync-result').append(table_button_html);

		}

	});

}

(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	$(document).ready( function () {

		var product_sync_table = $('#product-sync-table').DataTable({
			lengthMenu: [
				[50, 100, 200, 500],
				[50, 100, 200, 500],
			],
            pageLength: 50,
			processing: true,
			serverSide: true,
			oLanguage: {
				"sSearch": "Name or SKU"
			},
			ajax: {
				url: ajaxurl,
				type: 'post',
				data: function(params) {
					params.action = 'woo_jb_get_products_sync';
					params.security = woo_jb_vars.woo_jb_get_products_sync.ajax_nonce;
					return params;
				}
			},
			order: [[2, 'asc']],
			columns: [
				{
					className: 'dt-control',
					orderable: false,
					data: null,
					defaultContent: '',
				},
				{ 
					data: 'woo_id',
					orderable: false,
					render: function ( data, type, row, meta ) {
						var html = '';
						html = '<input type="checkbox" name="select_item[]" class="select_item" value="'+row.jb_id+'"> '+row.woo_id;
						return html;
					} 
				},
				{ 
					data: 'jb_id' 
				},
				{ 
					data: 'name' 
				},
				{ 
					data: 'stock',
					orderable: false, 
				},
				{ 
					orderable: false,
					data: 'thumbnail',
					render: function ( data, type, row, meta ) {
						var html = '';
						if ( data ) {
							html = '<img class="jb-item-image" src="'+data+'">';
						}
						return html;
					}  
				},
				{ 
					orderable: false,
					data: 'category' 
				},
				{ 
					orderable: false,
					data: 'status_sync', 
					render: function ( data, type, row, meta ) {
						var html = '';
						if ( data === 'Sudah' ) {
							html = '<span class="ui green label">Sudah</span>';
						} else {
							html = '<span class="ui red label">Belum</span>';
						}
						return html;
					}  
				}
			],
			createdRow: function (row, data, index) {
				if ( data.status_sync === 'Sudah' ) {
					var status_class = 'item-status-sync item-status-sync-done';
				} else {
					var status_class = 'item-status-sync item-status-sync-not-yet';
				}

				if ( status_class ) {

					var tampilkan_status_sync = $('#tampilkan_status_sync').val();
					if ( tampilkan_status_sync === 'Semua' ) {
						status_class += " item-status-sync-show";
					} else if ( tampilkan_status_sync === 'Sudah' ) {
						if ( data.status_sync === 'Sudah' ) {
							status_class += " item-status-sync-show";
						} else {
							status_class += " item-status-sync-hide";
						}
					} else if ( tampilkan_status_sync === 'Belum' ) {
						if ( data.status_sync === 'Belum' ) {
							status_class += " item-status-sync-show";
						} else {
							status_class += " item-status-sync-hide";
						}
					}
					
					$(row).addClass(status_class);
				}
			}
		});

		function formatSubRow(d) {

			var variants = d.variants;
			var data = '';
			for (let index = 0; index < variants.length; index++) {
				const element = variants[index];
					data += '<tr>' +
						'<td>'+element.variant_value+'</td>' +
						'<td>'+element.sku+'</td>' +
						'<td>'+element.harga+'</td>' +
						'<td>'+element.stok+'</td>' +
					'</tr>';
			}
			return (
				'<table class="ui small table">' +
					'<thead>' +
						'<tr>' +
							'<th>Varian</th>' +
							'<th>SKU</th>' +
							'<th>Harga</th>' +
							'<th>Stok</th>' +
						'</tr>' +
					'</thead>' +
					'<tbody>' + data + '</tbody>' +
				'</table>'
			);
		}

		$('#product-sync-table tbody').on('click', 'td.dt-control', function () {
			var tr = $(this).closest('tr');
			var row = product_sync_table.row(tr);		
			if (row.child.isShown()) {
				row.child.hide();
				tr.removeClass('shown');
			} else {
				row.child( formatSubRow( row.data() ) ).show();
				tr.addClass('shown');
			}
		});

		var order_sync_table = $('#order-sync-table').DataTable({
			lengthMenu: [
				[50, 100, 200, 500],
				[50, 100, 200, 500],
			],
            pageLength: 50,
			processing: true,
			serverSide: true,
			oLanguage: {
				"sSearch": "Woo ID"
			},
			ajax: {
				url: ajaxurl,
				type: 'post',
				data: function(params) {
					params.action = 'woo_jb_get_orders_sync';
					params.security = woo_jb_vars.woo_jb_get_orders_sync.ajax_nonce;
					return params;
				}
			},
			columns: [
				{ 
					data: 'woo_id',
					orderable: false,
					render: function ( data, type, row, meta ) {
						var html = '';
						if ( row.jb_id ) {
							html = row.woo_link;
						} else {
							html = '<input type="checkbox" name="select_item[]" class="select_item" value="'+data+'"> '+row.woo_link;
						}
						return html;
					} 
				},
				{ 
					data: 'jb_id' 
				},
				{ 
					data: 'customer'
				},
				{ 
					data: 'qty'
				},
				{ 
					data: 'total'
				},
				{ 
					data: 'date'
				},
				{ 
					data: 'status_sync'
				}
			]
		});

		$(document).on('change','.select_all',function(e){

			e.preventDefault();

			if ( $(this).prop('checked') ) {
				$('.select_item').prop('checked',true);
			} else {
				$('.select_item').prop('checked',false);
			}

		});
		
		$(document).on('click','.bulk-sync-btn',function(e){

			e.preventDefault();

			var item_ids = [];
			$('.item-status-sync-show .select_item:checked').each(function() {
				item_ids.push(this.value);
			});
			console.log(item_ids);

			if ( item_ids.length < 1 ) {

				alert('Silahkan ceklis produk yang akan di sinkronasi terlebih dulu');

			} else {

				var process_url = woo_jb_vars.product_sync_process_url;

				var ids = item_ids.toString();
				process_url += '&item_ids='+ids;
				window.location.replace(process_url);

				console.log(process_url);	

				// var formData = new FormData();
				// formData.append('item_ids',item_ids);
				// formData.append('action','woo_jb_bulk_sync_products');
				// formData.append('security',woo_jb_vars.woo_jb_bulk_sync_products.ajax_nonce);
	
				// $.ajax({
				// 	url: ajaxurl,
				// 	type: 'post',
				// 	processData: false,
				// 	contentType: false,
				// 	data: formData,
				// 	beforeSend: function() {
				// 		$('.bulk-sync-btn').prop('disabled', true);
				// 		$('.dataTables_processing').css({'display':'block'});
				// 	},
				// 	success: function(response) {

				// 		alert(response.message);

				// 		$('.dataTables_processing').css({'display':'none'});
				// 		console.log(response);
				// 		$('.bulk-sync-btn').prop('disabled', false);
	
				// 		product_sync_table.ajax.reload();
				// 	}
				// });
	
			}

		});

		$(document).on('click','.woo-jb-sync-products-btn',function(e){

			e.preventDefault();

			var formData = new FormData();
			formData.append('action','woo_jb_sync_products2');
			formData.append('security',woo_jb_vars.woo_jb_sync_products2.ajax_nonce);

			var lastResponseLength = false;
			$.ajax({
				url: ajaxurl,
				type: 'post',
				processData: false,
				contentType: false,
				data: formData,
				xhrFields: {
					onprogress: function (e) {
						// $("#progress").html(e.target.responseText);
						// console.log(e.target.responseText);
					}
					
				},
				beforeSend: function() {
					$("#progress").html("");
					$('.woo-jb-sync-products-btn').prop('disabled', true);
				},
				success: function(response) {
					console.log(response);
					$("#progress").html(response + "<p>All Done.</p>");
					$('.woo-jb-sync-products-btn').prop('disabled', false);


				}
			});
	
		});

		$(document).on('click','.bulk-sync-order-btn',function(e){

			e.preventDefault();

			var item_ids = [];
			$('.select_item:checked').each(function() {
				item_ids.push(this.value);
			});
			console.log(item_ids);

			if ( item_ids.length < 1 ) {

				alert('Silahkan ceklis order yang akan di sinkronasi terlebih dulu');

			} else {

				var formData = new FormData();
				formData.append('item_ids',item_ids);
				formData.append('action','woo_jb_bulk_sync_orders');
				formData.append('security',woo_jb_vars.woo_jb_bulk_sync_orders.ajax_nonce);
	
				$.ajax({
					url: ajaxurl,
					type: 'post',
					processData: false,
					contentType: false,
					data: formData,
					beforeSend: function() {
						$('.bulk-sync-order-btn').prop('disabled', true);
						$('.dataTables_processing').css({'display':'block'});
					},
					success: function(response) {
						$('.dataTables_processing').css({'display':'none'});
						console.log(response);
						$('.bulk-sync-order-btn').prop('disabled', false);
	
						order_sync_table.ajax.reload();
					}
				});
	
			}

		});

		// function bulk_all_sync_get_products() {

		// 	var start = parseInt($('.current_row').val())-1;
		// 	$('.start').val(start);

		// 	var form = document.getElementById('bulk-all-sync-get-products-form');
		// 	var formData = new FormData(form);

		// 	$.ajax({
		// 		url: ajaxurl,
		// 		type: 'post',
		// 		processData: false,
		// 		contentType: false,
		// 		data: formData,
		// 		beforeSend: function() {
		// 			$('.bulk-all-sync-result-message').html('<b>Get Products...</b>');
		// 		},
		// 		success: function(response) {
		// 			console.log(response);

		// 			var products = [];
		// 			if ( response.products ) {
		// 				products = response.products;
		// 			}

		// 			if ( products ) {

		// 				var total = 0;
		// 				if ( response.total ) {
		// 					total = response.total;
		// 				}
	
		// 				var total_all = 0;
		// 				if ( response.total_all ) {
		// 					total_all = response.total_all;
		// 					$('.total_all_row').val(total_all);
		// 				}

		// 				$('.bulk-all-sync-result-message').html('Berhasil get '+total+' dari '+total_all+' Products');

		// 				var no = 1;
		// 				if ( $('.sync-product-wr').length > 0 ) {
		// 					no = $('.sync-product-wr').length;
		// 					no++;
		// 				}

		// 				var current_row = no;
		// 				var product_id = 0;
		// 				var i = 0;
		// 				products.forEach(function(product, index, arr){
		// 					if ( i == 0 ) {
		// 						product_id = product.id;
		// 					}
		// 					$('.bulk-all-sync-result').append('<tr class="sync-product-wr"><td class="sync-product sync-product-'+product.id+'" data-product_id="'+product.id+'">'+no+'. <span class="sync-product-status"><span class="ui orange label">Waiting</span></span> Generate Product '+product.name+'</td></tr>');
		// 					no++;
		// 					i++;
		// 				});

		// 				$(".current_row").val(current_row);
		// 				$(".product_id").val(product_id);

		// 				// setTimeout(function(){

		// 					bulk_all_sync_per_product();

		// 				// }, 2000);

		// 			} else {

		// 				$('.bulk-all-sync-result-message').html('<b>Gagal mendapatkan produk dari Jubelio</b>');

		// 			}

		// 		}
		// 	});

		// }

		// $(document).on('click','.bulk-all-sync-now-btn',function(e){

		// 	e.preventDefault();

		// 	$('.bulk-all-sync-now-btn').prop('disabled', true);
		// 	$('.bulk-all-sync-result-message').html('<tr><td><b>Start...</b></td></tr>');
		// 	$('.bulk-all-sync-result').html('');

		// 	bulk_all_sync_get_products();

		// });

		$(document).on('change','#tampilkan_status_sync',function(e){

			e.preventDefault();

			var val = $(this).val();

			$('.item-status-sync').removeClass("item-status-sync-hide");
			$('.item-status-sync').removeClass("item-status-sync-show");

			if ( val === 'Semua' ) {
				$('.item-status-sync-done').addClass("item-status-sync-show");
				$('.item-status-sync-not-yet').addClass("item-status-sync-show");
			} else if ( val === 'Sudah' ) {
				$('.item-status-sync-done').addClass("item-status-sync-show");
				$('.item-status-sync-not-yet').addClass("item-status-sync-hide");
			} else if ( val === 'Belum' ) {
				$('.item-status-sync-done').addClass("item-status-sync-hide");
				$('.item-status-sync-not-yet').addClass("item-status-sync-show");
			}

		} );

	} );

})( jQuery );