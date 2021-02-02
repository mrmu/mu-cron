<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://audilu.com
 * @since      1.0.0
 *
 * @package    Mu_Cron
 * @subpackage Mu_Cron/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Mu_Cron
 * @subpackage Mu_Cron/admin
 * @author     Audi Lu <khl0327@gmail.com>
 */
class Mu_Cron_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	private $class_deps_check;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		// $this->class_deps_check = array(
		// 	'woocommerce' => array(
		// 		'status' => false,
		// 		'err_msg' => __('WooCommerce is not activated, please activate it to use plugin: Mu Cron.', $this->plugin_name)
		// 	),
		// );

	}

	private function is_enqueue_pages($hook) {
		global $post, $post_type;

		if (empty($post_type)){
			if (!empty($post)) {
				$post_type = $post->post_type;
			}
		}

		$load_post_types = array(
			//post type slugs ...
		);

		$apply_pages = array(
			'post-new.php' => $load_post_types, 		// new post
			'edit.php' => $load_post_types,				// post list
			'post.php' => $load_post_types,				// post edit
			'toplevel_page_mu-cron',				// Settings
			'mu-cron_page_mu-cron-logger'	// Log
		);

		// echo 'hook:['.$hook.'] '; // ref page name
		// echo 'post_type:['.$post_type.'] ';

		foreach ($apply_pages as $pg => $pts) {
			if ($pg === $hook) {
				if (!empty($pts) && is_array($pts)) {
					if (in_array($post_type, $pts)) {
						return true;
					}
				}
				return true;
			}else if ($pts === $hook) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles($hook) {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in mu-cron_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The mu-cron_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		if ($this->is_enqueue_pages($hook)) {
			wp_enqueue_style( 
				$this->plugin_name, 
				plugin_dir_url( __FILE__ ) . 'css/mu-cron-admin.css', 
				array(), 
				filemtime( (dirname( __FILE__ )) . '/css/mu-cron-admin.css' ), 
				'all' 
			);
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts($hook) {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in mu-cron_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The mu-cron_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		if ($this->is_enqueue_pages($hook)) {
			wp_enqueue_script( 
				$this->plugin_name, 
				plugin_dir_url( __FILE__ ) . 'js/mu-cron-admin.js', 
				array( 'jquery' ), 
				filemtime( (dirname( __FILE__ )) . '/js/mu-cron-admin.js' ), 
				false 
			);
			wp_localize_script(
				$this->plugin_name,
				'mu_cron_admin', 
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' )
				)
			);
		}
	}

	// check if the plugin need other class (that from another plugin)
	public function class_deps_check_active() {
		if (!is_array($this->class_deps_check)) {
			return;
		}
		foreach ($this->class_deps_check as $chk_class => $info) {
			if ( class_exists( $chk_class ) ) {
				$this->class_deps_check[$chk_class]['status'] = true;
			} else {
				$this->class_deps_check[$chk_class]['status'] = false;
			}
		}
	}

	public function class_deps_check_admin_notice() {
		if (!is_array($this->class_deps_check)) {
			return;
		}
		foreach ($this->class_deps_check as $chk_class => $info) {
			if ( $this->class_deps_check[$chk_class]['status'] === false ){
				?>
				<div class="notice notice-error is-dismissible">
					<p>
						<?php echo $this->class_deps_check[$chk_class]['err_msg']; ?>
					</p>
				</div>
				<?php
			}
		}
	}

	// ------ Mu Cron Functions ------ //

	private function set_data($option, $val) {
		wp_cache_delete('notoptions', 'options');
		wp_cache_delete('alloptions', 'options');
		update_option($option, $val);
	}

	private function get_data($option) {
		wp_cache_delete('notoptions', 'options');
		wp_cache_delete('alloptions', 'options');
		$val = get_option($option);
		return $val;
	}

	// 取出正在處理的 task
	private function get_cur_task() {
		return $this->get_data('mu_cron_cur_task');
	}

	// 取出還沒做的 tasks
	private function get_tasks() {
		return $this->get_data('mu_cron_tasks');
	}

	// 儲存目前正在處理的 task
	private function set_cur_task($task) {
		$this->set_data('mu_cron_cur_task', $task);
	}

	// 儲存還沒做的 tasks
	private function set_tasks($cron_arg, $mode = '') {
		if ($mode == 'add') {
			$cron_tasks = $this->get_tasks();
			if (empty($cron_tasks)) {
				$cron_tasks = array();
			}
			if (is_array($cron_tasks)) {
				$cron_tasks[$cron_arg['cron_slug']] = $cron_arg;
				uasort($cron_tasks, function($a, $b) {
					return $a['datetime'] - $b['datetime'];
				});
				$this->set_data('mu_cron_tasks', $cron_tasks);
			}
		}else{
			$this->set_data('mu_cron_tasks', $cron_arg);
		}
	}

	public function reg_cron_interval( $schedules ) {
		$schedules['ten_seconds'] = array(
			'interval' => 10,
			'display'  => esc_html__( 'Every 10 seconds.', $this->plugin_name ),
		);
		return $schedules;
	}

	// 註冊 event hook
	public function cron_setup() {
		// 當 cron event 啟動時，wp cron 會重覆執行此 event hook
		add_action('mu_cron_event_hook', array($this, 'trigger_cron')); 
	}

	// 觸發執行排程工作
	public function trigger_cron($args) {
		do_action( 'mu_cron_log', 'trigger_cron' );
		// 剩餘要做的工作數
		$need_done_num = $this->rest_tasks_need_to_do();
		do_action( 'mu_cron_log', $need_done_num );
		if ($need_done_num <= 0) {
			// 工作處理完畢，取出下次 event 執行的時間，停止排程 (unschedule)
			$timestamp = wp_next_scheduled( 'mu_cron_event_hook', array('test') );
			wp_unschedule_event( $timestamp, 'mu_cron_event_hook', array('test') );
			$this->set_data('mu_cron_status', 'stop');
			do_action( 'mu_cron_log', 'bye' );
		}else{
			// 實際執行
			// 處理目前的 task， 還剩：'.$need_done_num.'筆未處理...'
			$this->do_cron_task();
		}
	}

	// 檢查並回傳剩下的工作數量
	private function rest_tasks_need_to_do() {
		$todo_tasks = $this->get_tasks();
		$cur_task = $this->get_cur_task();
		$need_done = 0;
		if (!empty($cur_task)) {
			$need_done = $todo_tasks[$cur_task]['rest_need_do'];
			if (is_callable($todo_tasks[$cur_task]['callback_rest_need_do'])) {
				// 執行 callback func 詢問目前的工作是否已做完
				$need_done = call_user_func($todo_tasks[$cur_task]['callback_rest_need_do'], $cur_task);
				do_action( 'mu_cron_log', 'ask callback rest: '.$need_done );
			}else{
				do_action( 'mu_cron_log', 'didnot ask callback rest: '.$need_done );
			}
		}

		// 正在處理的工作若做完了
		if ($need_done < 1) {
			// 就清掉
			unset($todo_tasks[$cur_task]);
			// 如果還有下一個 task
			if (count($todo_tasks) > 0) {
				$cur_task = array_key_first($todo_tasks);	
				$need_done = $todo_tasks[$cur_task]['rest_need_do'];
			}else{
				// 全處理完了
				$cur_task = '';
				$need_done = 0;
			}
			$this->set_cur_task($cur_task);
			$this->set_tasks($todo_tasks);	
		}

		return $need_done;
	}

	// 實際執行排程內容
	public function do_cron_task() {
		do_action( 'mu_cron_log', 'do_cron_task' );
		// 取出目前該做的task
		$bef_need_done = $this->rest_tasks_need_to_do();
		$todo_tasks = $this->get_tasks();
		$cur_task = $this->get_cur_task();
		$datetime = $todo_tasks[$cur_task]['datetime'];

		// 到了該執行的時間
		if (time() >= $datetime) {
			// 執行處理
			if (is_callable($todo_tasks[$cur_task]['callback_do_action'])) {
				call_user_func($todo_tasks[$cur_task]['callback_do_action'], $cur_task);
				$aft_need_done = $this->rest_tasks_need_to_do();
				$aft_cur_task = $this->get_cur_task();

				// 同一個task，處理後的 need done 數量卻沒下降收斂，會無窮loop下去
				if ($aft_cur_task == $cur_task && $bef_need_done <= $aft_need_done) {
					$todo_tasks[$cur_task]['rest_need_do'] = 0;
					$this->set_tasks($todo_tasks);
					$this->set_cur_task('');
					do_action( 'mu_cron_log', '沒收斂要停.' );
					return;
				}else{
					// 有收斂就繼續執行
					$need_done = $aft_need_done;
					do_action( 'mu_cron_log', '繼續.' );
				}
			}else{
				// 沒有實際執行處理的function，就自行減1
				$need_done = $bef_need_done - 1;
			}

			$todo_tasks[$cur_task]['rest_need_do'] = $need_done;
			$this->set_tasks($todo_tasks);
		}else{
			// 執行目前 task 的時間未到，看還有沒有其他待執行的tasks
			if (count($todo_tasks) > 0) {
				// 目前的task 若不是第一個 (最快會被執行的)，就換成第一個
				$recentest_task = array_key_first($todo_tasks);	
				if ($recentest_task != $cur_task) {
					// 換成最快會被執行到的 task，優先處理
					$this->set_cur_task($recentest_task);
				}else{
					// 目前task已經是最快要被執行的了，慢慢等吧
				}
			}else{
				// 沒有其他待執行的task，就慢慢等吧
			}
		}
		// 回合結束
	}

	// cron callback: 返回剩下要處理的資料筆數
	public function mu_cron_get_rest($cron_slug) {

		global $wpdb;

		$tbl = "{$wpdb->prefix}mu_cron_demo";
		$sql = "SELECT COUNT(*) FROM {$tbl} WHERE `done` = 0 ";
		$cnt = absint($wpdb->get_var($sql));
		return $cnt;
	}
	// cron callback: 執行 排程作業內容
	public function mu_cron_do_action($cron_slug) {
		sleep(10);
		global $wpdb;

		$tbl = "{$wpdb->prefix}mu_cron_demo";
		$sql = "SELECT `ID` FROM {$tbl} WHERE `done` = 0 LIMIT 1";
		$do_id = absint($wpdb->get_var($sql));
		if ($do_id !== 0) {
			$wpdb->update( 
				$tbl, 
				array( 
					'done' => 1   // int
				), 
				array( 'ID' => $do_id ), // WHERE 
				array( 
					'%d'
				), 
				array( '%d' ) // WHERE
			);
		}
	}

	// ajax function: 準備開始排程，收集參數
	public function cron_enqueue() {

		$cron_slug = sanitize_text_field($_POST['cron_slug']);
		$datetime = sanitize_text_field($_POST['datetime']);

		$cron_arg = array(
			'datetime' => strtotime(get_gmt_from_date( $datetime )),
			
			// 作為 callback 的參數
			'cron_slug' => $cron_slug,

			// 由 callback 程式決定執行次數
			'callback_rest_need_do' => array($this, 'mu_cron_get_rest'), 

			// 由 callback 程式決定執行內容，須能讓 rest_need_do 收斂
			'callback_do_action' => array($this, 'mu_cron_do_action'),

			// 若未設定 callback_rest_need_do，就直接指定重覆做10次
			'rest_need_do' => 10 
		);
		$rst = $this->do_cron_queue($cron_arg);
		if (!empty($rst['success'])) {
			$rtn_ary = array('code'=>'0', 'text'=>$rst['success'].' 成功排程 '.$cron_slug.'。');
		}else{
			$rtn_ary = $rst;
		}
		echo json_encode($rtn_ary, JSON_FORCE_OBJECT);
		die();
	}

	private function do_cron_queue($cron_args) {

		$defaults = array(
			'cron_slug' => 'default',
			'datetime' => time(),
			'rest_need_do' => 5,
			'callback_rest_need_do' => '',
			'callback_do_action' => ''
		);
		$args = wp_parse_args( $cron_args, $defaults );

		if (is_callable($args['callback_rest_need_do'])) {
			$args['rest_need_do'] = call_user_func($args['callback_rest_need_do'], $args['cron_slug']);
		}

		if (!is_callable($args['callback_do_action'])) {
			return array('error' => 'arg error: callback function "callback_do_action" is invalid.');
		}

		// enqueued
		$this->set_tasks($args, 'add');

		// init
		$mu_cron_status = $this->get_data('mu_cron_status');

		// 若排程事件已被停止執行 (取不到下次執行的 timestamp)
		if (! wp_next_scheduled( 'mu_cron_event_hook', array('test') ) ) {
			$this->set_data('mu_cron_status', 'stop');
		}
		// 沒有正確的cron狀態
		if ($mu_cron_status !== 'stop' && $mu_cron_status !== 'doing') {
			$this->set_data('mu_cron_status', 'stop');
		}
		$mu_cron_status = $this->get_data('mu_cron_status');

		if ($mu_cron_status === 'stop') {
			wp_schedule_event( time(), 'ten_seconds', 'mu_cron_event_hook', array('test') );
			$this->set_data('mu_cron_status', 'doing');
			$this->set_cur_task($args['cron_slug']);
			return array('success' => '開工了!');
		}else{
			return array('error' => '排程事件執行中，已加入排程。');
		}
	}

	// ajax function: 更新排程進度 (UI)
	public function cron_progress_load() {

		$rst = $this->get_progress();
		if (!empty($rst)) {
			$rtn_ary = array('code'=>'0', 'data'=>$rst, 'status'=>1);
		}else{
			$rtn_ary = array('code'=>'0', 'data'=>$rst, 'status'=>0);
		}
		echo json_encode($rtn_ary, JSON_FORCE_OBJECT);
		die();
	}

	private function get_progress() {
		// 若排程事件已被停止執行 (取不到下次執行的 timestamp)
		if (! wp_next_scheduled( 'mu_cron_event_hook', array('test') ) ) {
			$this->set_data('mu_cron_status', 'stop');
			$this->set_cur_task('');
			return false;
		}else{
			$todo_tasks = $this->get_tasks();
			$cur_task = $this->get_cur_task();
		}

		if (empty($cur_task)) {
			return false;
		}
		
		$rst = '<table class="wp-list-table widefat">';
		$rst .= '<thead>';
		$rst .= '<tr><th>排程代號</th><th>待執行工作數</th><th>預計執行時間</th></tr>';
		$rst .= '</thead><tbody>';
		foreach ($todo_tasks as $task => $arg) {
			$rst .= '<tr>';
			if ($task == $cur_task) {
				$rst .= '<td>* '.$task.'</td>';
			}else{
				$rst .= '<td>'.$task.'</td>';
			}
			$rst .= '<td>'.$todo_tasks[$task]['rest_need_do'].'</td>';
			$rst .= '<td>'.get_date_from_gmt(date('Y-m-d H:i:s', $todo_tasks[$task]['datetime'])).'</td>';
			$rst .= '</tr>';
		}
		$rst .= '</tbody></table>';
		return $rst;
	}
}
