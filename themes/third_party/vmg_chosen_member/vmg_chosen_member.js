$(document).ready(function(){

	$('.vmg-chosen-member-container').each(function(){
		var current_field = $(this).find('.vmg-chosen-member');
        var unique_id = current_field.attr('rel');

		// Get out quick if no rel data
		if (unique_id == '') { return true; }

		var vmgcm_max_selections = $('#vmg_chosen_member_' + unique_id + '_max_selections').val();
		var vmgcm_placeholder_text = $('#vmg_chosen_member_' + unique_id + '_placeholder_text').val();
		var vmgcm_json_url = $(this).find('.vmg_chosen_member_json_url').val() + '&field_id=' + $('#vmg_chosen_member_' + unique_id + '_field_id').val();
		
		$('#vmg_chosen_member_' + unique_id).chosen(vmgcm_json_url, {
			max_selections: vmgcm_max_selections,
			placeholder_text: vmgcm_placeholder_text,
		});

		$('#hold_field_' + $('#vmg_chosen_member_' + unique_id + '_field_id').val()).css('overflow', 'visible');
	});
    
    Matrix.bind("vmg_chosen_member", "display", function(cell){
        if (cell.row.isNew) {
            var current_field = $(cell.dom.$td).find('.vmg-chosen-member');
            var unique_id = current_field.attr('rel') + '_new' + cell.row.index;
            
            // Get out quick if no rel data
            if (unique_id == '') { return true; }
            
            current_field.attr('id', 'vmg_chosen_member_' + unique_id);
            $('#vmg_chosen_member_' + current_field.attr('rel') + '_field_id').attr('id', 'vmg_chosen_member_' + unique_id + '_field_id');
            $('#vmg_chosen_member_' + current_field.attr('rel') + '_max_selections').attr('id', 'vmg_chosen_member_' + unique_id + '_max_selections');
            $('#vmg_chosen_member_' + current_field.attr('rel') + '_placeholder_text').attr('id', 'vmg_chosen_member_' + unique_id + '_placeholder_text');

            var vmgcm_max_selections = $('#vmg_chosen_member_' + unique_id + '_max_selections').val();
            var vmgcm_placeholder_text = $('#vmg_chosen_member_' + unique_id + '_placeholder_text').val();
            var vmgcm_json_url = $(this).find('.vmg_chosen_member_json_url').val() + '&field_id=' + $('#vmg_chosen_member_' + unique_id + '_field_id').val();
            
            $('#vmg_chosen_member_' + unique_id).chosen(vmgcm_json_url, {
                max_selections: vmgcm_max_selections,
                placeholder_text: vmgcm_placeholder_text,
            });

            $('#hold_field_' + $('#vmg_chosen_member_' + unique_id + '_field_id').val()).css('overflow', 'visible');
        }
    });

});