<div class="yokitabi-slider-wrapper" data-delay-start="<?php echo $delay_start ? 'true' : 'false'; ?>" data-delay-seconds="<?php echo esc_attr($delay_seconds); ?>">
  <div id="<?php echo esc_attr($slider_id_attr); ?>" class="yokitabi-slider swiper"
    data-autoplay="<?php echo esc_attr($autoplay); ?>"
    data-effect="<?php echo esc_attr($effect); ?>"
    data-speed="<?php echo esc_attr($speed); ?>"
    data-navigation="<?php echo $show_navigation ? 'true' : 'false'; ?>"
    data-pagination="<?php echo $show_pagination ? 'true' : 'false'; ?>"
    style="height: <?php echo esc_attr($height); ?>;">

    <div class="swiper-wrapper">
      <?php foreach ($slides as $slide): ?>
        <div class="swiper-slide">
          <?php if (!empty($slide->link_url)): ?>
            <a href="<?php echo esc_url($slide->link_url); ?>" class="slide-link" target="_blank" rel="noopener noreferrer nofollow">
            <?php endif; ?>
            <div class="slide-background" style="background-image: url('<?php echo esc_url($slide->image_url); ?>');">
              <div class="slide-overlay"></div>
              <div class="slide-content">
                <?php if (!empty($slide->title) || !empty($slide->subtitle)): ?>
                  <div class="facility-name">
                    <?php if (!empty($slide->title)): ?>
                      <h2 class="facility-title"><?php echo esc_html($slide->title); ?></h2>
                    <?php endif; ?>
                    <?php if (!empty($slide->subtitle)): ?>
                      <div class="facility-subtitle"><?php echo esc_html($slide->subtitle); ?></div>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
            <?php if (!empty($slide->link_url)): ?>
            </a>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($show_navigation): ?>
      <div class="swiper-button-next"></div>
      <div class="swiper-button-prev"></div>
    <?php endif; ?>

    <?php if ($show_pagination): ?>
      <div class="swiper-pagination"></div>
    <?php endif; ?>
  </div>
</div>