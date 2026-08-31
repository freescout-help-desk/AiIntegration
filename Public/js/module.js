/**
 * Module's JavaScript.
 */

function aiiInit()
{
	$(document).ready(function() {
		// Summarize
		$("#aii_summarize").click(function(e){
	    	aiiSummarize($(this))
	    });
	});
}

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

		// When Provider is changed, just clear the list of models.
		$("#aiintegration_provider").change(function(e){
			aiiCleanModels($('#aiintegration_model').val());
		});
		// Load models
		$("#aiintegration_api_key,#aiintegration_base_url").change(function(e){
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
		});
		// Without this existing models are not shown in the dropdown list
		$("#aiintegration_model").select2().trigger('change');

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
						button.button('reset');
						if (typeof(response.models) != "undefined") {

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

	if (typeof(selected_model) != "undefined") {
		selected_model += '';
		if (selected_model) {
			html += '<option value="'+selected_model+'" selected="selected">'+selected_model+'</option>';
		}
	}
	$('#aiintegration_model').html(html)
		.select2()
		.trigger('change');
}

function aiiDraftReplyModal(modal)
{
	// Show reply text
	var i = 0;
	var text = $('.modal-body:visible .aii-generated-reply-hidden:first').text();
	var cur_text = '';
	var container = $('.aii-generated-reply:visible:first');
	function typeWriter() {
		if (i < text.length) {
			cur_text += text.charAt(i);
			container.text(cur_text);
			i++;
			setTimeout(typeWriter, 10);
		}
	}
	typeWriter();

	// Apply reply
	modal.children().find('.aii-reply-apply:first').click(function(e) {
		var body = $('.aii-generated-reply:visible').text();
		
		if ($(".conv-reply-block").hasClass('conv-note-block')) {
			hideReplyEditor();
		}
		showReplyForm();
		setReplyBody(aiiNewLineToBr(body));

		modal.modal('hide')
		e.preventDefault();
	});
}

function aiiNewLineToBr(str)
{
	return str.replace(/(?:\r\n|\r|\n)/g, '<br>');
}

function aiiSummarize(button)
{
	if (!$('#aii_summary').hasClass('hidden')) {
		$('#aii_summary').addClass('hidden');
		return;
	}

	if ($('#aii_summary').text()) {
		$('#aii_summary').removeClass('hidden');
		return;
	}

	button.button('loading');
	
	fsAjax(
		{
			action: 'summarize',
			conversation_id: getGlobalAttr('conversation_id')
		},
		laroute.route('aiintegration.ajax'), 
		function(response) {
			button.button('reset');

			if (isAjaxSuccess(response) && typeof(response.summary) != "undefined") {
				// Show Summary
				$('#aii_summary').text(response.summary).removeClass('hidden');
			} else {
				showAjaxError(response);
			}
		}, true
	);
}