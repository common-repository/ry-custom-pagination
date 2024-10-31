<?php
defined('RY_CP_VERSION') OR exit('No direct script access allowed');

class RY_CP_update {
	public static function update() {
		$now_version = RY_CP::get_option('version');

		if( $now_version === FALSE ) {
			$now_version = '0.0.0';
		}
		if( $now_version == RY_CP_VERSION ) {
			return;
		}
		if( version_compare($now_version, '1.0.0', '<' ) ) {
			RY_CP::update_option('version', '1.0.0');
		}
		if( version_compare($now_version, '1.0.1', '<' ) ) {
			RY_CP::update_option('version', '1.0.1');
		}
		if( version_compare($now_version, '1.0.2', '<' ) ) {
			RY_CP::update_option('version', '1.0.2');
		}
		if( version_compare($now_version, '1.0.3', '<' ) ) {
			RY_CP::update_option('version', '1.0.3');
		}
		if( version_compare($now_version, '1.0.4', '<' ) ) {
			RY_CP::update_option('version', '1.0.4');
		}
	}
}
