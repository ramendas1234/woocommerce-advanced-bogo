<?php
/**
 * Display a message.
 *
 * @var array $field Field data.
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;
?>
<p class="description">
	<?php echo esc_html( $field['value'] ); ?>
</p>
