# Yokitabi カスタムスライダー

WordPressのローディング画面完了後に開始するカスタムスライダーシステムです。

## 機能

- ✅ ローディング画面完了後の遅延開始
- ✅ 管理画面での完全なスライダー管理
- ✅ 画像・タイトル・サブタイトル・リンクURL対応
- ✅ WordPressメディアライブラリ連携
- ✅ ドラッグ&ドロップでスライド順序変更
- ✅ スライダー複製機能
- ✅ フェード・スライド切り替え効果
- ✅ レスポンシブ対応
- ✅ ショートコードで簡単表示

## インストール

1. `custom-slider` フォルダを `/wp-content/plugins/` ディレクトリにアップロード
2. WordPress管理画面の「プラグイン」ページでプラグインを有効化
3. 管理画面に「Yokitabi スライダー」メニューが追加されます

## 使用方法

### 1. スライダーの作成

1. 管理画面の「Yokitabi スライダー」にアクセス
2. 「新規スライダー作成」フォームで設定を入力
3. 「スライダーを作成」ボタンをクリック

### 2. スライドの追加

1. スライダー編集ページにアクセス
2. 「新しいスライドを追加」フォームで情報を入力
3. 「画像を選択」ボタンでWordPressメディアライブラリから画像を選択
4. 「スライドを追加」ボタンをクリック

### 2.1. スライダーの複製

1. スライダー一覧ページで複製したいスライダーを選択
2. 「複製」ボタンをクリック
3. 確認ダイアログで「OK」をクリック
4. 新しいスライダーが作成される（名前に「(複製)」が付きます）

### 3. ショートコードで表示

```php
[yokitabi_slider id="1"]
```

## ショートコードオプション

### データベース管理スライダー
```php
[yokitabi_slider id="1"]
```

### レガシー（後方互換）スライダー
```php
[yokitabi_slider 
  images="https://example.com/img1.jpg,https://example.com/img2.jpg"
  facility_names="施設1,施設2"
  en_names="Facility 1,Facility 2"
  autoplay="5000"
  effect="fade"
  speed="1000"
  height="100vh"
  show_navigation="true"
  show_pagination="true"
  delay_start="true"
  delay_seconds="1.0"
]
```

## スライダー設定項目

| 設定項目 | 説明 | デフォルト値 |
|---------|------|------------|
| スライダー名 | 管理用の名前 | - |
| 説明 | スライダーの説明 | - |
| 自動再生間隔 | スライド切り替え間隔（ミリ秒） | 5000 |
| 切り替え効果 | fade または slide | fade |
| 切り替え速度 | アニメーション速度（ミリ秒） | 1000 |
| 高さ | スライダーの高さ | 100vh |
| ナビゲーションボタン | 前後ボタンの表示 | true |
| ページネーション | ドットページネーションの表示 | true |
| ローディング完了後に開始 | 遅延開始の有効化 | true |
| 開始遅延時間 | 遅延時間（秒） | 1.0 |

## スライド設定項目

| 設定項目 | 説明 | 必須 |
|---------|------|------|
| 画像URL | スライド背景画像のURL | ✅ |
| メインテキスト | 大きく表示されるタイトル | ❌ |
| サブテキスト | 小さく表示されるサブタイトル | ❌ |
| リンクURL | クリック時の遷移先URL | ❌ |

## CSS カスタマイズ

### カスタムスタイルの追加

```css
/* タイトルのカスタマイズ */
.facility-title {
    font-size: 4rem;
    color: #ff6347;
}

/* オーバーレイの透明度変更 */
.slide-overlay {
    background: rgba(0, 0, 0, 0.5);
}

/* ボタンのカスタマイズ */
.yokitabi-slider .swiper-button-next,
.yokitabi-slider .swiper-button-prev {
    background: rgba(255, 255, 255, 0.8);
    color: #333;
}
```

## JavaScript フック

### カスタム初期化

```javascript
// カスタムイベントでスライダーを初期化
$(document).trigger('yokitabi-slider-init', [sliderElement]);
```

### スライダー初期化後の処理

```javascript
document.addEventListener('DOMContentLoaded', function() {
    const sliders = document.querySelectorAll('.yokitabi-slider');
    sliders.forEach(function(slider) {
        // swiper.onメソッドでイベントを追加
        if (slider.swiper) {
            slider.swiper.on('slideChange', function() {
                console.log('スライドが変更されました');
            });
        }
    });
});
```

## トラブルシューティング

### スライダーが表示されない

1. ブラウザの開発者ツールでエラーを確認
2. Swiperライブラリが正常に読み込まれているか確認
3. 画像URLが正しいか確認

### ローディング完了検知が動作しない

1. ローディング要素のセレクタが正しいか確認
2. 遅延時間を長めに設定して動作確認
3. `delay_start="false"` で遅延機能を無効化して確認

### 管理画面でメディアライブラリが開かない

1. WordPressの管理画面でJavaScriptエラーがないか確認
2. 他のプラグインとの競合を確認
3. プラグインを再有効化

## 更新履歴

### 1.0.0
- 初回リリース
- 基本的なスライダー機能
- 管理画面での完全管理
- ローディング画面完了後の開始機能

## サポート

このプラグインに関するお問い合わせは、開発者までご連絡ください。

## ライセンス

GPL v2 or later 