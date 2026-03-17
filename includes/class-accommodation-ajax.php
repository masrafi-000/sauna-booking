<?php
if (! defined('ABSPATH')) exit;

class SB_Accommodation_Ajax
{

    public static function init()
    {
        $actions = [
            'acc_check_availability'     => [__CLASS__, 'check_availability'],
            'acc_create_payment_intent'  => [__CLASS__, 'create_payment_intent'],
            'acc_confirm_booking'        => [__CLASS__, 'confirm_booking'],
            'acc_get_booked_dates'       => [__CLASS__, 'get_booked_dates'],
        ];

        foreach ($actions as $action => $callback) {
            add_action('wp_ajax_' . $action,        $callback);
            add_action('wp_ajax_nopriv_' . $action, $callback);
        }
    }

    /**
     * Verify nonce helper
     */
    private static function verify()
    {
        if (! isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'acc_nonce')) {
            wp_send_json_error(['message' => 'Security check failed.'], 403);
        }
    }

    /**
     * GET BOOKED DATES - Get all booked date ranges for a room
     */
    public static function get_booked_dates()
    {
        self::verify();

        $room_id = absint($_POST['room_id'] ?? 0);

        if (! $room_id) {
            wp_send_json_error(['message' => 'Invalid room ID.']);
        }

        $booked = SB_Accommodation_Database::get_booked_dates($room_id);
        wp_send_json_success(['booked_dates' => $booked]);
    }

    /**
     * CHECK AVAILABILITY - Verify if dates are available
     */
    public static function check_availability()
    {
        self::verify();

        $room_id    = absint($_POST['room_id'] ?? 0);
        $check_in   = sanitize_text_field($_POST['check_in'] ?? '');
        $check_out  = sanitize_text_field($_POST['check_out'] ?? '');

        if (! $room_id || ! $check_in || ! $check_out) {
            wp_send_json_error(['message' => 'Missing required data.']);
        }

        // Validate date format
        if (
            ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $check_in) ||
            ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $check_out)
        ) {
            wp_send_json_error(['message' => 'Invalid date format.']);
        }

        // Check if checkout is after checkin
        $in_time  = strtotime($check_in);
        $out_time = strtotime($check_out);

        if ($out_time <= $in_time) {
            wp_send_json_error(['message' => 'Check-out date must be after check-in date.']);
        }

        // Check availability
        $available = SB_Accommodation_Database::check_availability($room_id, $check_in, $check_out);

        if (! $available) {
            wp_send_json_error(['message' => 'Room is not available for selected dates.']);
        }

        $nights = SB_Accommodation_Database::calculate_nights($check_in, $check_out);
        wp_send_json_success([
            'available' => true,
            'nights'    => $nights,
            'check_in'  => $check_in,
            'check_out' => $check_out,
        ]);
    }

    /**
     * CREATE PAYMENT INTENT - Create Stripe payment intent
     */
    public static function create_payment_intent()
    {
        self::verify();

        $room_id       = absint($_POST['room_id'] ?? 0);
        $check_in      = sanitize_text_field($_POST['check_in'] ?? '');
        $check_out     = sanitize_text_field($_POST['check_out'] ?? '');
        $occupants     = absint($_POST['occupants'] ?? 1);
        $guest_name    = sanitize_text_field($_POST['guest_name'] ?? '');
        $guest_email   = sanitize_email($_POST['guest_email'] ?? '');
        $guest_phone   = sanitize_text_field($_POST['guest_phone'] ?? '');

        // Validate inputs
        if (! $room_id || ! $check_in || ! $check_out || ! $guest_name || ! $guest_email) {
            wp_send_json_error(['message' => 'Missing required booking data.']);
        }

        $max_allowed = intval(get_post_meta($room_id, '_sb_max_occupants', true) ?: 2);
        if ($occupants < 1 || $occupants > $max_allowed) {
            wp_send_json_error(['message' => sprintf('Invalid number of occupants. Maximum allowed: %d', $max_allowed)]);
        }

        // Verify dates again
        if (
            ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $check_in) ||
            ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $check_out)
        ) {
            wp_send_json_error(['message' => 'Invalid date format.']);
        }

        $in_time  = strtotime($check_in);
        $out_time = strtotime($check_out);

        if ($out_time <= $in_time) {
            wp_send_json_error(['message' => 'Check-out must be after check-in.']);
        }

        // Check availability again
        if (! SB_Accommodation_Database::check_availability($room_id, $check_in, $check_out)) {
            wp_send_json_error(['message' => 'Room is no longer available for these dates.']);
        }

        // Get room details
        $room = get_post($room_id);
        if (! $room || $room->post_type !== 'accommodation_room') {
            wp_send_json_error(['message' => 'Invalid room.']);
        }

        $price_per_night = floatval(get_post_meta($room_id, '_sb_price_per_night', true) ?: 0);
        if (! $price_per_night) {
            wp_send_json_error(['message' => 'Room pricing not configured.']);
        }

        // Calculate total
        $nights = SB_Accommodation_Database::calculate_nights($check_in, $check_out);
        $amount = $nights * $price_per_night;

        $currency = strtolower(get_option('sb_currency', 'PHP'));

        // Get Stripe API key
        $secret_key = get_option('sb_stripe_secret_key', '');
        if (! $secret_key) {
            wp_send_json_error(['message' => 'Payment not configured. Please contact administrator.']);
        }

        // Create Stripe payment intent
        $description = sprintf(
            '%s — %s to %s (%d nights) × %d guest(s)',
            $room->post_title,
            $check_in,
            $check_out,
            $nights,
            $occupants
        );

        $response = wp_remote_post('https://api.stripe.com/v1/payment_intents', [
            'headers' => [
                'Authorization' => 'Bearer ' . $secret_key,
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
            'body' => http_build_query([
                'amount'               => intval(round($amount * 100)),
                'currency'             => $currency,
                'description'          => $description,
                'receipt_email'        => $guest_email,
                'metadata[room_id]'    => $room_id,
                'metadata[check_in]'   => $check_in,
                'metadata[check_out]'  => $check_out,
                'metadata[occupants]'  => $occupants,
                'automatic_payment_methods[enabled]' => 'true',
            ]),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Payment gateway error. Please try again.']);
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            wp_send_json_error(['message' => $body['error']['message'] ?? 'Payment error.']);
        }

        // Re-check availability IMMEDIATELY before creating the booking (mitigate race condition)
        if (! SB_Accommodation_Database::check_availability($room_id, $check_in, $check_out)) {
            wp_send_json_error(['message' => 'Sorry, dates were just taken. please select other dates.']);
        }

        // Pre-create booking as pending
        $booking_id = SB_Accommodation_Database::create_booking([
            'room_type_id'   => $room_id,
            'check_in_date'  => $check_in,
            'check_out_date' => $check_out,
            'occupant_count' => $occupants,
            'guest_name'     => $guest_name,
            'guest_email'    => $guest_email,
            'guest_phone'    => $guest_phone,
            'total_amount'   => $amount,
            'stripe_pi_id'   => $body['id'],
            'stripe_status'  => 'requires_payment_method',
            'booking_status' => 'pending',
        ]);

        if (! $booking_id) {
            wp_send_json_error(['message' => 'Failed to create booking record in database.'], 500);
        }

        wp_send_json_success([
            'client_secret'  => $body['client_secret'],
            'payment_intent' => $body['id'],
            'booking_id'     => $booking_id,
            'amount'         => $amount,
            'currency'       => strtoupper($currency),
        ]);
    }

    /**
     * CONFIRM BOOKING - Finalize booking after successful payment
     */
    public static function confirm_booking()
    {
        self::verify();

        $booking_id = absint($_POST['booking_id'] ?? 0);
        $pi_id      = sanitize_text_field($_POST['pi_id'] ?? '');

        if (! $booking_id || ! $pi_id) {
            wp_send_json_error(['message' => 'Missing booking information.']);
        }

        // Get booking
        $booking = SB_Accommodation_Database::get_booking($booking_id);
        if (! $booking) {
            wp_send_json_error(['message' => 'Booking not found.']);
        }

        // Verify payment intent ID matches
        if ($booking->stripe_pi_id !== $pi_id) {
            wp_send_json_error(['message' => 'Payment intent mismatch.']);
        }

        // Update booking status to confirmed
        SB_Accommodation_Database::update_booking_status($booking_id, 'confirmed', 'succeeded');

        // Send confirmation email
        self::send_confirmation_email($booking);

        wp_send_json_success([
            'status'      => 'confirmed',
            'message'     => 'Booking confirmed! Check your email for details.',
            'booking_id'  => $booking_id,
        ]);
    }

    /**
     * Send professional confirmation emails to guest and admin
     */
    private static function send_confirmation_email($booking)
    {
        $room_title = get_the_title($booking->room_type_id);
        $currency   = get_option('sb_currency_symbol', '₱');
        $site_name  = get_bloginfo('name');
        $nights     = SB_Accommodation_Database::calculate_nights($booking->check_in_date, $booking->check_out_date);

        $subject = "Booking Confirmed – {$room_title} (" . date('M j', strtotime($booking->check_in_date)) . ")";
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        $message = "
        <html><body style='font-family:sans-serif;color:#333;line-height:1.6;'>
        <div style='max-width:600px;margin:20px auto;border:1px solid #eee;padding:30px;border-radius:8px;'>
            <h2 style='color:#111b19;margin-top:0;'>Your Reservation is Confirmed!</h2>
            <p>Hi " . esc_html($booking->guest_name) . ",</p>
            <p>We're excited to host you. Your booking at <strong>" . esc_html($room_title) . "</strong> has been successfully confirmed.</p>
            
            <div style='background:#f9fcfb;padding:20px;border-radius:8px;margin:25px 0;'>
                <table style='width:100%;border-collapse:collapse;'>
                    <tr><td style='padding:5px 0;color:#666;'>Check-in</td><td style='padding:5px 0;text-align:right;'><strong>" . date('l, F j, Y', strtotime($booking->check_in_date)) . "</strong></td></tr>
                    <tr><td style='padding:5px 0;color:#666;'>Check-out</td><td style='padding:5px 0;text-align:right;'><strong>" . date('l, F j, Y', strtotime($booking->check_out_date)) . "</strong></td></tr>
                    <tr><td style='padding:5px 0;color:#666;'>Duration</td><td style='padding:5px 0;text-align:right;'><strong>{$nights} night" . ($nights > 1 ? 's' : '') . "</strong></td></tr>
                    <tr><td style='padding:5px 0;color:#666;'>Guests</td><td style='padding:5px 0;text-align:right;'><strong>{$booking->occupant_count}</strong></td></tr>
                    <tr style='border-top:1px solid #ddd;'><td style='padding:15px 0 0;font-size:18px;'><strong>Total Paid</strong></td><td style='padding:15px 0 0;text-align:right;font-size:18px;color:#111b19;'><strong>{$currency}" . number_format($booking->total_amount, 2) . "</strong></td></tr>
                </table>
            </div>

            <p>If you have any questions or need to make changes, please don't hesitate to reach out.</p>
            <p style='margin-top:30px;border-top:1px solid #eee;padding-top:20px;'>
                Best regards,<br>
                <strong>" . esc_html($site_name) . "</strong>
            </p>
        </div>
        </body></html>";

        // To Guest
        wp_mail($booking->guest_email, $subject, $message, $headers);

        // To Admin
        $admin_email = get_option('admin_email');
        wp_mail(
            $admin_email,
            "New Accommodation Booking: " . esc_html($room_title) . " – " . esc_html($booking->guest_name),
            $message,
            $headers
        );
    }
}
