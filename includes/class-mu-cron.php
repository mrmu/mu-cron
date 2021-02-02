<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://audilu.com
 * @since      1.0.0
 *
 * @package    Mu_Cron
 * @subpackage Mu_Cron/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Mu_Cron
 * @subpackage Mu_Cron/includes
 * @author     Audi Lu <khl0327@gmail.com>
 */
class Mu_Cron {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      mu-cron_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'MU_CRON_VERSION' ) ) {
			$this->version = MU_CRON_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'mu-cron';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - mu-cron_Loader. Orchestrates the hooks of the plugin.
	 * - mu-cron_i18n. Defines internationalization functionality.
	 * - mu-cron_Admin. Defines all hooks for the admin area.
	 * - mu-cron_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-mu-cron-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-mu-cron-i18n.php';

		/**
		 * The class responsible for utils (static functions)
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-mu-cron-utils.php';

		/**
		 * The class responsible for register custom post types / taxonomy / post status
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-mu-cron-custom_post_type.php';
		
		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-mu-cron-admin.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-mu-cron-settings.php';
		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-mu-cron-public.php';

		$this->loader = new Mu_Cron_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the mu-cron_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Mu_Cron_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Mu_Cron_Admin( $this->get_plugin_name(), $this->get_version() );
		$plugin_settings = new Mu_Cron_Settings( $this->get_plugin_name(), $this->get_version() );
		$plugin_cpt = new Mu_Cron_Custom_Post_Type( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'class_deps_check_active' );
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'class_deps_check_admin_notice' );

		// 註冊 cron interval
		$this->loader->add_filter( 'cron_schedules', $plugin_admin, 'reg_cron_interval' );
		$this->loader->add_action( 'plugins_loaded', $plugin_admin, 'cron_setup' );
		$this->loader->add_action( 'wp_ajax_mu_cron_enqueue', $plugin_admin,  'cron_enqueue' );
		$this->loader->add_action( 'wp_ajax_mu_cron_progress_load', $plugin_admin,  'cron_progress_load' );
	
		// Custom post type

		$this->loader->add_action( 'init', $plugin_cpt, 'reg' );

		// Settings 

		// Usage:
		// $set = Mu_Cron_Settings_Factory::get_instance('general');
		// $set->get_fd_option('lic_key'); // get field setting value

		$this->loader->add_action( 'admin_menu', $plugin_settings, 'add_menu_items');
		$this->loader->add_action( 'admin_init', $plugin_settings, 'register');
		$this->loader->add_action( 'wp_ajax_clear_log', $plugin_settings, 'clear_log' );
		$this->loader->add_action( 'mu_cron_log', $plugin_settings, 'add_log', 10, 2 );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Mu_Cron_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'init', $plugin_public, 'register_shortcodes' );
		$this->loader->add_action( 'wp_head', $plugin_public, 'add_grecaptcha_api_js' );
		$this->loader->add_action( 'wp_ajax_send_to_backend', $plugin_public, 'send_to_backend' ); // logged in
		$this->loader->add_action( 'wp_ajax_nopriv_send_to_backend', $plugin_public, 'send_to_backend' ); // not yet login
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Mu_Cron_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
