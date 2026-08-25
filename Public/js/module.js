/**
 * Module's JavaScript.
 */
function aiiInitSettings()
{
	$(document).ready(function() {
		$("#aiintegration_provider").change(function(e){
			var value = $(this).val();

			var base_url = $(this).children('[value='+value+']').attr('data-aii-base-url');

			if (base_url) {
				$('#aii_default_base_url_value').text(base_url);
				$('#aii_default_base_url').show();
			} else {
				$('#aii_default_base_url').hide();
			}
		});

		$("#aiintegration_provider,#aiintegration_api_key,#aiintegration_base_url").change(function(e){
			$("#aii_load_models").click();
		});

		$("#aiintegration_model").select2({...fs_select2_config, ...{
			maximumSelectionLength: 1
			//multiple: false
		}}).on('select2:selecting', function(e) {
			// Allow only one element to be selected.
			//var data = e.params.data;
			if ($("#aiintegration_model").val().length) {
				$("#aiintegration_model").val('');
			    // e.preventDefault();
			    // return false;
			}
		});;

		// Load models
		$("#aii_load_models").click(function(e){

			if ($('#aiintegration_model').attr('disabled')) {
				// Already loading.
				return;
			}

	    	var api_key = $('#aiintegration_api_key').val();
			var selected_model = $('#aiintegration_model').val();

	    	if (!api_key) {
				aiiCleanModels(selected_model);
	    		return;
	    	}
			
			var button = $(this);
	    	button.button('loading');

	    	$('#aiintegration_model').attr('disabled', 'disabled');
			fsAjax(
				{
					action: 'load_models',
					provider: $('#aiintegration_provider').val(),
					api_key: api_key,
					base_url: $('#aiintegration_base_url').val()
				},
				laroute.route('aiintegration.ajax_admin'), 
				function(response) {
					if (isAjaxSuccess(response)) {
						if (typeof(response.models) != "undefined") {
							button.button('reset');

							if (selected_model.length) {
								selected_model = selected_model[0];
							} else {
								selected_model = '';
							}
							// Load options.
							var html = '';
							var first_val = '';
							for (var i in response.models) {
								var model = htmlEscape(response.models[i]);
								if (!first_val) {
									first_val = model;
								}
								html += '<option value="'+model+'">'+model+'</option>';
							}
							if (!selected_model && first_val) {
								selected_model = first_val;
							}
							$('#aiintegration_model').html(html)
								.val(selected_model)
								.select2()
								.trigger('change');
						}
					} else {
						showAjaxError(response);
						button.button('reset');
						aiiCleanModels(selected_model);
					}
					$('#aiintegration_model').removeAttr('disabled');
				}, true, 
				function() {
					showAjaxError(response);
					button.button('reset');
					$('#aiintegration_model').removeAttr('disabled');
				}
			);
		});
	});
}

function aiiCleanModels(selected_model)
{
	var html = '';
	if (selected_model) {
		html += '<option value="'+selected_model+'" selected="selected">'+selected_model+'</option>';
	}
	$('#aiintegration_model').html(html)
		.select2()
		.trigger('change');
}