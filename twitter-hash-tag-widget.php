<?php
/*
Plugin Name: Twitter Hash Tag Widget
Plugin URI: http://webdevstudios.com/support/wordpress-plugins/
Description: A widget for displaying the most recent twitter status updates for a particular hash tag.
Author: Brad Williams
Author URI: http://webdevstudios.com/
Version: 1.1

        Copyright (c) 2009-2010 Brad Williams (http://webdevstudios.com/)
        Twitter Hash Tag Widget is released under the GNU General Public License (GPL)
        http://www.gnu.org/licenses/gpl-2.0.txt
*/

class WP_Widget_Twitter_Hash_Tag extends WP_Widget {

	function WP_Widget_Twitter_Hash_Tag() {
		$widget_ops = array('description' => __( 'Real time Twitter hash tag following' ) );
		$this->WP_Widget('twitter_hash_tag', __( 'Twitter Hash Tag' ), $widget_ops);
	}

	function widget( $args, $instance ) {
		extract($args, EXTR_SKIP);

		$api_url = 'http://search.twitter.com/search.json';

        $title = ! empty($instance['title']) ? esc_attr($instance['title']) : false;
        $hashtag = ! empty($instance['hashtag']) ? ltrim(esc_attr($instance['hashtag']), '#@') : 'wordpress';
        $number = ! empty($instance['number']) ? $instance['number'] : 3;
		$images = $instance['images'];

        echo $before_widget;
        if ( $title )
            echo $before_title . "<a href='http://search.twitter.com/search?q=%23$hashtag'>$title</a>" . $after_title;

		$raw_response = wp_remote_get("$api_url?q=%23$hashtag&rpp=$number");

		if ( is_wp_error($raw_response) ) {
			$output = "<p>Failed to update from Twitter!</p>\n";
			$output .= "<!--{$raw_response->errors['http_request_failed'][0]}-->\n";
			$output .= get_option('twitter_hash_tag_cache');
		} else {
			if ( function_exists('json_decode') ) {
				$response = get_object_vars(json_decode($raw_response['body']));
				for ( $i=0; $i < count($response['results']); $i++ ) {
					$response['results'][$i] = get_object_vars($response['results'][$i]);
				}
			} else {
				include(ABSPATH . WPINC . '/js/tinymce/plugins/spellchecker/classes/utils/JSON.php');
				$json = new Moxiecode_JSON();
				$response = @$json->decode($raw_response['body']);
			}

			$output = "<ul class='twitter-hash-tag-widget'>\n";
			foreach ( $response['results'] as $result ) {
		   		$text = $result['text'];
			    $user = $result['from_user'];
			    $image = $result['profile_image_url'];
			    $user_url = "http://twitter.com/$user";
				$source_url = "$user_url/status/{$result['id']}";

		    	$text = preg_replace('|(https?://[^\ ]+)|', '<a href="$1">$1</a>', $text);
			    $text = preg_replace('|@(\w+)|', '<a href="http://twitter.com/$1">@$1</a>', $text);
			    $text = preg_replace('|#(\w+)|', '<a href="http://search.twitter.com/search?q=%23$1">#$1</a>', $text);

			    $output .= "<li>";

				if ( $images )
					$output .= "<a href='$user_url'><img src='$image' alt='$user' /></a>";
				$output .= "<a href='$user_url'>$user</a>: $text <a href='$source_url'>&raquo;</a></li>\n";
			}
			$output .= "<li class='view-all'><a href='http://search.twitter.com/search?q=%23$hashtag'>" . __('View All') . "</a></li>\n";
			$output .= "</ul>\n";
		}

		if ( ! is_wp_error($raw_response) )
			update_option('twitter_hash_tag_cache', $output);

		echo $output;

		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$new_instance = (array) $new_instance;
		$instance['title'] = ! empty($new_instance['title']) ? esc_attr($new_instance['title']) : '';
		$instance['hashtag'] = ! empty($new_instance['hashtag']) ? ltrim(esc_attr($new_instance['hashtag']), '#@') : 'wordpress';
		$instance['number'] = ! empty($new_instance['number']) ? $new_instance['number'] : 3;
		$instance['images'] = ! empty($new_instance['images']) ? true : false;
        return $instance;
	}

	function form( $instance ) {
        $title = ! empty($instance['title']) ? esc_attr($instance['title']) : '';
        $hashtag = ! empty($instance['hashtag']) ? ltrim(esc_attr($instance['hashtag']), '#@') : 'wordpress';
        $number = ! empty($instance['number']) ? $instance['number'] : 3;
        $images = ! empty($instance['images']) ? $instance['images'] : true;

?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

        <p><label for="<?php echo $this->get_field_id('hashtag'); ?>"><?php _e('Hash Tag:'); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('hashtag'); ?>" name="<?php echo $this->get_field_name('hashtag'); ?>" type="text" value="<?php echo $hashtag; ?>" /><br />
		Do not include the #</p>

        <p><label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of tweets to show:'); ?></label>
        <input id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>

		<p><label for="<?php echo $this->get_field_id('images'); ?>"><?php _e('Show images:'); ?></label>
		<input id="<?php echo $this->get_field_id('images'); ?>" name="<?php echo $this->get_field_name('images'); ?>" type="checkbox" <?php checked(true, $images); ?></p>

<?php
	}

}

function wp_widget_twitter_hash_tag_init() {
	add_option('twitter_hash_tag_cache', '');
}

add_action('widgets_init', create_function('', 'return register_widget("WP_Widget_Twitter_Hash_Tag");'));

register_activation_hook(__FILE__, 'wp_widget_twitter_hash_tag_init');

?>
