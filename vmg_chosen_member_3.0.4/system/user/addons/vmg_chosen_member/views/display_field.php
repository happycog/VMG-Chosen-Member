<div class="vmg-chosen-member-container">
	<input type="hidden" name="<?=$field_name?>">
	<select id="vmg_chosen_member_<?=$unique_id?>" name="<?=$field_name?>[]" <?=($max_selections != 1 ? 'multiple ' : '')?>style="width: 100%;" class="vmg-chosen-member" rel="<?=$unique_id?>">
		<?php if(VMG_JS_TYPE != "select2"){ ?>
		<option value="__empty__" selected="selected"></option>
		<?php } ?>
		<?php foreach($member_associations AS $member): ?>
			<option value="<?=$member['member_id']?>" selected="selected"><?=$member['screen_name']?></option>
		<?php endforeach; ?>
	</select>
	<input type="hidden" id="vmg_chosen_member_<?=$unique_id?>_field_id" value="<?=$field_id?>"/>
	<input type="hidden" id="vmg_chosen_member_<?=$unique_id?>_col_id" value="<?=$col_id?>"/>
	<input type="hidden" id="vmg_chosen_member_<?=$unique_id?>_var_id" value="<?=$var_id?>"/>
	<input type="hidden" id="vmg_chosen_member_<?=$unique_id?>_max_selections" value="<?=$max_selections?>"/>
	<input type="hidden" id="vmg_chosen_member_<?=$unique_id?>_placeholder_text" value="<?=$placeholder_text?>"/>
	<input type="hidden" class="vmg_chosen_member_json_url" value="<?=$json_url?>"/>
</div>
