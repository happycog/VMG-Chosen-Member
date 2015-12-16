<?php echo form_open($base_url)?>
    <input type="hidden" value="yes" name="convert_data_go" />
    <p style="font-weight:bold;">Click "Convert old field data" to automatically parse old field data in to VMG Chosen Member.</p>
    <p>This is needed when switching to VMG Chosen Member from a different field type that stores pipe-delimited member IDs.</p>
    <?php echo form_submit(array('name' => 'submit', 'value' => 'Convert old field data', 'class' => 'submit'));?>
<?php echo form_close()?>
