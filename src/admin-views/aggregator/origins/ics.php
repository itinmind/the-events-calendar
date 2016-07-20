<?php
$field              = (object) array();
$field->label       = __( 'Choose File', 'the-events-calendar' );
$field->placeholder = __( 'Choose File', 'the-events-calendar' );
$field->help        = __( 'Select your ICS file from the WordPress media library. You may need to first upload the file from your computer to the library.', 'the-events-calendar' );
$field->source      = 'ical_files';
$field->button      = __( 'Upload new File', 'the-events-calendar' );
$field->media_title = __( 'Upload an ICS File', 'the-events-calendar' );
?>
<tr class="tribe-dependent" data-depends="#tribe-ea-field-origin" data-condition="ical">
	<th scope="row">
		<label for="tribe-ea-field-file"><?php echo esc_html( $field->label ); ?></label>
	</th>
	<td>
		<input
			name="aggregator[ical][file]"
			type="hidden"
			id="tribe-ea-field-ical_file"
			class="tribe-ea-field tribe-ea-size-large"
			placeholder="<?php echo esc_attr( $field->placeholder ); ?>"
		>
		<button
			class="tribe-ea-field tribe-ea-media_button tribe-dependent button button-secondary"
			data-input="tribe-ea-field-ical_file"
			data-media-title="<?php echo esc_attr( $field->media_title ); ?>"
			data-mime-type="text/calendar"
			data-depends="#tribe-ea-field-ical_file"
			data-condition-empty
		>
			<?php echo esc_html( $field->button ); ?>
		</button>
		<span class="tribe-bumpdown-trigger tribe-bumpdown-permanent tribe-ea-help dashicons dashicons-editor-help" data-bumpdown="<?php echo esc_attr( $field->help ); ?>"></span>
	</td>
</tr>

<tr class="tribe-dependent" data-depends="tribe-ea-field-ical_file" data-condition-not-empty>
	<td colspan="2">
		<div class="tribe-ea-table-container">
			<span class='spinner tribe-ea-active'></span>
		</div>
	</td>
</tr>