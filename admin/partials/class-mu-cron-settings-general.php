<?php
class Mu_Cron_Settings_General extends Mu_Cron_Settings_Abstract{
	private static $instance;
	private $setting;

	// singleton
    public static function get_instance() {
        if( self::$instance === null ){
            self::$instance = new self();
        }
        return self::$instance;
	}

	public function get_setting() {
		return $this->setting;
	}

	public function __construct() {
		self::$instance = null;

		// 檢查 login page, profile page 是否已建立，若無則於控制項上顯示提示
		$opts = get_option('mu_cron_general_settings');

		$this->setting = array(
			'tab' => array( 
				'name' => 'general-options', //$_GET arg
				'title' => __('General', 'mu-cron')
			),
			'section' => array(
				'id' => 'mu-cron-general-main',
				'title' => __('General', 'mu-cron'),
				'callback' => '',
				'page' => 'mu-cron-general-section'
			),
			'option_key' => 'mu_cron_general_settings',
			'fields' => array(
				// field ID => [settings...]
				'debug_mode' => array(
					'title' => 'Debug mode',
					'desc' => 'Logger will work if enabled. <a href="'.get_admin_url('', 'admin.php?page=mu-cron-logger').'">Check log?</a>',
					'placeholder' => '',
					'callback' => array($this, 'display_checkbox_fd'),
				),
				'lic_key' => array(
					'title' => 'License Key',
					'desc' => '',
					'placeholder' => '',
					'callback' => array($this, 'display_text_fd'), //args: fd_id
				),
				'g_recaptcha_v2_key' => array(
					'title' => 'reCaptcha v2 Key',
					'desc' => 'Google reCAPTCHA v2 Key',
					'placeholder' => '',
					'callback' => array($this, 'display_text_fd'), //args: fd_id
				),
				'g_recaptcha_v2_sec' => array(
					'title' => 'reCaptcha v2 Secret',
					'desc' => 'Google reCAPTCHA v2 Secret',
					'placeholder' => '',
					'callback' => array($this, 'display_text_fd'), //args: fd_id
				),
			)
		);
	}

	// get fields value from option and display fields.

	public function display_text_fd($fd_id) {
		if (empty($fd_id)) {
			echo __('Field ID is required.', 'mu-cron');
			return;
		}
		$obj_setting = $this->get_setting();
		$option_key = $obj_setting['option_key'];
		$opts = get_option( $option_key );
		$fd_name = $option_key.'['.$fd_id.']';
		$fd_placeholder = $obj_setting['fields'][$fd_id]['placeholder'];
		$fd_desc = $obj_setting['fields'][$fd_id]['desc'];
		$fd_val = isset( $opts[$fd_id] ) ? $opts[$fd_id] : ''; 
		?>
		<input type="text" size="80" name="<?php echo $fd_name;?>" value="<?php echo $fd_val; ?>" placeholder="<?php echo $fd_placeholder;?>" />
		<p class="description" ><?php echo $fd_desc;?></p>
		<?php
	}

	public function display_checkbox_fd($fd_id) {
		if (empty($fd_id)) {
			echo __('Field ID is required.', 'mu-cron');
			return;
		}
		$obj_setting = $this->get_setting();
		$fds_setting = $obj_setting['fields'][$fd_id];

		// default option key
		$option_key = $obj_setting['option_key'];
		$fd_name = $option_key.'['.$fd_id.']';

		// set/get with custom option key
		if (!empty($fds_setting['option_key'])) {
			$option_key = $fds_setting['option_key']; 
			$fd_name = $option_key;
		}
		$opts = get_option( $option_key );
		if (is_array($opts) && isset( $opts[$fd_id] )) {
			$fd_val = $opts[$fd_id];
		}else{
			$fd_val = $opts;
		}
		$fd_desc = $fds_setting['desc'];		
		?>
		<input type="checkbox" name="<?php echo $fd_name;?>" value="1" <?php checked(1, $fd_val, true); ?>>
		<p class="description" ><?php echo $fd_desc;?></p>
		<?php
	}
}