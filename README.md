# Mu Cron

### Features
本外掛主要用來 Demo WP 排程的行為。

### Installaion

### Usage
* 安裝外掛後，會 Create 一個 xx_mu_cron_demo 的資料表，並且建立 5 筆記錄。
* 進入後台 Mu Cron > Settings 頁，會發現有一個 Demo Mu Cron 的按鈕。
* 按下後，會每 6 秒觸發 Ajax 到後端去取得執行進度 (此頻率與 wp cron 無關)，並將進度表顯示於畫面。
* 後端會執行將 xx_mu_cron_demo 資料的 done 欄位一一設為1，直到所有記錄的 done 欄位均為1。
* 執行時可切換後台頁面。
* 待排程執行完畢，進度表會消失。

### 抄至其他外掛
0. 啟用本外掛，以建立 demo 用資料表 xx_mu_cron_demo
1. HTML DOM - 複製 Cron 按鈕和進度區至目標外掛 (通常放在後台)：
    ```
    <button id="test_progress" class="button"> Demo Mu Cron </button>
    <div id="display_cron_progress"></div>
    ```
2. JS - 複製 js 的語法，注意要對應 HTML DOM，還有將 mu_cron_admin.ajax_url 換成正確的參數：
    ```
    (function( $ ) {
    	'use strict';
    
    	var _mu_cron_loading = false;

    	// 排程作業進度更新
    	function mu_cron_progress_load() {
    		console.log('mu_cron_progress_load');
    		_mu_cron_loading = true;
    		$.ajax({
    			async: true,
    			type: 'POST',
    			url: mu_cron_admin.ajax_url,
    			data: {
    				action: 'mu_cron_progress_load'
    			},
			dataType: 'json',
			success: function(res) {
				if (res.status != 0) {
					setTimeout(function(){
						mu_cron_progress_load(); //call itself
						console.log('tick!');
					}, 6000);
					console.log('mu_cron_progress_load: go next round!');
				}else{
					_mu_cron_loading = false;
					console.log('mu_cron_progress_load: stop!');
					console.log(res);
				}
				// 畫出進度表
				$('#display_cron_progress').html(res.data);
			},
			error:function (xhr, ajaxOptions, thrownError){
				_mu_cron_loading = false;
				alert(ajaxOptions+':'+thrownError);
			}
		});
	}

	$(function() {
        // 切換後台頁面再回來時可以再觸發 setTimeout
		mu_cron_progress_load();

        // 按下執行 Cron 按鈕
		$('#test_progress').on('click', function(e){
			let event_id = '1'; // 視需要修改
			let cron_purp = 'test'; // 視需要修改
			let m = new Date();
			let datetime = m.getFullYear() +"-"+ (m.getMonth()+1) +"-"+ m.getDate() + " " + m.getHours() + ":" + m.getMinutes() + ":" + m.getSeconds();
			// let datetime = '2020-08-09 00:58:00';
			$.ajax({
				async: true,
				type: 'POST',
				url: mu_cron_admin.ajax_url,
				data: {
					action: 'mu_cron_enqueue',
					cron_slug: event_id+'-'+cron_purp,
					datetime: datetime
				},
				dataType: 'json',
				success: function(res) {
					console.log(res.text);
					// alert(res.text);
					if (!_mu_cron_loading) {
						mu_cron_progress_load();
					}
				},
				error:function (xhr, ajaxOptions, thrownError){
					_mu_cron_loading = false;
					alert(ajaxOptions+':'+thrownError);
				}
			});
		});

	});

    })( jQuery );

    ```
3. Admin Hooks:
    ```
    // 註冊 cron interval
    $this->loader->add_filter( 'cron_schedules', $plugin_admin, 'reg_cron_interval' );
    $this->loader->add_action( 'plugins_loaded', $plugin_admin, 'cron_setup' );
    $this->loader->add_action( 'wp_ajax_mu_cron_enqueue', $plugin_admin,  'cron_enqueue' );
    $this->loader->add_action( 'wp_ajax_mu_cron_progress_load', $plugin_admin,  'cron_progress_load' );
    ```
4. 複製 admin.php 202行以下的函式。
5. 確認資料表 xx_mu_cron_demo 裡的 done 欄位均為 0
6. 按下執行 Cron 的按鈕後，應該會正確執行排程工作。
7. 改寫排程工作的邏輯，請改寫 callback_do_action 和 callback_rest_need_do 

### Post Meta

### Hooks

### Dev Memo

* 前端有兩個 ajax 執行點:

    1. mu_cron_progress_load:
    設定每6秒就去確認後端的排程，若還存在就查詢執行進度 (剩餘工作數、預計執行時間)，再反應至UI。

    2. mu_cron_enqueue: 
    將參數傳入後端，先註冊兩個 callback function 之後會互相配合執行主要工作 ( 後端要實作這兩個 callback function )：
        1. callback_do_action: 執行主要處理的任務，須消耗工作數量 (資料筆數)
        2. callback_rest_need_do: 負責回傳目前剩餘處理的工作數量 (資料筆數)

    執行 wp_next_scheduled 開始排程，使用 mu_cron_event_hook 作為 event hook，讓 wp cron 每10秒就 trigger 它，接著就去執行上述兩個 callback function，邊問剩餘工作數量，一邊消耗掉工作數量，若工作數量一來一往沒有被消耗，就會強制停止。

* 工作狀態和資料都是利用 option 來儲存。
* 要注意不要把目標工作數量(資料筆數)的初始設定寫在建構子，例如 fake_rest_tasks = 5，然後用 callback_do_action 去做 5--，因為每次 wp cron 進來就會執行建構子重設掉初始值，所以會變成 5,4,5,4...。

### Todo

* 同時間只能允許一個排程
* 如何讓外部使用 (丟callback 進去)    
