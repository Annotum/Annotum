<?php 
/**
 * Text widget class
 *
 * @since 2.8.0
 */
class WP_Widget_Solvitor_Ad extends WP_Widget_Text {

	function WP_Widget_Solvitor_Ad() {
		$widget_ops = array('classname' => 'widget_solvitor_ad', 'description' => __('A sidebar advertisement', 'anno'));
		$control_ops = array('width' => 400, 'height' => 350);
		$this->WP_Widget('advertisement', __('Advertisement', 'anno'), $widget_ops, $control_ops);
	}
	
	function widget( $args, $instance ) {
		extract($args);
		$text = apply_filters( 'widget_solvitor_ad', $instance['text'], $instance );
		echo $before_widget; ?>
		<div class="textwidget"><?php echo $instance['filter'] ? wpautop($text) : $text; ?></div>
		<?php
		echo $after_widget;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'text' => '' ) );
		$text = esc_textarea($instance['text']);
?>

		<textarea class="widefat" rows="16" cols="20" id="<?php echo $this->get_field_id('text'); ?>" name="<?php echo $this->get_field_name('text'); ?>"><?php echo $text; ?></textarea>

<?php
	}
}