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
        <div class="acc-rooms-grid acc-cols-<?php echo esc_attr($cols); ?>">
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
                $amenities         = get_post_meta($id, '_sb_amenities', true);

                $gallery_urls = $gallery
                    ? array_values(array_filter(array_map('trim', explode("\n", $gallery))))
                    : [];

                $amenity_list = $amenities
                    ? array_values(array_filter(array_map('trim', explode(',', $amenities))))
                    : [];

                if (! $thumb && ! empty($gallery_urls)) {
                    $thumb = $gallery_urls[0];
                }
            ?>
                <div class="acc-room-card">
                    <a href="<?php echo esc_url($link); ?>" class="acc-card-image-link">
                        <div class="acc-card-image">
                            <?php if ($thumb) : ?>
                                <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy" />
                            <?php else : ?>
                                <div class="acc-card-placeholder">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                    </svg>
                                </div>
                            <?php endif; ?>
                            <div class="acc-card-overlay"></div>
                        </div>
                    </a>

                    <div class="acc-card-body">
                        <h3 class="acc-card-title"><?php echo esc_html($title); ?></h3>

                        <?php if ($room_category) : ?>
                            <p class="acc-room-category"><?php echo esc_html($room_category); ?></p>
                        <?php endif; ?>

                        <div class="acc-card-meta">
                            <span class="acc-occupants">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                </svg>
                                Up to <?php echo esc_html($max_occupants); ?> guests
                            </span>
                        </div>

                        <?php if (! empty($amenity_list)) : ?>
                            <div class="acc-amenities">
                                <?php foreach (array_slice($amenity_list, 0, 3) as $amenity) : ?>
                                    <span class="acc-amenity-badge"><?php echo esc_html($amenity); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($price_per_night) : ?>
                            <div class="acc-card-price">
                                <span class="acc-price-amount"><?php echo esc_html($currency . number_format((float) $price_per_night, 2)); ?></span>
                                <span class="acc-price-period">per night</span>
                            </div>
                        <?php endif; ?>

                        <a href="<?php echo esc_url($link); ?>" class="acc-view-btn">View & Book</a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <style>
            .acc-rooms-grid {
                display: grid;
                gap: 24px;
                margin: 20px 0;
            }

            .acc-cols-2 {
                grid-template-columns: repeat(2, 1fr);
            }

            .acc-cols-3 {
                grid-template-columns: repeat(3, 1fr);
            }

            .acc-cols-4 {
                grid-template-columns: repeat(4, 1fr);
            }

            @media (max-width: 768px) {

                .acc-cols-2,
                .acc-cols-3,
                .acc-cols-4 {
                    grid-template-columns: 1fr;
                }
            }

            .acc-room-card {
                background: #fff;
                border: 1px solid #eee;
                border-radius: 8px;
                overflow: hidden;
                transition: all 0.3s ease;
                display: flex;
                flex-direction: column;
            }

            .acc-room-card:hover {
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                transform: translateY(-2px);
            }

            .acc-card-image-link {
                display: block;
                height: 200px;
                overflow: hidden;
            }

            .acc-card-image {
                height: 100%;
                position: relative;
            }

            .acc-card-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .acc-card-placeholder {
                width: 100%;
                height: 100%;
                background: #f5f5f5;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #ccc;
            }

            .acc-card-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0);
                transition: background 0.3s ease;
            }

            .acc-room-card:hover .acc-card-overlay {
                background: rgba(0, 0, 0, 0.1);
            }

            .acc-card-body {
                padding: 16px;
                flex: 1;
                display: flex;
                flex-direction: column;
            }

            .acc-card-title {
                margin: 0 0 4px 0;
                font-size: 16px;
                font-weight: 600;
                color: #333;
            }

            .acc-room-category {
                margin: 0 0 8px 0;
                font-size: 12px;
                color: #666;
                font-weight: 500;
            }

            .acc-card-meta {
                display: flex;
                gap: 12px;
                margin-bottom: 8px;
                font-size: 13px;
                color: #666;
            }

            .acc-occupants,
            .acc-location {
                display: flex;
                align-items: center;
                gap: 4px;
            }

            .acc-amenities {
                display: flex;
                flex-wrap: wrap;
                gap: 4px;
                margin-bottom: 8px;
            }

            .acc-amenity-badge {
                display: inline-block;
                padding: 2px 6px;
                background: #f0f0f0;
                border-radius: 3px;
                font-size: 11px;
                color: #666;
            }

            .acc-card-price {
                margin: auto 0 12px 0;
                padding-top: 8px;
                border-top: 1px solid #eee;
            }

            .acc-price-amount {
                display: block;
                font-size: 18px;
                font-weight: 700;
                color: #333;
            }

            .acc-price-period {
                display: block;
                font-size: 12px;
                color: #888;
            }

            .acc-view-btn {
                display: inline-block;
                padding: 8px 16px;
                background: #333;
                color: #fff;
                text-decoration: none;
                border-radius: 4px;
                font-size: 13px;
                font-weight: 500;
                transition: background 0.2s ease;
                text-align: center;
            }

            .acc-view-btn:hover {
                background: #1a1a1a;
            }

            .acc-no-rooms {
                padding: 40px;
                text-align: center;
                color: #666;
            }
        </style>
<?php
        wp_reset_postdata();
        return ob_get_clean();
    }
}
