<?php
defined('RY_CP_VERSION') OR exit('No direct script access allowed');

class RY_CP_admin {
	private static $initiated = FALSE;
	
	public static function init() {
		if( !self::$initiated ) {
			self::$initiated = TRUE;
		}
	}
}