<?php

/**
 * Fired during plugin activation
 *
 * @link       https://audilu.com
 * @since      1.0.0
 *
 * @package    Mu_Cron
 * @subpackage Mu_Cron/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Mu_Cron
 * @subpackage Mu_Cron/includes
 * @author     Audi Lu <khl0327@gmail.com>
 */
class Mu_Cron_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;

		$tbl = "{$wpdb->prefix}mu_cron_demo";
		$sql = <<<SQL
CREATE TABLE {$tbl} (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `done` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`ID`),
  KEY `EMAIL` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
SQL;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		// 建立假資料 for demo
		$fake_emails   = array( 
			'a@a.com', 
			'b@b.com', 
			'c@c.com',
			'd@d.com',
			'e@e.com' 
		);
		
		foreach( $fake_emails as $x ) {
			$insert = $wpdb->insert(
				$tbl,
				 array(
					'email'   => $x
				),
				array( '%s' )
			);
		}
	}

}
