<?php
/*
Plugin Name: RY Custom Pagination
Plugin URI: http://fantasyworld.idv.tw/ry-custom-pagination
Description: Custom Pagination
Version: 1.0.4
Author: Richer Yang
Author URI: http://fantasyworld.idv.tw/
License: MIT License  
License URI: http://opensource.org/licenses/MIT
*/

function_exists('plugin_dir_url') OR exit('No direct script access allowed');

define('RY_CP_VERSION', '1.0.4');
define('RY_CP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RY_CP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RY_CP_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once(RY_CP_PLUGIN_DIR . 'class.ry-cp.php');

register_activation_hook(__FILE__, array('RY_CP', 'plugin_activation'));
register_deactivation_hook(__FILE__, array('RY_CP', 'plugin_deactivation'));

add_action('init', array('RY_CP', 'init'));

function the_ry_posts_pagination($args = array()) {
	echo get_the_ry_posts_pagination($args);
}

function get_the_ry_posts_pagination($args = array()) {
	$args = wp_parse_args($args, array(
		'class' => 'pagination',
		'type' => 'monthly', // monthly, yearly, daily
		'end_size' => 2,
		'mid_size' => 2,

		'show_all' => false,
		'show_prev_next' => true,
		'show_page_number' => true,
		'show_type' => 'html',
		
		'prev_text' => _x('Previous', 'previous post'),
		'next_text' => _x('Next', 'next post'),
		'screen_reader_text' => __('Posts navigation'),
		'before_page_number' => '',
		'after_page_number' => '',
	));
	if( empty($args['type']) ) {
		$args['type'] = 'monthly';
	}
	$archives_list = RY_CP::wp_get_archives($args);

	$total = count($archives_list);
	if( $total < 2 ) {
		return '';
	}

	$show_all = (bool) $args['show_all'];
	$show_prev_next = (bool) $args['show_prev_next'];
	$show_page_number = (bool) $args['show_page_number'];
	$end_size = (int) $args['end_size'];
	if( $end_size < 1 ) {
		$end_size = 1;
	}
	$mid_size = (int) $args['mid_size'];
	if( $mid_size < 0 ) {
		$mid_size = 2;
	}

	$query_m = get_query_var('m');
	if( empty($query_m) ) {
		$query_year = get_query_var('year');
		$query_month = get_query_var('monthnum');
		$query_day = get_query_var('day');
	} else {
		$query_year = substr($query_m, 0, 4);
		if( strlen($query_m) > 5 ) {
			$query_month = substr($query_m, 4, 2);
		}
		if( strlen($query_m) > 7 ) {
			$query_day = substr($query_m, 6, 2);
		}
	}

	$current = 1;
	foreach( $archives_list as $key => $value ) {
		if( 'monthly' == $args['type'] ) {
			if( $value['year'] == $query_year && $value['month'] == $query_month ) {
				$current = $key + 1;
				break;
			}
		} elseif( 'yearly' == $args['type'] ) {
			if( $value['year'] == $query_year ) {
				$current = $key + 1;
				break;
			}
		} elseif( 'daily' == $args['type'] ) {
			if( $value['year'] == $query_year && $value['month'] == $query_month && $value['day'] == $query_day ) {
				$current = $key + 1;
				break;
			}
		}
	}

	if( $show_prev_next && 1 < $current ) {
		$page_links[] = '<a class="prev page-numbers" href="' . esc_url($archives_list[$current - 2]['url']) . '">' . $args['prev_text'] . '</a>';
	}

	for($n = 1; $n <= $total; ++$n ) {
		if( $n == $current ) {
			$page_links[] = '<span class="page-numbers current">' . $args['before_page_number']
				. ($show_page_number ? number_format_i18n($n) : $archives_list[$n - 1]['text'])
				. $args['after_page_number'] . '</span>';
			$dots = true;
		} else {
			if( $show_all || ( $n <= $end_size || ( $current && $n >= $current - $mid_size && $n <= $current + $mid_size ) || $n > $total - $end_size ) ) {
				$page_links[] = '<a class="page-numbers" href="' . esc_url($archives_list[$n - 1]['url']) . '">' . $args['before_page_number']
				. ($show_page_number ? number_format_i18n($n) : $archives_list[$n - 1]['text'])
				. $args['after_page_number'] . '</a>';
				$dots = true;
			} elseif ( $dots && !$show_all ) {
				$page_links[] = '<span class="page-numbers dots">' . __( '&hellip;' ) . '</span>';
				$dots = false;
			}
		}
	}
	if( $show_prev_next && ( $current < $total ) ) {
		$page_links[] = '<a class="next page-numbers" href="' . esc_url($archives_list[$current]['url']) . '">' . $args['next_text'] . '</a>';
	}
	switch( $args['show_type'] ) {
		case 'array':
			return $page_links;
		case 'list':
			$links = '<ul class="page-numbers">' . "\n\t" . '<li>'
				. join('</li>' . "\n\t" . '<li>', $page_links)
				. '</li>' . "\n" . '</ul>' . "\n";
			break;
		default :
			$links = join("\n", $page_links);
			break;
	}

	$navigation = _navigation_markup($links, $args['class'], $args['screen_reader_text']);
	$navigation = apply_filters('ry_posts_pagination_html', $navigation, $args);
	return $navigation;
}