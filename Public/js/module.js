/**
 * Module's JavaScript.
 */

// AI button in reply editor.
var AiEditorButton = function (context) {
	var ui = $.summernote.ui;

	// create button
	var button = ui.buttonGroup([
		// We have to create button inside button group to have tooltip separate for button
	    ui.button({
	        className: 'dropdown-toggle',
	        contents: 'AI <span class="note-icon-caret"></span>',
	        tooltip: Lang.get("messages.aii_assist"),
	        container: 'body',
        	data: {
                toggle: 'dropdown'
            }
	    }),
	    ui.dropdown({
            className: 'aii-editor-actions',
            items: $('#aii_editor_items').html()
        })
	]);

	var obj = button.render();

	return obj;
}

if ($("body:first").hasClass('aii-active')) {
	fs_conv_editor_buttons['ai'] = AiEditorButton;
	fs_conv_editor_toolbar[0][1].push('ai');
}

function aiiInit()
{
	$(document).ready(function() {
		// Summarize
		$("#aii_summarize").click(function(e){
	    	aiiSummarize($(this))
	    });

	    $('.aii-editor-actions li').click(function(e){
	    	aiiAssist($(this));
	    	e.preventDefault();
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

		// Select2 allowing to enter custom value and select only one element
		$("#aiintegration_model").select2({
			dropdownCssClass: "select2-multi-dropdown",
			tags: true
		}).on('select2:selecting', function(e) {
			// Allow only one element to be selected.
			//var data = e.params.data;
			if ($("#aiintegration_model").val().length) {
				$("#aiintegration_model").val('');
			    // e.preventDefault();
			    // return false;
			}
		// Without this existing models are not shown in the dropdown list
		}).trigger('change');

		// Load models
		$("#aii_load_models").click(function(e){

			if ($('#aiintegration_model').attr('disabled')) {
				// Already loading.
				return;
			}

	    	var api_key = $('#aiintegration_api_key').val();
			var selected_model = $('#aiintegration_model').val();

	    	/*if (!api_key) {
				aiiCleanModels(selected_model);
	    		return;
	    	}*/
			
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
	var container = modal.children().find('.aii-draft:first');
	var input = container.find('.aii-draft-instructions:first');

	function send() {
		aiiDraftReply(modal, $.trim(input.val()));
	}

	container.find('.aii-draft-send:first').click(function(e) {
		send();
		e.preventDefault();
	});

	input.keydown(function(e) {
		if (e.keyCode == 13) {
			send();
			e.preventDefault();
		}
	});

	// Collapse / expand a draft
	container.on('click', '.aii-draft-toggle', function(e) {
		var item = $(this).closest('.aii-draft-item');
		item.toggleClass('aii-draft-collapsed');
		item.find('.aii-draft-caret:first').text(item.hasClass('aii-draft-collapsed') ? '▸' : '▾');
		e.preventDefault();
	});

	if (container.attr('data-auto-draft') == '1') {
		aiiDraftReply(modal, '');
	} else {
		input.focus();
	}
}

function aiiDraftReply(modal, instructions)
{
	var container = modal.children().find('.aii-draft:first');
	var drafts = container.find('.aii-drafts:first');
	var loader = container.find('.modal-loader:first');
	var composer = container.find('.aii-draft-composer:first');
	var input = composer.find('.aii-draft-instructions:first');
	var send_button = composer.find('.aii-draft-send:first');

	if (container.data('aii-busy')) {
		return;
	}
	container.data('aii-busy', true);

	function done() {
		container.data('aii-busy', false);
		loader.addClass('hidden');
		input.prop('disabled', false);
		send_button.prop('disabled', false);
		composer.removeClass('hidden');
	}

	drafts.children('.aii-draft-error').remove();
	input.prop('disabled', true);
	send_button.prop('disabled', true);
	loader.removeClass('hidden');

	fsAjax(
		{
			action: 'draft_reply',
			conversation_id: container.attr('data-conversation-id'),
			instructions: instructions,
			// Last draft is passed only when the agent asks for changes
			previous_draft: instructions ? (container.data('aii-last-reply') || '') : ''
		},
		laroute.route('aiintegration.ajax'), 
		function(response) {
			done();

			if (typeof(response.html) != "undefined") {
				var item = $('<div class="aii-draft-item"></div>');

				if (isAjaxSuccess(response)) {
					var number = drafts.children('.aii-draft-item').length+1;
					var title = number+'. '+(instructions || container.attr('data-label-draft'));
					var toggle = $('<a href="#" class="aii-draft-toggle text-help"><span class="aii-draft-caret">▾</span> <span class="aii-draft-title"></span></a>');

					toggle.find('.aii-draft-title').text(title);
					item.append(toggle).append($('<div class="aii-draft-body"></div>').append(response.html));

					// Keep only the newest draft expanded
					drafts.children('.aii-draft-item').addClass('aii-draft-collapsed')
						.find('.aii-draft-caret').text('▸');
					drafts.append(item);
					item[0].scrollIntoView({block: 'start'});

					container.data('aii-last-reply', item.find('.aii-generated-reply-hidden:first').text());
					input.val('');
					aiiShowDraftReply(item, modal);
				} else {
					// Error is shown in the window, instructions are kept to retry
					item.addClass('aii-draft-error').append(response.html);
					drafts.append(item);
				}
			} else {
				showAjaxError(response);
			}
			input.focus();
		}, true,
		function() {
			done();
			showFloatingAlert('error', Lang.get("messages.ajax_error"));
		}
	);
}

function aiiShowDraftReply(item, modal)
{
	// Show reply text
	var i = 0;
	var text = item.find('.aii-generated-reply-hidden:first').text();
	var cur_text = '';
	var container = item.find('.aii-generated-reply:first');
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
	item.find('.aii-reply-apply:first').click(function(e) {
		var body = container.text();
		
		if ($(".conv-reply-block").hasClass('conv-note-block')) {
			hideReplyEditor();
		}
		showReplyForm();
		setReplyBody(aiiNewLineToBr(htmlEscape(body)));

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
				$('#aii_summary').html(aiiNewLineToBr(response.summary)).removeClass('hidden');
			} else {
				showAjaxError(response);
			}
		}, true
	);
}

function aiiAssist(button)
{
	var sub_action = button.attr('data-action');
	if (!sub_action) {
		button = button.children(":first");
		sub_action = button.attr('data-action');
	}

	// Show modal
	var modal_html = '<div>'+
		'<div class="text-center modal-loader"><img src="'+Vars.public_url+'/modules/aiintegration/img/loader.svg'+'" /></div>'+
		'<div class="aii-assist-result aii-result hidden"></div>'+
		'<div class="form-group margin-top aii-assist-buttons hidden">'+
			'<button class="btn btn-warning aii-assist-ok"><i class="glyphicon glyphicon-ok"></i> '+Lang.get("messages.aii_use")+'</button>'+
			'<button class="btn btn-link" data-dismiss="modal">'+Lang.get("messages.cancel")+'</button>'+
		'</div>'+
		'</div>';

		showModalDialog(modal_html, {
			size: 'md',
			width_auto: false,
			no_header: false,
			title: button.text(),
			class: 'aii-modal-reply',
			on_show: function(modal) {
				// Prepare text
				fsAjax(
					{
						action: 'assist',
						sub_action: sub_action,
						body: getReplyBody()
					},
					laroute.route('aiintegration.ajax'), 
					function(response) {
						button.button('reset');

						if (isAjaxSuccess(response) && typeof(response.body) != "undefined") {
							modal.children().find('.modal-loader:first').hide();
							modal.children().find('.aii-assist-result').html(response.body).removeClass('hidden');
							modal.children().find('.aii-assist-buttons:first').removeClass('hidden');
						} else {
							showAjaxError(response);
						}
					}, true
				);

				// Ok
				modal.children().find('.aii-assist-ok:first').click(function(e) {
					modal.modal('hide');
					setReplyBody(modal.children().find('.aii-assist-result').html());
					e.preventDefault();
				});
			}
		});
}