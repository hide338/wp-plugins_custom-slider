/**
 * Advanced Custom Slider 管理画面JavaScript
 */

(function ($) {
  "use strict";

  $(document).ready(function () {
    // メディアライブラリ連携
    initMediaUploader();

    // ソート機能
    initSortable();

    // ショートコードコピー機能
    initShortcodeCopy();
  });

  /**
   * WordPressメディアライブラリ連携
   */
  function initMediaUploader() {
    $(document).on("click", ".upload-image", function (e) {
      e.preventDefault();

      const button = $(this);
      const input = button.siblings('input[type="url"]');

      // メディアライブラリを開く
      const mediaUploader = wp.media({
        title: "画像を選択",
        button: {
          text: "画像を選択",
        },
        multiple: false,
        library: {
          type: "image",
        },
      });

      mediaUploader.on("select", function () {
        const attachment = mediaUploader
          .state()
          .get("selection")
          .first()
          .toJSON();
        input.val(attachment.url);

        // プレビュー画像がある場合は更新
        const preview = button
          .closest(".slide-item")
          .find(".slide-preview img");
        if (preview.length) {
          preview.attr("src", attachment.url);
        }
      });

      mediaUploader.open();
    });
  }

  /**
   * スライドのソート機能
   */
  function initSortable() {
    if ($("#slides-container").length) {
      $("#slides-container").sortable({
        items: ".slide-item",
        handle: ".slide-handle",
        placeholder: "slide-item slide-placeholder",
        tolerance: "pointer",
        cursor: "move",
        opacity: 0.8,
        start: function (e, ui) {
          ui.placeholder.height(ui.item.height());
          ui.placeholder.addClass("ui-sortable-placeholder");
        },
        update: function (e, ui) {
          // ソート順序をサーバーに送信
          updateSlideOrder();
        },
      });
    }
  }

  /**
   * スライドの順序をサーバーに送信
   */
  function updateSlideOrder() {
    const slideIds = [];
    $("#slides-container .slide-item").each(function (index) {
      const slideId = $(this).data("slide-id");
      if (slideId) {
        slideIds.push({
          id: slideId,
          order: index + 1,
        });
      }
    });

    if (slideIds.length === 0) {
      return;
    }

    // AJAXでサーバーに送信
    $.ajax({
      url: custom_admin_ajax.ajaxurl,
      type: "POST",
      data: {
        action: "update_slide_order",
        slides: slideIds,
        nonce: custom_admin_ajax.nonce,
      },
      success: function (response) {
        if (response.success) {
          console.log("スライドの順序を更新しました");
        } else {
          console.error("順序更新エラー:", response.data);
        }
      },
      error: function () {
        console.error("順序更新でエラーが発生しました");
      },
    });
  }

  /**
   * ショートコードコピー機能
   */
  function initShortcodeCopy() {
    $(document).on("click", ".copy-shortcode", function (e) {
      e.preventDefault();

      const button = $(this);
      const shortcode = button.data("shortcode");

      if (navigator.clipboard) {
        navigator.clipboard
          .writeText(shortcode)
          .then(function () {
            showCopySuccess(button);
          })
          .catch(function () {
            fallbackCopy(shortcode, button);
          });
      } else {
        fallbackCopy(shortcode, button);
      }
    });
  }

  /**
   * コピー成功時の表示
   */
  function showCopySuccess(button) {
    const originalText = button.text();
    button.text("コピー完了！");
    button.addClass("button-primary");

    setTimeout(function () {
      button.text(originalText);
      button.removeClass("button-primary");
    }, 2000);
  }

  /**
   * フォールバック用のコピー機能
   */
  function fallbackCopy(text, button) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "fixed";
    textArea.style.opacity = "0";
    document.body.appendChild(textArea);
    textArea.select();

    try {
      document.execCommand("copy");
      showCopySuccess(button);
    } catch (err) {
      console.error("コピーに失敗しました:", err);
      button.text("コピー失敗");
      setTimeout(function () {
        button.text("コピー");
      }, 2000);
    }

    document.body.removeChild(textArea);
  }

  /**
   * フォーム送信前のバリデーション
   */
  $(document).on("submit", "form", function (e) {
    const form = $(this);

    // 画像URLが必須の場合のチェック
    const imageUrlInput = form.find('input[name="image_url"]');
    if (
      imageUrlInput.length &&
      imageUrlInput.prop("required") &&
      !imageUrlInput.val()
    ) {
      e.preventDefault();
      alert("画像URLを入力してください。");
      imageUrlInput.focus();
      return false;
    }

    // スライダー名が必須の場合のチェック
    const sliderNameInput = form.find('input[name="slider_name"]');
    if (
      sliderNameInput.length &&
      sliderNameInput.prop("required") &&
      !sliderNameInput.val()
    ) {
      e.preventDefault();
      alert("スライダー名を入力してください。");
      sliderNameInput.focus();
      return false;
    }
  });

  /**
   * 削除確認ダイアログ
   */
  $(document).on(
    "click",
    '.button-link-delete, a[onclick*="confirm"]',
    function (e) {
      const link = $(this);
      const message = link.attr("onclick")
        ? link.attr("onclick").match(/confirm\(['"](.+?)['"]\)/)?.[1]
        : "この項目を削除しますか？";

      if (!confirm(message)) {
        e.preventDefault();
        return false;
      }
    }
  );
})(jQuery);
