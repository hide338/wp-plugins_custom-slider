<?php
if (!defined('ABSPATH')) {
  exit;
}

global $wpdb;

$slider_id = isset($_GET['slider_id']) ? intval($_GET['slider_id']) : 0;
if (!$slider_id) {
  echo '<div class="wrap"><h1>無効なスライダーIDです</h1></div>';
  return;
}

$slider_table = $wpdb->prefix . 'custom_sliders';
$slide_table = $wpdb->prefix . 'custom_slides';

$slider = $wpdb->get_row($wpdb->prepare(
  "SELECT * FROM $slider_table WHERE id = %d",
  $slider_id
));

if (!$slider) {
  echo '<div class="wrap"><h1>スライダーが見つかりません</h1></div>';
  return;
}

$settings = json_decode($slider->settings, true);
if (!$settings) {
  $settings = array();
}

// メッセージ表示
$message = '';
if (isset($_GET['message'])) {
  switch ($_GET['message']) {
    case 'created':
      $message = 'スライダーを作成しました。';
      break;
    case 'updated':
      $message = 'スライダー設定を更新しました。';
      break;
    case 'slide_added':
      $message = 'スライドを追加しました。';
      break;
    case 'slide_updated':
      $message = 'スライドを更新しました。';
      break;
    case 'slide_deleted':
      $message = 'スライドを削除しました。';
      break;
  }
}

$slides = $wpdb->get_results($wpdb->prepare(
  "SELECT * FROM $slide_table WHERE slider_id = %d AND is_active = 1 ORDER BY sort_order ASC",
  $slider_id
));
?>

<div class="wrap">
  <h1>スライダー編集: <?php echo esc_html($slider->name); ?></h1>

  <?php if ($message): ?>
    <div class="notice notice-success is-dismissible">
      <p><?php echo esc_html($message); ?></p>
    </div>
  <?php endif; ?>

  <div style="margin: 20px 0;">
    <a href="<?php echo admin_url('admin.php?page=custom-slider-manager'); ?>" class="button">← スライダー一覧に戻る</a>
    <span style="margin: 0 20px;">ショートコード: <code>[custom_slider id="<?php echo $slider_id; ?>"]</code></span>
  </div>

  <!-- スライダー設定 -->
  <div class="custom-admin-card">
    <h2>スライダー設定</h2>
    <form method="post">
      <?php wp_nonce_field('update_slider'); ?>
      <input type="hidden" name="slider_id" value="<?php echo $slider_id; ?>">
      <table class="form-table">
        <tr>
          <th><label for="slider_name">スライダー名</label></th>
          <td><input type="text" id="slider_name" name="slider_name" value="<?php echo esc_attr($slider->name); ?>" required class="regular-text"></td>
        </tr>
        <tr>
          <th><label for="slider_description">説明</label></th>
          <td><textarea id="slider_description" name="slider_description" rows="3" class="large-text"><?php echo esc_textarea($slider->description); ?></textarea></td>
        </tr>
        <tr>
          <th><label for="autoplay">自動再生間隔</label></th>
          <td><input type="number" id="autoplay" name="autoplay" value="<?php echo $settings['autoplay'] ?? 5000; ?>" min="1000" max="30000" step="500"> ミリ秒</td>
        </tr>
        <tr>
          <th><label for="effect">切り替え効果</label></th>
          <td>
            <select id="effect" name="effect">
              <option value="fade" <?php selected($settings['effect'] ?? 'fade', 'fade'); ?>>フェード</option>
              <option value="slide" <?php selected($settings['effect'] ?? 'fade', 'slide'); ?>>スライド</option>
            </select>
          </td>
        </tr>
        <tr>
          <th><label for="speed">切り替え速度</label></th>
          <td><input type="number" id="speed" name="speed" value="<?php echo $settings['speed'] ?? 1000; ?>" min="300" max="3000" step="100"> ミリ秒</td>
        </tr>
        <tr>
          <th><label for="height">高さ</label></th>
          <td><input type="text" id="height" name="height" value="<?php echo esc_attr($settings['height'] ?? '100vh'); ?>" class="regular-text" placeholder="例: 100vh, 600px"></td>
        </tr>
        <tr>
          <th>表示オプション</th>
          <td>
            <label><input type="checkbox" name="show_navigation" <?php checked($settings['show_navigation'] ?? true); ?>> ナビゲーションボタンを表示</label><br>
            <label><input type="checkbox" name="show_pagination" <?php checked($settings['show_pagination'] ?? true); ?>> ページネーションを表示</label><br>
            <label><input type="checkbox" name="delay_start" <?php checked($settings['delay_start'] ?? true); ?>> ローディング完了後に開始</label>
          </td>
        </tr>
        <tr>
          <th><label for="delay_seconds">開始遅延時間</label></th>
          <td><input type="number" id="delay_seconds" name="delay_seconds" value="<?php echo $settings['delay_seconds'] ?? 1.0; ?>" min="0" max="10" step="0.1"> 秒</td>
        </tr>
      </table>
      <p class="submit">
        <input type="submit" name="update_slider" class="button-primary" value="設定を更新">
      </p>
    </form>
  </div>

  <!-- 新しいスライドを追加 -->
  <div class="custom-admin-card">
    <h2>新しいスライドを追加</h2>
    <form method="post">
      <?php wp_nonce_field('add_slide'); ?>
      <input type="hidden" name="slider_id" value="<?php echo $slider_id; ?>">
      <table class="form-table">
        <tr>
          <th><label for="image_url">画像URL</label></th>
          <td>
            <input type="url" id="image_url" name="image_url" required class="large-text" placeholder="https://example.com/image.jpg">
            <button type="button" class="button upload-image">画像を選択</button>
          </td>
        </tr>
        <tr>
          <th><label for="slide_title">メインテキスト</label></th>
          <td><input type="text" id="slide_title" name="slide_title" class="regular-text" placeholder="例: 施設名"></td>
        </tr>
        <tr>
          <th><label for="slide_subtitle">サブテキスト</label></th>
          <td><input type="text" id="slide_subtitle" name="slide_subtitle" class="regular-text" placeholder="例: 場所情報"></td>
        </tr>
        <tr>
          <th><label for="slide_link_url">リンクURL</label></th>
          <td>
            <input type="url" id="slide_link_url" name="slide_link_url" class="large-text" placeholder="https://example.com/page">
            <p class="description">スライドクリック時に移動するURL（オプション）</p>
          </td>
        </tr>
      </table>
      <p class="submit">
        <input type="submit" name="add_slide" class="button-primary" value="スライドを追加">
      </p>
    </form>
  </div>

  <!-- スライド一覧 -->
  <div class="custom-admin-card">
    <h2>スライド一覧 (<?php echo count($slides); ?>枚)</h2>

    <?php if (empty($slides)): ?>
      <p>スライドがありません。上記フォームから追加してください。</p>
    <?php else: ?>
      <div id="slides-container">
        <?php foreach ($slides as $slide): ?>
          <div class="slide-item" data-slide-id="<?php echo $slide->id; ?>">
            <div class="slide-preview">
              <img src="<?php echo esc_url($slide->image_url); ?>" alt="スライド画像" style="width: 150px; height: 100px; object-fit: cover;">
            </div>
            <div class="slide-content">
              <form method="post" style="display: inline-block; width: 100%;">
                <?php wp_nonce_field('update_slide'); ?>
                <input type="hidden" name="slide_id" value="<?php echo $slide->id; ?>">
                <input type="hidden" name="slider_id" value="<?php echo $slider_id; ?>">

                <table class="form-table">
                  <tr>
                    <th><label>画像URL</label></th>
                    <td>
                      <input type="url" name="image_url" value="<?php echo esc_attr($slide->image_url); ?>" required class="large-text">
                      <button type="button" class="button upload-image">画像を選択</button>
                    </td>
                  </tr>
                  <tr>
                    <th><label>メインテキスト</label></th>
                    <td><input type="text" name="slide_title" value="<?php echo esc_attr($slide->title); ?>" class="regular-text"></td>
                  </tr>
                  <tr>
                    <th><label>サブテキスト</label></th>
                    <td><input type="text" name="slide_subtitle" value="<?php echo esc_attr($slide->subtitle); ?>" class="regular-text"></td>
                  </tr>
                  <tr>
                    <th><label>リンクURL</label></th>
                    <td><input type="url" name="slide_link_url" value="<?php echo esc_attr($slide->link_url); ?>" class="large-text"></td>
                  </tr>
                </table>

                <p class="submit">
                  <input type="submit" name="update_slide" class="button-primary" value="更新">
                  <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=custom-slider-edit&slider_id=' . $slider_id . '&action=delete_slide&slide_id=' . $slide->id), 'delete_slide_' . $slide->id); ?>"
                    class="button"
                    onclick="return confirm('このスライドを削除しますか？')">削除</a>
                </p>
              </form>
            </div>
            <div class="slide-handle">
              <span class="dashicons dashicons-sort"></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>