/**
 * Yokitabi カスタムスライダー JavaScript
 */

(function ($) {
  "use strict";

  // ローディング完了を監視する関数
  function waitForLoading(callback) {
    // ローディング要素を探す
    const loadingElement = document.querySelector(
      ".p-loading, #loading, .loading, .loader"
    );

    if (!loadingElement) {
      // ローディング要素がない場合は即座に実行
      callback();
      return;
    }

    // ローディング要素が非表示になるまで待機
    const observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        if (
          mutation.type === "attributes" &&
          mutation.attributeName === "style"
        ) {
          const target = mutation.target;
          if (
            target.style.display === "none" ||
            target.style.opacity === "0" ||
            target.style.visibility === "hidden"
          ) {
            observer.disconnect();
            callback();
          }
        }
      });
    });

    observer.observe(loadingElement, {
      attributes: true,
      attributeFilter: ["style", "class"],
    });

    // クラス変更も監視
    const classObserver = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        if (
          mutation.type === "attributes" &&
          mutation.attributeName === "class"
        ) {
          const target = mutation.target;
          if (
            target.classList.contains("is-hide") ||
            target.classList.contains("hidden") ||
            target.classList.contains("fade-out")
          ) {
            classObserver.disconnect();
            observer.disconnect();
            callback();
          }
        }
      });
    });

    classObserver.observe(loadingElement, {
      attributes: true,
      attributeFilter: ["class"],
    });

    // 5秒後にタイムアウト
    setTimeout(function () {
      observer.disconnect();
      classObserver.disconnect();
      callback();
    }, 5000);
  }

  // 遅延開始時の1枚目表示関数
  function showFirstSlidePreview(sliderElement) {
    if (!sliderElement) return;

    // 遅延プレビュー用のクラスを追加
    sliderElement.classList.add("delay-preview");
    sliderElement.style.opacity = "1";

    console.log("First slide preview displayed for:", sliderElement.id);
    console.log(
      "delay-preview class added, current classes:",
      sliderElement.className
    );
  }

  // スライダー初期化関数
  function initializeSlider(sliderElement, isDelayedStart = false) {
    if (!sliderElement || sliderElement.swiper) {
      return; // 既に初期化済み
    }

    // 遅延開始の場合は、delay-previewクラスを削除
    if (isDelayedStart) {
      console.log("Removing delay-preview class from:", sliderElement.id);
      sliderElement.classList.remove("delay-preview");
      console.log("After removal, current classes:", sliderElement.className);
    }

    const autoplay = parseInt(sliderElement.dataset.autoplay) || 5000;
    const effect = sliderElement.dataset.effect || "fade";
    const speed = parseInt(sliderElement.dataset.speed) || 1000;
    const showNavigation = sliderElement.dataset.navigation === "true";
    const showPagination = sliderElement.dataset.pagination === "true";

    const swiperConfig = {
      loop: true,
      autoplay: {
        delay: autoplay,
        disableOnInteraction: false,
      },
      effect: effect,
      speed: speed,
      lazy: true,
      on: {
        init: function () {
          sliderElement.style.opacity = "1";
        },
      },
    };

    // フェード効果の設定
    if (effect === "fade") {
      swiperConfig.fadeEffect = {
        crossFade: true,
      };
    }

    // ナビゲーション設定
    if (showNavigation) {
      swiperConfig.navigation = {
        nextEl: sliderElement.querySelector(".swiper-button-next"),
        prevEl: sliderElement.querySelector(".swiper-button-prev"),
      };
    }

    // ページネーション設定
    if (showPagination) {
      swiperConfig.pagination = {
        el: sliderElement.querySelector(".swiper-pagination"),
        clickable: true,
      };
    }

    // Swiper初期化
    const swiper = new Swiper(sliderElement, swiperConfig);

    console.log("Yokitabi Slider initialized:", sliderElement.id);
    return swiper;
  }

  // DOMが読み込まれた後に実行
  $(document).ready(function () {
    const sliderWrappers = document.querySelectorAll(
      ".yokitabi-slider-wrapper"
    );

    sliderWrappers.forEach(function (wrapper) {
      const slider = wrapper.querySelector(".yokitabi-slider");
      if (!slider) return;

      const delayStart = wrapper.dataset.delayStart === "true";
      const delaySeconds = parseFloat(wrapper.dataset.delaySeconds) || 1.0;

      if (delayStart) {
        // 遅延開始時は1枚目を即座に表示
        showFirstSlidePreview(slider);

        waitForLoading(function () {
          setTimeout(function () {
            console.log(
              "Starting delayed slider initialization for:",
              slider.id
            );
            initializeSlider(slider, true);
          }, delaySeconds * 1000);
        });
      } else {
        initializeSlider(slider);
      }
    });
  });

  // 動的に追加されたスライダーにも対応
  $(document).on("yokitabi-slider-init", function (event, slider) {
    if (slider && !slider.swiper) {
      initializeSlider(slider);
    }
  });
})(jQuery);
