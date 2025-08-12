<?php
if (!defined('ABSPATH')) {
  exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'yokitabi_sliders';

// メッセージ表示
$message = '';
if (isset($_GET['message'])) {
  switch ($_GET['message']) {
    case 'deleted':
      $message = 'スライダーを削除しました。';
      break;
    case 'duplicated':
      $message = 'スライダーを複製しました。';
      break;
    case 'error':
      $message = 'エラーが発生しました。再度お試しください。';
      break;
  }
}

// スライダー一覧取得
$sliders = $wpdb->get_results("SELECT * FROM $table_name WHERE is_active = 1 ORDER BY created_at DESC");
?>

<div class="wrap">
  <h1>Yokitabi スライダー管理</h1>

  <?php if ($message): ?>
    <div class="notice notice-success is-dismissible">
      <p><?php echo esc_html($message); ?></p>
    </div>
  <?php endif; ?>

  <!-- 新規スライダー作成フォーム -->
  <div class="yokitabi-admin-card">
    <h2>新規スライダー作成</h2>
    <form method="post" style="max-width: 600px;">
      <?php wp_nonce_field('create_slider'); ?>
      <table class="form-table">
        <tr>
          <th><label for="slider_name">スライダー名</label></th>
          <td><input type="text" id="slider_name" name="slider_name" required class="regular-text" placeholder="例: 日本語用メインスライダー"></td>
        </tr>
        <tr>
          <th><label for="slider_description">説明</label></th>
          <td><textarea id="slider_description" name="slider_description" rows="3" class="large-text" placeholder="このスライダーの用途や説明"></textarea></td>
        </tr>
        <tr>
          <th><label for="autoplay">自動再生間隔</label></th>
          <td><input type="number" id="autoplay" name="autoplay" value="5000" min="1000" max="30000" step="500"> ミリ秒</td>
        </tr>
        <tr>
          <th><label for="effect">切り替え効果</label></th>
          <td>
            <select id="effect" name="effect">
              <option value="fade">フェード</option>
              <option value="slide">スライド</option>
            </select>
          </td>
        </tr>
        <tr>
          <th><label for="speed">切り替え速度</label></th>
          <td><input type="number" id="speed" name="speed" value="1000" min="300" max="3000" step="100"> ミリ秒</td>
        </tr>
        <tr>
          <th><label for="height">高さ</label></th>
          <td><input type="text" id="height" name="height" value="100vh" class="regular-text" placeholder="例: 100vh, 600px"></td>
        </tr>
        <tr>
          <th>表示オプション</th>
          <td>
            <label><input type="checkbox" name="show_navigation" checked> ナビゲーションボタンを表示</label><br>
            <label><input type="checkbox" name="show_pagination" checked> ページネーションを表示</label><br>
            <label><input type="checkbox" name="delay_start" checked> ローディング完了後に開始</label>
          </td>
        </tr>
        <tr>
          <th><label for="delay_seconds">開始遅延時間</label></th>
          <td><input type="number" id="delay_seconds" name="delay_seconds" value="1.0" min="0" max="10" step="0.1"> 秒</td>
        </tr>
      </table>
      <p class="submit">
        <input type="submit" name="create_slider" class="button-primary" value="スライダーを作成">
      </p>
    </form>
  </div>

  <!-- スライダー一覧 -->
  <div class="yokitabi-admin-card">
    <h2>作成済みスライダー (<?php echo count($sliders); ?>個)</h2>

    <?php if (empty($sliders)): ?>
      <p>スライダーがありません。上記フォームから作成してください。</p>
    <?php else: ?>
      <table class="wp-list-table widefat fixed striped">
        <thead>
          <tr>
            <th>ID</th>
            <th>スライダー名</th>
            <th>説明</th>
            <th>ショートコード</th>
            <th>作成日</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sliders as $slider): ?>
            <tr>
              <td><?php echo $slider->id; ?></td>
              <td>
                <strong>
                  <a href="<?php echo admin_url('admin.php?page=yokitabi-slider-edit&slider_id=' . $slider->id); ?>">
                    <?php echo esc_html($slider->name); ?>
                  </a>
                </strong>
              </td>
              <td><?php echo esc_html($slider->description); ?></td>
              <td>
                <code>[yokitabi_slider id="<?php echo $slider->id; ?>"]</code>
                <button type="button" class="button copy-shortcode" data-shortcode='[yokitabi_slider id="<?php echo $slider->id; ?>"]'>コピー</button>
              </td>
              <td><?php echo date('Y-m-d H:i', strtotime($slider->created_at)); ?></td>
              <td>
                <a href="<?php echo admin_url('admin.php?page=yokitabi-slider-edit&slider_id=' . $slider->id); ?>" class="button">編集</a>
                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=yokitabi-slider-manager&action=duplicate_slider&slider_id=' . $slider->id), 'duplicate_slider_' . $slider->id); ?>" 
                   class="button">複製</a>
                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=yokitabi-slider-manager&action=delete_slider&slider_id=' . $slider->id), 'delete_slider_' . $slider->id); ?>"
                  class="button button-link-delete"
                  onclick="return confirm('このスライダーを削除しますか？関連するスライドも全て削除されます。')">削除</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<script>
  // ショートコードコピー機能
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.copy-shortcode').forEach(button => {
      button.addEventListener('click', function() {
        const shortcode = this.getAttribute('data-shortcode');
        navigator.clipboard.writeText(shortcode).then(() => {
          this.textContent = 'コピー完了！';
          setTimeout(() => {
            this.textContent = 'コピー';
          }, 2000);
        });
      });
    });
  });
</script>