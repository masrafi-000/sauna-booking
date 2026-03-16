<?php
if (! defined('ABSPATH')) exit;

class SB_Accommodation_Post_Type
{

    public static function init()
    {
        add_action('init',           [__CLASS__, 'register_cpt']);
        add_action('add_meta_boxes', [__CLASS__, 'add_meta_boxes']);
        add_action('save_post',      [__CLASS__, 'save_meta']);
    }

    /* ── Register CPT ──────────────────────────────────────────────────────── */
    public static function register_cpt()
    {
        register_post_type('accommodation_room', [
            'labels' => [
                'name'               => 'Accommodation Rooms',
                'singular_name'      => 'Room Type',
                'add_new'            => 'Add New',
                'add_new_item'       => 'Add New Room Type',
                'edit_item'          => 'Edit Room Type',
                'view_item'          => 'View Room Type',
                'search_items'       => 'Search Room Types',
                'not_found'          => 'No room types found',
                'not_found_in_trash' => 'No room types found in trash',
            ],
            'public'       => true,
            'has_archive'  => true,
            'supports'     => ['title', 'thumbnail', 'editor'],
            'show_in_rest' => false,
            'menu_icon'    => 'dashicons-building',
            'rewrite'      => ['slug' => 'accommodation'],
        ]);
    }

    /* ── Meta boxes ────────────────────────────────────────────────────────── */
    public static function add_meta_boxes()
    {
        add_meta_box(
            'sb_room_details',
            'Room Details',
            [__CLASS__, 'render_details_box'],
            'accommodation_room',
            'normal',
            'high'
        );
    }

    /* ── BOX: Room Details ─────────────────────────────────────────────────── */
    public static function render_details_box($post)
    {
        wp_nonce_field('sb_save_room_meta', 'sb_room_meta_nonce');

        $price_per_night = get_post_meta($post->ID, '_sb_price_per_night',  true);
        $room_category   = get_post_meta($post->ID, '_sb_room_category',    true);
        $max_occupants   = get_post_meta($post->ID, '_sb_max_occupants',    true) ?: 2;
        $amenities       = get_post_meta($post->ID, '_sb_amenities',        true);
        $gallery         = get_post_meta($post->ID, '_sb_gallery',          true);
        $description     = get_post_meta($post->ID, '_sb_description',      true);
?>
        <style>
            .sb-mg {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 14px;
            }

            .sb-mf {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }

            .sb-mf label {
                font-weight: 600;
                font-size: 13px;
                color: #333;
            }

            .sb-mf input,
            .sb-mf textarea,
            .sb-mf select {
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 8px 10px;
                font-size: 13px;
                width: 100%;
                box-sizing: border-box;
            }

            .sb-mf-full {
                grid-column: 1/-1;
            }

            .sb-meta-note {
                font-size: 11px;
                color: #888;
                margin-top: 3px;
            }
        </style>

        <div class="sb-mg">
            <div class="sb-mf">
                <label>Price Per Night (<?php echo esc_html(get_option('sb_currency', 'EUR')); ?>) *</label>
                <input type="number" step="0.01" name="sb_price_per_night" value="<?php echo esc_attr($price_per_night); ?>" placeholder="80.00" required />
                <p class="sb-meta-note">Price guests will pay per night</p>
            </div>

            <div class="sb-mf">
                <label>Room Category *</label>
                <select name="sb_room_category" required>
                    <option value="">-- Select Category --</option>
                    <option value="Standard Room" <?php selected($room_category, 'Standard Room'); ?>>Standard Room</option>
                    <option value="Deluxe Suite" <?php selected($room_category, 'Deluxe Suite'); ?>>Deluxe Suite</option>
                    <option value="Premium Room" <?php selected($room_category, 'Premium Room'); ?>>Premium Room</option>
                    <option value="Economy Room" <?php selected($room_category, 'Economy Room'); ?>>Economy Room</option>
                </select>
            </div>

            <div class="sb-mf">
                <label>Max Occupants *</label>
                <input type="number" name="sb_max_occupants" value="<?php echo esc_attr($max_occupants); ?>" min="1" max="4" required />
                <p class="sb-meta-note">Maximum people allowed in this room (default: 2)</p>
            </div>

            <div class="sb-mf">
                <label>Room Type Display Name *</label>
                <input type="text" name="sb_room_type_name" value="<?php echo esc_attr(get_the_title($post->ID)); ?>" placeholder="e.g., Room Type A" />
                <p class="sb-meta-note">How this room will appear to guests</p>
            </div>

            <div class="sb-mf sb-mf-full">
                <label>Description</label>
                <textarea name="sb_description" rows="4" placeholder="Describe the room..."><?php echo esc_textarea($description); ?></textarea>
            </div>

            <div class="sb-mf sb-mf-full">
                <label>Amenities (comma-separated)</label>
                <textarea name="sb_amenities" rows="3" placeholder="WiFi, Air Conditioning, Private Bathroom, TV"><?php echo esc_textarea($amenities); ?></textarea>
                <p class="sb-meta-note">List amenities separated by commas</p>
            </div>

            <div class="sb-mf sb-mf-full">
                <label>Gallery (one URL per line)</label>
                <textarea name="sb_gallery" rows="4" placeholder="https://example.com/image1.jpg&#10;https://example.com/image2.jpg"><?php echo esc_textarea($gallery); ?></textarea>
                <p class="sb-meta-note">Enter image URLs (one per line)</p>
            </div>
        </div>
<?php
    }

    /* ── Save meta ─────────────────────────────────────────────────────────– */
    public static function save_meta($post_id)
    {
        if (
            ! isset($_POST['sb_room_meta_nonce']) ||
            ! wp_verify_nonce($_POST['sb_room_meta_nonce'], 'sb_save_room_meta')
        ) {
            return;
        }

        if (get_post_type($post_id) !== 'accommodation_room') {
            return;
        }

        $fields = [
            'sb_price_per_night' => 'sanitize_text_field',
            'sb_room_category'   => 'sanitize_text_field',
            'sb_max_occupants'   => 'absint',
            'sb_description'     => 'sanitize_textarea_field',
            'sb_amenities'       => 'sanitize_textarea_field',
            'sb_gallery'         => 'sanitize_textarea_field',
        ];

        foreach ($fields as $field => $sanitize) {
            if (isset($_POST[$field])) {
                $value = call_user_func($sanitize, $_POST[$field]);
                update_post_meta($post_id, '_' . $field, $value);
            }
        }
    }
}
