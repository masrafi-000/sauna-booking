<?php
if (! defined('ABSPATH')) exit;

class SB_Accommodation_Admin
{

    public static function init()
    {
        add_action('admin_menu', [__CLASS__, 'add_menu']);
    }

    /**
     * Add admin menu
     */
    public static function add_menu()
    {
        add_submenu_page(
            'edit.php?post_type=accommodation_room',
            'Bookings',
            'Bookings',
            'manage_options',
            'accommodation_bookings',
            [__CLASS__, 'render_bookings_page']
        );
    }

    /**
     * Render bookings admin page
     */
    public static function render_bookings_page()
    {
?>
        <div class="wrap">
            <h1>Accommodation Bookings</h1>

            <?php
            $bookings = SB_Accommodation_Database::get_all_bookings(100);

            if (empty($bookings)) {
                echo '<p>No bookings found.</p>';
                return;
            }
            ?>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Guest Name</th>
                        <th>Guest Email</th>
                        <th>Room</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Guests</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($booking['id']); ?></strong></td>
                            <td><?php echo esc_html($booking['guest_name']); ?></td>
                            <td><?php echo esc_html($booking['guest_email']); ?></td>
                            <td><?php echo esc_html(get_the_title($booking['room_type_id'])); ?></td>
                            <td><?php echo esc_html($booking['check_in_date']); ?></td>
                            <td><?php echo esc_html($booking['check_out_date']); ?></td>
                            <td><?php echo intval($booking['occupant_count']); ?></td>
                            <td><?php echo esc_html(get_option('sb_currency_symbol', '€') . number_format($booking['total_amount'], 2)); ?></td>
                            <td>
                                <span class="status-<?php echo esc_attr($booking['booking_status']); ?>">
                                    <?php echo esc_html(ucfirst($booking['booking_status'])); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(date('Y-m-d H:i', strtotime($booking['created_at']))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <style>
                .status-pending {
                    color: #856404;
                    background: #fff3cd;
                    padding: 3px 8px;
                    border-radius: 3px;
                }

                .status-confirmed {
                    color: #155724;
                    background: #d4edda;
                    padding: 3px 8px;
                    border-radius: 3px;
                }

                .status-cancelled {
                    color: #721c24;
                    background: #f8d7da;
                    padding: 3px 8px;
                    border-radius: 3px;
                }
            </style>
        </div>
<?php
    }
}
