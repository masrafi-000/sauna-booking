<?php
if (! defined('ABSPATH')) exit;

class SB_Accommodation_Shortcode
{

    public static function init()
    {
        add_shortcode('accommodation_rooms', [__CLASS__, 'render']);
    }

    /**
     * Render accommodation rooms shortcode
     */
    public static function render($atts)
    {
        $atts = shortcode_atts([
            'columns'    => 3,
            'per_page'   => 9,
            'ids'        => '',
        ], $atts, 'accommodation_rooms');

        $query_args = [
            'post_type'      => 'accommodation_room',
            'post_status'    => 'publish',
            'posts_per_page' => intval($atts['per_page']),
        ];

        if (! empty($atts['ids'])) {
            $query_args['post__in'] = array_map('intval', explode(',', $atts['ids']));
        }

        $rooms = new WP_Query($query_args);

        if (! $rooms->have_posts()) {
            return '<p class="acc-no-rooms">No accommodation rooms found.</p>';
        }

        $cols = intval($atts['columns']);
        ob_start();
?>
        <div class="sb-products-grid sb-cols-<?php echo esc_attr($cols); ?>">
            <?php while ($rooms->have_posts()) : $rooms->the_post();
                $id                = get_the_ID();
                $title             = get_the_title();
                $link              = get_permalink();
                $price_per_night   = get_post_meta($id, '_sb_price_per_night', true);
                $room_category     = get_post_meta($id, '_sb_room_category', true);
                $max_occupants     = get_post_meta($id, '_sb_max_occupants', true) ?: 2;
                $thumb             = get_the_post_thumbnail_url($id, 'large');
                $currency          = get_option('sb_currency_symbol', '€');
                $gallery           = get_post_meta($id, '_sb_gallery', true);

                $gallery_urls = $gallery
                    ? array_values(array_filter(array_map('trim', explode("\n", $gallery))))
                    : [];

                if (! $thumb && ! empty($gallery_urls)) {
                    $thumb = $gallery_urls[0];
                }
            ?>
                <a href="<?php echo esc_url($link); ?>" class="sb-product-card">
                    <div class="sb-card-image">
                        <?php if ($thumb) : ?>
                            <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy" />
                        <?php else : ?>
                            <div class="sb-card-placeholder">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <rect x="3" y="3" width="18" height="18" rx="2" />
                                    <circle cx="8.5" cy="8.5" r="1.5" />
                                    <polyline points="21 15 16 10 5 21" />
                                </svg>
                            </div>
                        <?php endif; ?>
                        <?php if ($room_category) : ?>
                            <span class="sb-card-badge"><?php echo esc_html($room_category); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="sb-card-body">
                        <h3 class="sb-card-title"><?php echo esc_html($title); ?></h3>

                        <div class="sb-card-meta">
                            <span>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                </svg>
                                Up to <?php echo esc_html($max_occupants); ?> guests
                            </span>
                        </div>

                        <?php if ($price_per_night) : ?>
                            <div class="sb-card-price">
                                <span class="sb-price-amount"><?php echo esc_html($currency . number_format((float) $price_per_night, 2)); ?></span>
                                <span class="sb-price-from">/ night</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
<?php
        wp_reset_postdata();
        return ob_get_clean();
    }
}
