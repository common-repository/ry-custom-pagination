<?php
defined('RY_CP_VERSION') OR exit('No direct script access allowed');

class RY_CP {
	private static $option_prefix = 'RY_';
	private static $initiated = false;

	public static function init() {
		if( !self::$initiated ) {
			self::$initiated = true;

			require_once(RY_CP_PLUGIN_DIR . 'class.ry-cp.update.php');
			add_rewrite_rule('date/([^/]+)/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/?', 'index.php?post_type=$matches[1]&year=$matches[2]&monthnum=$matches[3]&day=$matches[4]', 'top');
			add_rewrite_rule('date/([^/]+)/([0-9]{4})/([0-9]{1,2})/?', 'index.php?post_type=$matches[1]&year=$matches[2]&monthnum=$matches[3]', 'top');
			add_rewrite_rule('date/([^/]+)/([0-9]{4})/?', 'index.php?post_type=$matches[1]&year=$matches[2]', 'top');

			if( is_admin() ) {
				require_once(RY_CP_PLUGIN_DIR . 'class.ry-cp.admin.php');
				RY_CP_admin::init();
			}
		}
	}

	public static function wp_get_archives($args = array()) {
		global $wpdb, $wp_rewrite;

		$r = wp_parse_args($args, array(
			'post_type' => 'post',
			'order' => 'DESC',
			'date_query' => null
		));
		$r['post_type'] = sanitize_key($r['post_type']);
		if( !post_type_exists($r['post_type']) ) {
			return array();
		}

		$order = strtoupper($r['order']);
		if(  $order !== 'ASC' ) {
			$order = 'DESC';
		}

		$where = " WHERE {$wpdb->posts}.post_type = '".$r['post_type']."' AND {$wpdb->posts}.post_status = 'publish'";
		if( !empty($r['date_query']) ) {
			$date_query = new WP_Date_Query($r['date_query']);
			$where .= $date_query->get_sql();
		}
		$where = apply_filters('getarchives_where', $where, $args);
		$where = apply_filters('ry_getarchives_where', $where, $args);

		$join = '';
		$join = apply_filters('getarchives_join', $join, $args);
		$join = apply_filters('ry_getarchives_join', $join, $args);

		$last_changed = wp_cache_get('last_changed', 'posts');
		if( !$last_changed ) {
			$last_changed = microtime();
			wp_cache_set('last_changed', $last_changed, 'posts');
		}

		$list = array();
		if( 'monthly' == $r['type'] ) {
			$query = "SELECT YEAR({$wpdb->posts}.post_date) AS `year`, MONTH({$wpdb->posts}.post_date) AS `month` FROM $wpdb->posts $join $where GROUP BY YEAR({$wpdb->posts}.post_date), MONTH({$wpdb->posts}.post_date) ORDER BY {$wpdb->posts}.post_date $order $limit";
			$key = md5($query);
			$key = "RY_CP_wp_get_archives:$key:$last_changed";
			if( !$results = wp_cache_get($key, 'posts') ) {
				$results = $wpdb->get_results($query);
				wp_cache_set($key, $results, 'posts');
			}
			if( $results ) {
				$permalink_type = $wp_rewrite->get_month_permastruct();
				$permalink_type = empty($permalink_type);
				foreach( (array) $results as $result ) {
					$url = get_month_link($result->year, $result->month);
					if( $r['post_type'] != 'post' ) {
						if ( $permalink_type ) {
							$url .= '&post_type=' . $r['post_type'];
						} else {
							$url = str_replace('/date/', '/date/' . $r['post_type'] . '/', $url);
						}
					}
					$list[] = array(
						'year' => $result->year,
						'month' => $result->month,
						'day' => 0,
						'url' => $url
					);
					
				}
			}
		} elseif( 'yearly' == $r['type'] ) {
			$query = "SELECT YEAR({$wpdb->posts}.post_date) AS `year` FROM $wpdb->posts $join $where GROUP BY YEAR({$wpdb->posts}.post_date) ORDER BY {$wpdb->posts}.post_date $order $limit";
			$key = md5($query);
			$key = "RY_CP_wp_get_archives:$key:$last_changed";
			if( !$results = wp_cache_get($key, 'posts') ) {
				$results = $wpdb->get_results($query);
				wp_cache_set($key, $results, 'posts');
			}
			if( $results ) {
				$permalink_type = $wp_rewrite->get_year_permastruct();
				$permalink_type = empty($permalink_type);
				foreach( (array) $results as $result ) {
					$url = get_year_link($result->year);
					if( $r['post_type'] != 'post' ) {
						if ( $permalink_type ) {
							$url .= '&post_type=' . $r['post_type'];
						} else {
							$url = str_replace('/date/', '/date/' . $r['post_type'] . '/', $url);
						}
					}
					$list[] = array(
						'year' => $result->year,
						'month' => 0,
						'day' => 0,
						'url' => $url
					);
				}
			}
		} elseif( 'daily' == $r['type'] ) {
			$query = "SELECT YEAR({$wpdb->posts}.post_date) AS `year`, MONTH({$wpdb->posts}.post_date) AS `month`, DAYOFMONTH({$wpdb->posts}.post_date) AS `day` FROM $wpdb->posts $join $where GROUP BY YEAR({$wpdb->posts}.post_date), MONTH({$wpdb->posts}.post_date), DAYOFMONTH({$wpdb->posts}.post_date) ORDER BY {$wpdb->posts}.post_date $order $limit";
			$key = md5($query);
			$key = "RY_CP_wp_get_archives:$key:$last_changed";
			if( !$results = wp_cache_get($key, 'posts') ) {
				$results = $wpdb->get_results($query);
				wp_cache_set($key, $results, 'posts');
			}
			if( $results ) {
				$archive_day_date_format = 'Y/m/d';
				if ( !(bool) $r['over_date'] ) {
					$archive_day_date_format = get_option('date_format');
				}
				$permalink_type = $wp_rewrite->get_day_permastruct();
				$permalink_type = empty($permalink_type);
				foreach( (array) $results as $result ) {
					$url = get_day_link($result->year, $result->month, $result->day);
					if( $r['post_type'] != 'post' ) {
						if ( $permalink_type ) {
							$url .= '&post_type=' . $r['post_type'];
						} else {
							$url = str_replace('/date/', '/date/' . $r['post_type'] . '/', $url);
						}
					}
					$list[] = array(
						'year' => $result->year,
						'month' => $result->month,
						'day' => $result->day,
						'url' => $url
					);
				}
			}
		}

		return $list;
	}

	public static function get_option($option, $default = false) {
		return get_option(self::$option_prefix . $option, $default);
	}

	public static function update_option($option, $value) {
		return update_option(self::$option_prefix . $option, $value);
	}

	public static function plugin_activation() {
	}

	public static function plugin_deactivation( ) {
	}
}