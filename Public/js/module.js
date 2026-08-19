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

		$("#aiintegration_model").select2({...fs_select2_config, ...{
			maximumSelectionLength: 1
		}});
	});
}
