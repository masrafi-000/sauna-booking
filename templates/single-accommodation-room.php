<?php

/**
 * Template: Single Accommodation Room
 */
if (! defined('ABSPATH')) exit;

get_header();

while (have_posts()) : the_post();
    $room_id         = get_the_ID();
    $price_per_night = get_post_meta($room_id, '_sb_price_per_night', true);
    $room_category   = get_post_meta($room_id, '_sb_room_category', true);
    $max_occupants   = get_post_meta($room_id, '_sb_max_occupants', true) ?: 2;
    $description     = get_post_meta($room_id, '_sb_description', true);
    $amenities_raw   = get_post_meta($room_id, '_sb_amenities', true);
    $gallery_raw     = get_post_meta($room_id, '_sb_gallery', true);

    $currency        = get_option('sb_currency_symbol', '€');
    $currency_code   = get_option('sb_currency', 'EUR');

    $gallery_urls = $gallery_raw
        ? array_values(array_filter(array_map('trim', explode("\n", $gallery_raw))))
        : [];

    $amenities = $amenities_raw
        ? array_values(array_filter(array_map('trim', explode(',', $amenities_raw))))
        : [];

    $main_image = get_the_post_thumbnail_url($room_id, 'full');
    if (! $main_image && ! empty($gallery_urls)) {
        $main_image = $gallery_urls[0];
    }

    $all_thumbs = [];
    if ($main_image) {
        $all_thumbs[] = $main_image;
    }
    foreach ($gallery_urls as $u) {
        if ($u !== $main_image) {
            $all_thumbs[] = $u;
        }
    }
?>

    <div class="acc-page-wrap">
        <div class="acc-single-room" data-room-id="<?php echo $room_id; ?>" data-price="<?php echo esc_attr($price_per_night); ?>">

            <!-- LEFT: Gallery -->
            <div class="acc-room-hero">
                <div class="acc-hero-main">
                    <?php if ($main_image) : ?>
                        <img src="<?php echo esc_url($main_image); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" class="acc-hero-img" id="acc-main-img" />
                    <?php else : ?>
                        <div class="acc-hero-placeholder">No image available</div>
                    <?php endif; ?>
                </div>

                <?php if (count($all_thumbs) > 1) : ?>
                    <div class="acc-thumbnails">
                        <?php foreach ($all_thumbs as $idx => $thumb) : ?>
                            <img src="<?php echo esc_url($thumb); ?>" alt="Gallery image <?php echo $idx + 1; ?>" class="acc-thumb" data-full="<?php echo esc_url($thumb); ?>" />
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- RIGHT: Details & Booking -->
            <div class="acc-room-details">
                <h1><?php echo esc_html(get_the_title()); ?></h1>

                <?php if ($room_category) : ?>
                    <p class="acc-room-category-large"><?php echo esc_html($room_category); ?></p>
                <?php endif; ?>

                <div class="acc-room-meta">
                    <span class="acc-meta-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                        </svg>
                        Up to <?php echo esc_html($max_occupants); ?> guests
                    </span>
                </div>

                <!-- Price -->
                <div class="acc-price-section">
                    <div class="acc-price-display">
                        <span class="acc-price-amount"><?php echo esc_html($currency . number_format((float) $price_per_night, 2)); ?></span>
                        <span class="acc-price-period">per night</span>
                    </div>
                </div>

                <!-- Booking Form -->
                <div class="acc-booking-form">
                    <h3>Book This Room</h3>
                    <form id="acc-booking-form" method="POST">
                        <div class="acc-form-group">
                            <label>Check-in Date</label>
                            <input type="date" id="acc-checkin" name="check_in" required min="<?php echo date('Y-m-d'); ?>" />
                        </div>

                        <div class="acc-form-group">
                            <label>Check-out Date</label>
                            <input type="date" id="acc-checkout" name="check_out" required min="<?php echo date('Y-m-d', time() + 86400); ?>" />
                        </div>

                        <div class="acc-form-group">
                            <label>Number of Guests</label>
                            <select id="acc-occupants" name="occupants" required>
                                <option value="">Select...</option>
                                <option value="1">1 Guest</option>
                                <?php if ($max_occupants >= 2) : ?>
                                    <option value="2">2 Guests</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="acc-form-group">
                            <label>Your Name *</label>
                            <input type="text" name="guest_name" required placeholder="John Doe" />
                        </div>

                        <div class="acc-form-group">
                            <label>Email *</label>
                            <input type="email" name="guest_email" required placeholder="john@example.com" />
                        </div>

                        <div class="acc-form-group">
                            <label>Phone *</label>
                            <input type="tel" name="guest_phone" required placeholder="+1 (555) 000-0000" />
                        </div>

                        <div class="acc-availability-info" id="acc-availability">
                            <!-- Filled by JS -->
                        </div>

                        <button type="submit" class="acc-book-btn" id="acc-book-btn" disabled>
                            Check Availability First
                        </button>

                        <input type="hidden" name="room_id" value="<?php echo $room_id; ?>" />
                    </form>
                </div>

                <!-- Amenities -->
                <?php if (! empty($amenities)) : ?>
                    <div class="acc-amenities-section">
                        <h3>Amenities</h3>
                        <ul class="acc-amenities-list">
                            <?php foreach ($amenities as $amenity) : ?>
                                <li>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    <?php echo esc_html($amenity); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Description -->
                <?php if ($description) : ?>
                    <div class="acc-description-section">
                        <h3>About This Room</h3>
                        <p><?php echo wp_kses_post(nl2br($description)); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <style>
        .acc-page-wrap {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .acc-single-room {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        @media (max-width: 768px) {
            .acc-single-room {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }

        /* Hero */
        .acc-hero-main {
            width: 100%;
            height: 400px;
            margin-bottom: 16px;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
        }

        .acc-hero-main img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .acc-hero-placeholder {
            width: 100%;
            height: 100%;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
        }

        .acc-thumbnails {
            display: flex;
            gap: 8px;
            overflow-x: auto;
        }

        .acc-thumb {
            width: 80px;
            height: 80px;
            border-radius: 6px;
            object-fit: cover;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.2s ease;
        }

        .acc-thumb:hover,
        .acc-thumb.active {
            border-color: #333;
        }

        /* Details */
        .acc-room-details h1 {
            margin: 0 0 8px 0;
            font-size: 32px;
            color: #333;
        }

        .acc-room-category-large {
            margin: 0 0 16px 0;
            font-size: 16px;
            color: #666;
            font-weight: 500;
        }

        .acc-room-meta {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
            text-align: center;
        }

        .acc-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #666;
        }

        .acc-price-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 24px;
        }

        .acc-price-display {
            display: flex;
            flex-direction: column;
        }

        .acc-price-amount {
            font-size: 28px;
            font-weight: 700;
            color: #333;
        }

        .acc-price-period {
            font-size: 14px;
            color: #888;
        }

        /* Form */
        .acc-booking-form {
            background: #fff;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 24px;
        }

        .acc-booking-form h3 {
            margin: 0 0 16px 0;
            font-size: 18px;
            color: #333;
        }

        .acc-form-group {
            margin-bottom: 16px;
        }

        .acc-form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }

        .acc-form-group input,
        .acc-form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .acc-form-group input:focus,
        .acc-form-group select:focus {
            outline: none;
            border-color: #333;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
        }

        .acc-availability-info {
            padding: 12px;
            background: #f0f0f0;
            border-radius: 4px;
            margin-bottom: 16px;
            display: none;
            font-size: 14px;
            color: #333;
        }

        .acc-availability-info.show {
            display: block;
        }

        .acc-book-btn {
            width: 100%;
            padding: 12px;
            background: #333;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .acc-book-btn:hover:not(:disabled) {
            background: #1a1a1a;
        }

        .acc-book-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        /* Amenities */
        .acc-amenities-section {
            margin-bottom: 24px;
        }

        .acc-amenities-section h3 {
            margin: 0 0 12px 0;
            font-size: 16px;
            color: #333;
        }

        .acc-amenities-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .acc-amenities-list li {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 0;
            font-size: 14px;
            color: #666;
        }

        /* Description */
        .acc-description-section {
            margin-bottom: 24px;
        }

        .acc-description-section h3 {
            margin: 0 0 12px 0;
            font-size: 16px;
            color: #333;
        }

        .acc-description-section p {
            margin: 0;
            line-height: 1.6;
            color: #666;
            font-size: 14px;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('acc-booking-form');
            const checkinInput = document.getElementById('acc-checkin');
            const checkoutInput = document.getElementById('acc-checkout');
            const occupantsSelect = document.getElementById('acc-occupants');
            const bookBtn = document.getElementById('acc-book-btn');
            const availabilityDiv = document.getElementById('acc-availability');
            const roomId = form.querySelector('input[name="room_id"]').value;

            // Thumbnail switcher
            document.querySelectorAll('.acc-thumb').forEach(thumb => {
                thumb.addEventListener('click', function() {
                    document.getElementById('acc-main-img').src = this.dataset.full;
                    document.querySelectorAll('.acc-thumb').forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            // Validate and check availability
            function checkAvailability() {
                const checkin = checkinInput.value;
                const checkout = checkoutInput.value;

                if (!checkin || !checkout) {
                    availabilityDiv.classList.remove('show');
                    bookBtn.disabled = true;
                    return;
                }

                fetch(ajaxurl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'acc_check_availability',
                            nonce: '<?php echo wp_create_nonce('acc_nonce'); ?>',
                            room_id: roomId,
                            check_in: checkin,
                            check_out: checkout,
                        }),
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            availabilityDiv.classList.add('show');
                            availabilityDiv.innerHTML = `
                    <strong>✓ Available for ${data.data.nights} night(s)</strong><br>
                    ${checkin} → ${checkout}
                `;
                            bookBtn.disabled = occupantsSelect.value === '';
                        } else {
                            availabilityDiv.classList.add('show');
                            availabilityDiv.innerHTML = `<strong style="color: red;">✗ ${data.data.message}</strong>`;
                            availabilityDiv.style.background = '#fee';
                            bookBtn.disabled = true;
                        }
                    });
            }

            checkinInput.addEventListener('change', checkAvailability);
            checkoutInput.addEventListener('change', checkAvailability);
            occupantsSelect.addEventListener('change', checkAvailability);

            // Form submission
            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                const formData = new FormData(form);
                formData.append('action', 'acc_create_payment_intent');
                formData.append('nonce', '<?php echo wp_create_nonce('acc_nonce'); ?>');

                bookBtn.disabled = true;
                bookBtn.textContent = 'Processing...';

                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    body: new URLSearchParams(formData),
                });

                const data = await response.json();

                if (!data.success) {
                    alert('Error: ' + data.data.message);
                    bookBtn.disabled = false;
                    bookBtn.textContent = 'Book Now';
                    return;
                }

                // Load Stripe
                const stripe = Stripe('<?php echo esc_js(get_option('sb_stripe_public_key', '')); ?>');
                const {
                    error
                } = await stripe.confirmCardPayment(data.data.client_secret, {
                    payment_method: {
                        card: cardElement
                    },
                });

                if (error) {
                    alert('Payment failed: ' + error.message);
                    bookBtn.disabled = false;
                    bookBtn.textContent = 'Book Now';
                } else {
                    // Confirm booking
                    await fetch(ajaxurl, {
                        method: 'POST',
                        body: new URLSearchParams({
                            action: 'acc_confirm_booking',
                            nonce: '<?php echo wp_create_nonce('acc_nonce'); ?>',
                            booking_id: data.data.booking_id,
                            pi_id: data.data.payment_intent,
                        }),
                    });

                    alert('Booking confirmed!');
                    window.location.href = '/thank-you/';
                }
            });
        });
    </script>

<?php
endwhile;
get_footer();
