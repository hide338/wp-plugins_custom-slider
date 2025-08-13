<?php

/**
 * Plugin Name: Advanced Custom Slider
 * Plugin URI: https://github.com/custom-slider
 * Description: ローディング画面完了後に開始するカスタムスライダーシステム
 * Version: 1.1.0
 * Author: Toshihide Nakahara
 * Text Domain: advanced-custom-slider
 * Domain Path: /languages
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
  exit;
}

// プラグインの定数定義
define('CUSTOM_SLIDER_VERSION', '1.1.0');
define('CUSTOM_SLIDER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CUSTOM_SLIDER_PLUGIN_PATH', plugin_dir_path(__FILE__));

class AdvancedCustomSlider
{
  public function __construct()
  {
    add_action('init', array($this, 'init'));
    add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    add_action('admin_menu', array($this, 'add_admin_menu'));
    add_action('admin_init', array($this, 'handle_admin_actions'));
    add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

    // AJAX処理
    add_action('wp_ajax_update_slide_order', array($this, 'ajax_update_slide_order'));

    // ショートコード登録
    add_shortcode('custom_slider', array($this, 'slider_shortcode'));

    // 後方互換性のための旧ショートコード
    add_shortcode('yokitabi_slider', array($this, 'slider_shortcode'));

    // プラグイン有効化時のフック
    register_activation_hook(__FILE__, array($this, 'activate'));
    register_deactivation_hook(__FILE__, array($this, 'deactivate'));
  }

  public function init()
  {
    // 言語ファイルの読み込み
    load_plugin_textdomain('advanced-custom-slider', false, dirname(plugin_basename(__FILE__)) . '/languages');
  }

  public function activate()
  {
    $this->create_database_tables();
    $this->migrate_old_tables();
    flush_rewrite_rules();
  }

  public function deactivate()
  {
    flush_rewrite_rules();
  }

  /**
   * データベーステーブル作成
   */
  public function create_database_tables()
  {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // スライダーテーブル
    $slider_table = $wpdb->prefix . 'custom_sliders';
    $slider_sql = "CREATE TABLE $slider_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            settings longtext,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

    // スライドテーブル
    $slide_table = $wpdb->prefix . 'custom_slides';
    $slide_sql = "CREATE TABLE $slide_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            slider_id mediumint(9) NOT NULL,
            image_url varchar(2048) NOT NULL,
            title varchar(255),
            subtitle varchar(255),
            link_url varchar(2048),
            sort_order int(11) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY slider_id (slider_id)
        ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($slider_sql);
    dbDelta($slide_sql);
  }

  /**
   * 旧テーブルからのデータ移行
   */
  private function migrate_old_tables()
  {
    global $wpdb;

    $old_slider_table = $wpdb->prefix . 'yokitabi_sliders';
    $old_slide_table = $wpdb->prefix . 'yokitabi_slides';
    $new_slider_table = $wpdb->prefix . 'custom_sliders';
    $new_slide_table = $wpdb->prefix . 'custom_slides';

    // 旧テーブルが存在し、新テーブルが空の場合のみ移行
    $old_exists = $wpdb->get_var("SHOW TABLES LIKE '$old_slider_table'") === $old_slider_table;
    $new_count = $wpdb->get_var("SELECT COUNT(*) FROM $new_slider_table");

    if ($old_exists && $new_count == 0) {
      // スライダーデータ移行
      $wpdb->query("INSERT INTO $new_slider_table (id, name, description, settings, is_active, created_at, updated_at) 
                    SELECT id, name, description, settings, is_active, created_at, updated_at FROM $old_slider_table");

      // スライドデータ移行
      $wpdb->query("INSERT INTO $new_slide_table (id, slider_id, image_url, title, subtitle, link_url, sort_order, is_active, created_at, updated_at) 
                    SELECT id, slider_id, image_url, title, subtitle, link_url, sort_order, is_active, created_at, updated_at FROM $old_slide_table");
    }
  }

  /**
   * スクリプトとスタイルの読み込み
   */
  public function enqueue_scripts()
  {
    // Swiperライブラリ
    wp_enqueue_script(
      'swiper-js',
      'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js',
      array(),
      '10.0.0',
      true
    );
    wp_enqueue_style(
      'swiper-css',
      'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css',
      array(),
      '10.0.0'
    );

    // カスタムスライダーJS
    wp_enqueue_script(
      'advanced-custom-slider',
      CUSTOM_SLIDER_PLUGIN_URL . 'assets/js/custom-slider.js',
      array('jquery', 'swiper-js'),
      CUSTOM_SLIDER_VERSION,
      true
    );

    // カスタムスライダーCSS
    wp_enqueue_style(
      'advanced-custom-slider-css',
      CUSTOM_SLIDER_PLUGIN_URL . 'assets/css/custom-slider.css',
      array('swiper-css'),
      CUSTOM_SLIDER_VERSION
    );
  }

  /**
   * 管理画面用スクリプト
   */
  public function admin_enqueue_scripts($hook)
  {
    if (strpos($hook, 'custom-slider') === false) {
      return;
    }

    wp_enqueue_media();
    wp_enqueue_script('jquery-ui-sortable');

    wp_enqueue_script(
      'custom-slider-admin',
      CUSTOM_SLIDER_PLUGIN_URL . 'assets/js/admin.js',
      array('jquery', 'jquery-ui-sortable'),
      CUSTOM_SLIDER_VERSION,
      true
    );

    wp_enqueue_style(
      'custom-slider-admin-css',
      CUSTOM_SLIDER_PLUGIN_URL . 'assets/css/admin.css',
      array(),
      CUSTOM_SLIDER_VERSION
    );

    // 管理画面用のajaxurl変数を追加
    wp_localize_script('custom-slider-admin', 'custom_admin_ajax', array(
      'ajaxurl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('custom_admin_nonce')
    ));
  }

  /**
   * 管理画面メニュー追加
   */
  public function add_admin_menu()
  {
    add_menu_page(
      'カスタムスライダー',
      'カスタムスライダー',
      'manage_options',
      'custom-slider-manager',
      array($this, 'admin_page_list'),
      'dashicons-images-alt2',
      30
    );

    add_submenu_page(
      'custom-slider-manager',
      'スライダー編集',
      'スライダー編集',
      'manage_options',
      'custom-slider-edit',
      array($this, 'admin_page_edit')
    );
  }

  /**
   * ショートコード処理
   */
  public function slider_shortcode($atts)
  {
    $atts = shortcode_atts(array(
      'id' => 0,
      'images' => '',
      'facility_names' => '',
      'en_names' => '',
      'autoplay' => 5000,
      'effect' => 'fade',
      'speed' => 1000,
      'height' => '100vh',
      'show_navigation' => true,
      'show_pagination' => true,
      'delay_start' => true,
      'delay_seconds' => 1.0
    ), $atts, 'custom_slider');

    if (!empty($atts['id'])) {
      return $this->render_database_slider($atts['id']);
    } else {
      return $this->render_legacy_slider($atts);
    }
  }

  /**
   * データベース管理スライダーのレンダリング
   */
  private function render_database_slider($slider_id)
  {
    global $wpdb;

    $slider_table = $wpdb->prefix . 'custom_sliders';
    $slide_table = $wpdb->prefix . 'custom_slides';

    $slider = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM $slider_table WHERE id = %d AND is_active = 1",
      $slider_id
    ));

    if (!$slider) {
      return '<p>スライダーが見つかりません。</p>';
    }

    $slides = $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM $slide_table WHERE slider_id = %d AND is_active = 1 ORDER BY sort_order ASC",
      $slider_id
    ));

    if (empty($slides)) {
      return '<p>スライドが設定されていません。</p>';
    }

    $settings = json_decode($slider->settings, true);
    if (!$settings) {
      $settings = array();
    }

    $autoplay = $settings['autoplay'] ?? 5000;
    $effect = $settings['effect'] ?? 'fade';
    $speed = $settings['speed'] ?? 1000;
    $height = $settings['height'] ?? '100vh';
    $show_navigation = $settings['show_navigation'] ?? true;
    $show_pagination = $settings['show_pagination'] ?? true;
    $delay_start = $settings['delay_start'] ?? true;
    $delay_seconds = $settings['delay_seconds'] ?? 1.0;

    $slider_id_attr = 'custom-slider-' . $slider_id;

    ob_start();
    include CUSTOM_SLIDER_PLUGIN_PATH . 'templates/slider.php';
    return ob_get_clean();
  }

  /**
   * 後方互換性のためのレガシースライダー
   */
  private function render_legacy_slider($atts)
  {
    if (empty($atts['images'])) {
      return '<p>画像が指定されていません。</p>';
    }

    $images = explode(',', $atts['images']);
    $facility_names = !empty($atts['facility_names']) ? explode(',', $atts['facility_names']) : array();
    $en_names = !empty($atts['en_names']) ? explode(',', $atts['en_names']) : array();

    $slides = array();
    foreach ($images as $index => $image_url) {
      $slide = new stdClass();
      $slide->image_url = trim($image_url);
      $slide->title = isset($facility_names[$index]) ? trim($facility_names[$index]) : '';
      $slide->subtitle = isset($en_names[$index]) ? trim($en_names[$index]) : '';
      $slide->link_url = '';
      $slides[] = $slide;
    }

    $autoplay = $atts['autoplay'];
    $effect = $atts['effect'];
    $speed = $atts['speed'];
    $height = $atts['height'];
    $show_navigation = filter_var($atts['show_navigation'], FILTER_VALIDATE_BOOLEAN);
    $show_pagination = filter_var($atts['show_pagination'], FILTER_VALIDATE_BOOLEAN);
    $delay_start = filter_var($atts['delay_start'], FILTER_VALIDATE_BOOLEAN);
    $delay_seconds = floatval($atts['delay_seconds']);

    $slider_id_attr = 'custom-slider-legacy-' . uniqid();

    ob_start();
    include CUSTOM_SLIDER_PLUGIN_PATH . 'templates/slider.php';
    return ob_get_clean();
  }

  /**
   * 管理画面の処理
   */
  public function handle_admin_actions()
  {
    if (!current_user_can('manage_options')) {
      return;
    }

    // スライダー作成
    if (isset($_POST['create_slider']) && wp_verify_nonce($_POST['_wpnonce'], 'create_slider')) {
      $this->create_slider();
    }

    // スライダー更新
    if (isset($_POST['update_slider']) && wp_verify_nonce($_POST['_wpnonce'], 'update_slider')) {
      $this->update_slider();
    }

    // スライド追加
    if (isset($_POST['add_slide']) && wp_verify_nonce($_POST['_wpnonce'], 'add_slide')) {
      $this->add_slide();
    }

    // スライド更新
    if (isset($_POST['update_slide']) && wp_verify_nonce($_POST['_wpnonce'], 'update_slide')) {
      $this->update_slide();
    }

    // スライダー削除
    if (isset($_GET['action']) && $_GET['action'] === 'delete_slider' && isset($_GET['slider_id'])) {
      if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_slider_' . intval($_GET['slider_id']))) {
        $this->delete_slider();
      }
    }

    // スライド削除
    if (isset($_GET['action']) && $_GET['action'] === 'delete_slide' && isset($_GET['slide_id'])) {
      if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_slide_' . intval($_GET['slide_id']))) {
        $this->delete_slide();
      }
    }

    // スライダー複製
    if (isset($_GET['action']) && $_GET['action'] === 'duplicate_slider' && isset($_GET['slider_id'])) {
      if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'duplicate_slider_' . intval($_GET['slider_id']))) {
        $this->duplicate_slider();
      }
    }
  }

  /**
   * スライダー作成
   */
  private function create_slider()
  {
    global $wpdb;

    $name = sanitize_text_field($_POST['slider_name']);
    $description = sanitize_textarea_field($_POST['slider_description']);

    $settings = array(
      'autoplay' => intval($_POST['autoplay']),
      'effect' => sanitize_text_field($_POST['effect']),
      'speed' => intval($_POST['speed']),
      'height' => sanitize_text_field($_POST['height']),
      'show_navigation' => isset($_POST['show_navigation']),
      'show_pagination' => isset($_POST['show_pagination']),
      'delay_start' => isset($_POST['delay_start']),
      'delay_seconds' => floatval($_POST['delay_seconds'])
    );

    $table_name = $wpdb->prefix . 'custom_sliders';

    $result = $wpdb->insert(
      $table_name,
      array(
        'name' => $name,
        'description' => $description,
        'settings' => json_encode($settings),
        'is_active' => 1
      ),
      array('%s', '%s', '%s', '%d')
    );

    if ($result) {
      $slider_id = $wpdb->insert_id;
      wp_redirect(admin_url('admin.php?page=custom-slider-edit&slider_id=' . $slider_id . '&message=created'));
      exit;
    }
  }

  /**
   * スライダー更新
   */
  private function update_slider()
  {
    global $wpdb;

    $slider_id = intval($_POST['slider_id']);
    $name = sanitize_text_field($_POST['slider_name']);
    $description = sanitize_textarea_field($_POST['slider_description']);

    $settings = array(
      'autoplay' => intval($_POST['autoplay']),
      'effect' => sanitize_text_field($_POST['effect']),
      'speed' => intval($_POST['speed']),
      'height' => sanitize_text_field($_POST['height']),
      'show_navigation' => isset($_POST['show_navigation']),
      'show_pagination' => isset($_POST['show_pagination']),
      'delay_start' => isset($_POST['delay_start']),
      'delay_seconds' => floatval($_POST['delay_seconds'])
    );

    $table_name = $wpdb->prefix . 'custom_sliders';

    $wpdb->update(
      $table_name,
      array(
        'name' => $name,
        'description' => $description,
        'settings' => json_encode($settings)
      ),
      array('id' => $slider_id),
      array('%s', '%s', '%s'),
      array('%d')
    );

    wp_redirect(admin_url('admin.php?page=custom-slider-edit&slider_id=' . $slider_id . '&message=updated'));
    exit;
  }

  /**
   * スライド追加
   */
  private function add_slide()
  {
    global $wpdb;

    $slider_id = intval($_POST['slider_id']);
    $image_url = esc_url_raw($_POST['image_url']);
    $title = sanitize_text_field($_POST['slide_title']);
    $subtitle = sanitize_text_field($_POST['slide_subtitle']);
    $link_url = esc_url_raw($_POST['slide_link_url']);

    // 最大のsort_orderを取得
    $table_name = $wpdb->prefix . 'custom_slides';
    $max_order = $wpdb->get_var($wpdb->prepare(
      "SELECT MAX(sort_order) FROM $table_name WHERE slider_id = %d",
      $slider_id
    ));

    $wpdb->insert(
      $table_name,
      array(
        'slider_id' => $slider_id,
        'image_url' => $image_url,
        'title' => $title,
        'subtitle' => $subtitle,
        'link_url' => $link_url,
        'sort_order' => ($max_order + 1),
        'is_active' => 1
      ),
      array('%d', '%s', '%s', '%s', '%s', '%d', '%d')
    );

    wp_redirect(admin_url('admin.php?page=custom-slider-edit&slider_id=' . $slider_id . '&message=slide_added'));
    exit;
  }

  /**
   * スライド更新
   */
  private function update_slide()
  {
    global $wpdb;

    $slide_id = intval($_POST['slide_id']);
    $slider_id = intval($_POST['slider_id']);
    $image_url = esc_url_raw($_POST['image_url']);
    $title = sanitize_text_field($_POST['slide_title']);
    $subtitle = sanitize_text_field($_POST['slide_subtitle']);
    $link_url = esc_url_raw($_POST['slide_link_url']);

    $table_name = $wpdb->prefix . 'custom_slides';

    $wpdb->update(
      $table_name,
      array(
        'image_url' => $image_url,
        'title' => $title,
        'subtitle' => $subtitle,
        'link_url' => $link_url
      ),
      array('id' => $slide_id),
      array('%s', '%s', '%s', '%s'),
      array('%d')
    );

    wp_redirect(admin_url('admin.php?page=custom-slider-edit&slider_id=' . $slider_id . '&message=slide_updated'));
    exit;
  }

  /**
   * スライダー削除
   */
  private function delete_slider()
  {
    global $wpdb;

    $slider_id = intval($_GET['slider_id']);

    $slider_table = $wpdb->prefix . 'custom_sliders';
    $slide_table = $wpdb->prefix . 'custom_slides';

    // スライドを削除
    $wpdb->delete($slide_table, array('slider_id' => $slider_id), array('%d'));

    // スライダーを削除
    $wpdb->delete($slider_table, array('id' => $slider_id), array('%d'));

    wp_redirect(admin_url('admin.php?page=custom-slider-manager&message=deleted'));
    exit;
  }

  /**
   * スライダー複製
   */
  private function duplicate_slider()
  {
    global $wpdb;

    $original_slider_id = intval($_GET['slider_id']);

    $slider_table = $wpdb->prefix . 'custom_sliders';
    $slide_table = $wpdb->prefix . 'custom_slides';

    // 元スライダー存在確認
    $original_slider = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM $slider_table WHERE id = %d AND is_active = 1",
      $original_slider_id
    ));

    if (!$original_slider) {
      wp_redirect(admin_url('admin.php?page=custom-slider-manager&message=error'));
      exit;
    }

    // スライダー複製
    $new_name = $original_slider->name . ' (複製)';

    $result = $wpdb->insert(
      $slider_table,
      array(
        'name' => $new_name,
        'description' => $original_slider->description,
        'settings' => $original_slider->settings,
        'is_active' => 1
      ),
      array('%s', '%s', '%s', '%d')
    );

    if (!$result) {
      wp_redirect(admin_url('admin.php?page=custom-slider-manager&message=error'));
      exit;
    }

    $new_slider_id = $wpdb->insert_id;

    // 関連スライド複製
    $original_slides = $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM $slide_table WHERE slider_id = %d AND is_active = 1 ORDER BY sort_order ASC",
      $original_slider_id
    ));

    foreach ($original_slides as $slide) {
      $wpdb->insert(
        $slide_table,
        array(
          'slider_id' => $new_slider_id,
          'image_url' => $slide->image_url,
          'title' => $slide->title,
          'subtitle' => $slide->subtitle,
          'link_url' => $slide->link_url,
          'sort_order' => $slide->sort_order,
          'is_active' => 1
        ),
        array('%d', '%s', '%s', '%s', '%s', '%d', '%d')
      );
    }

    // 成功リダイレクト
    wp_redirect(admin_url('admin.php?page=custom-slider-manager&message=duplicated'));
    exit;
  }

  /**
   * スライド削除
   */
  private function delete_slide()
  {
    global $wpdb;

    $slide_id = intval($_GET['slide_id']);
    $slider_id = intval($_GET['slider_id']);

    $table_name = $wpdb->prefix . 'custom_slides';

    $wpdb->delete($table_name, array('id' => $slide_id), array('%d'));

    wp_redirect(admin_url('admin.php?page=custom-slider-edit&slider_id=' . $slider_id . '&message=slide_deleted'));
    exit;
  }

  /**
   * AJAX - スライド順序更新
   */
  public function ajax_update_slide_order()
  {
    // 権限チェック
    if (!current_user_can('manage_options')) {
      wp_die(__('権限がありません。'));
    }

    // nonce検証
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'custom_admin_nonce')) {
      wp_die(__('セキュリティチェックに失敗しました。'));
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_slides';

    $slides = $_POST['slides'];
    if (!is_array($slides)) {
      wp_send_json_error('無効なデータです。');
    }

    foreach ($slides as $slide) {
      $slide_id = intval($slide['id']);
      $order = intval($slide['order']);

      $wpdb->update(
        $table_name,
        array('sort_order' => $order),
        array('id' => $slide_id),
        array('%d'),
        array('%d')
      );
    }

    wp_send_json_success('順序を更新しました。');
  }

  /**
   * 管理画面 - スライダー一覧
   */
  public function admin_page_list()
  {
    include CUSTOM_SLIDER_PLUGIN_PATH . 'templates/admin-list.php';
  }

  /**
   * 管理画面 - スライダー編集
   */
  public function admin_page_edit()
  {
    include CUSTOM_SLIDER_PLUGIN_PATH . 'templates/admin-edit.php';
  }
}

// プラグイン初期化
new AdvancedCustomSlider();