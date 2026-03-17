# Accommodation Booking System - Quick Start Guide

## 🚀 5-Minute Setup

### Step 1: Activate Plugin

1. Go to WordPress Admin → Plugins
2. Find "Sauna Booking"
3. Click "Activate"
4. Plugin automatically creates tables and registers CPTs

### Step 2: Configure Stripe Keys

1. Go to WordPress Admin → Settings → (look for Sauna Booking settings or add this)

```
// Option 1: Via WordPress Settings page
Admin → [Sauna Booking Settings Page]
- Stripe Public Key: pk_...
- Stripe Secret Key: sk_...
- Currency: PHP
- Currency Symbol: ₱

// Option 2: Direct database/code
update_option('sb_stripe_public_key', 'pk_...');
update_option('sb_stripe_secret_key', 'sk_...');
update_option('sb_currency', 'PHP');
update_option('sb_currency_symbol', '₱');
```

### Step 3: Create Your First Room

1. Go to WordPress Admin → Accommodations → Add New
2. Enter details:
   - Title: "Deluxe Suite"
   - Price per night: 80.00
   - Room Category: "Deluxe Suite"
   - Max Occupants: 2
   - Description: "Beautiful room with ocean view"
   - Amenities: "WiFi, Air Conditioning, Hot Shower"
   - Add featured image
   - Publish!

### Step 4: Display Rooms on Website

1. Create a page or post where you want rooms displayed
2. Add shortcode:

```
[accommodation_rooms columns="3" per_page="9"]
```

3. Publish

### Step 5: Test Booking

1. Visit page with shortcode
2. Click on a room
3. Select check-in & check-out dates
4. Enter guest info
5. Click "Book Now"
6. Enter test card: `4242 4242 4242 4242` (Stripe test mode)
7. Verify booking appears in Accommodations → Bookings

---

## 📋 Admin Tasks

### View All Bookings

Admin → Accommodations → Bookings

- See all guest bookings
- View dates, amounts, status
- Filter by status

### Edit Room

Admin → Accommodations → Edit [Room Name]

- Update price, amenities, images
- Adjust max occupants
- Change description
- Republish

### Delete Room

Admin → Accommodations

- Hover over room
- Click "Trash"

---

## 🎨 Shortcode Options

### Basic

```
[accommodation_rooms]
```

### 2-Column Layout

```
[accommodation_rooms columns="2" per_page="6"]
```

### Specific Rooms

```
[accommodation_rooms ids="123,456"]
```

### Combine All

```
[accommodation_rooms columns="3" per_page="12" ids="123,456,789"]
```

---

## 🧪 Run Tests

### Via PHP (if console access)

```bash
php wp-content/plugins/sauna-booking/tests/test-accommodation.php
```

### Via WordPress Admin (recommended)

1. Create test page/post
2. Add this to theme's functions.php temporarily:

```php
if (is_page('test-accommodation')) {
    if (file_exists(WP_PLUGIN_DIR . '/sauna-booking/tests/test-accommodation.php')) {
        require_once WP_PLUGIN_DIR . '/sauna-booking/tests/test-accommodation.php';
    }
}
```

3. View the page in browser
4. See test results (should be all ✓)

---

## 💳 Test Stripe Cards

When in Stripe test mode, use these cards to simulate different outcomes:

### Successful Payment

```
Card Number: 4242 4242 4242 4242
Exp: Any future date (e.g., 12/25)
CVC: Any 3 digits (e.g., 123)
Name: Any name
```

### Declined Card

```
Card Number: 4000 0000 0000 0002
```

### Requires Authentication

```
Card Number: 4000 2500 0000 0002
```

---

## 📱 Frontend Features Checklist

- [ ] Room cards display correctly
- [ ] Images load properly
- [ ] Click room → detail page
- [ ] Date pickers work
- [ ] "Check Availability" shows correct nights
- [ ] Guest info form validates
- [ ] Stripe payment dialog appears
- [ ] After payment → confirmation page

---

## 🔧 Database Quick Check

If you want to manually check database:

```sql
-- Check if tables exist
SHOW TABLES LIKE '%accommodation%';

-- See room bookings
SELECT * FROM wp_accommodation_bookings ORDER BY check_in_date DESC;

-- See all bookings for a room
SELECT * FROM wp_accommodation_bookings
WHERE room_type_id = 123
ORDER BY check_in_date DESC;

-- Check availability for room on date range
SELECT COUNT(*) FROM wp_accommodation_bookings
WHERE room_type_id = 123
AND booking_status != 'cancelled'
AND (
  (check_in_date <= '2025-06-18' AND check_out_date > '2025-06-15')
);
-- Should return 0 for available dates
```

---

## 🚨 Troubleshooting

### "Table does not exist" error

**Solution:** Plugin not activated properly

```php
// Manually trigger activation
do_action('activate_sauna-booking/sauna-booking.php');
```

### Stripe payment fails

**Check:**

1. API keys correct in WordPress
2. Test mode enabled in Stripe
3. Account has proper permissions

### Availability always shows unavailable

**Check:**

1. Database table exists (run tests)
2. Date format is YYYY-MM-DD
3. Check booking status != 'cancelled'

### Room shows but no price

**Check:**

1. Edit room in admin
2. Verify "\_sb_price_per_night" meta field is set
3. Save room again

---

## 📧 Email Customization

When booking confirmed, plugin sends email. To customize:

```php
// In class-accommodation-ajax.php, find confirm_booking()
// Modify this section:
wp_mail(
    $booking['guest_email'],
    'Booking Confirmation - YOUR COMPANY',
    sprintf(
        "Dear %s,\n\n" .
        "Thank you for booking!\n\n" .
        "Room: %s\n" .
        "Check-in: %s\n" .
        "Check-out: %s\n" .
        "Guests: %d\n" .
        "Total: %s\n\n" .
        "Confirmation Code: %d\n\n" .
        "Looking forward to your visit!\n\n" .
        "Best regards,\n" .
        "Management Team",
        $booking['guest_name'],
        get_the_title($booking['room_type_id']),
        $booking['check_in_date'],
        $booking['check_out_date'],
        $booking['occupant_count'],
        get_option('sb_currency_symbol', '₱') . number_format($booking['total_amount'], 2),
        $booking['id']
    )
);
```

---

## 🔐 Security Notes

- All AJAX endpoints protected by nonce verification
- Inputs sanitized via WordPress functions
- Payment amounts verified server-side
- Dates re-validated before booking
- SQL injection prevented with parameterized queries

---

## 📞 Getting Help

### Files to Reference

1. `ACCOMMODATION_GUIDE.md` - Complete API documentation
2. `IMPLEMENTATION_SUMMARY.md` - Architecture overview
3. `tests/test-accommodation.php` - Working examples
4. `includes/class-accommodation-*.php` - Source code

### Common Code Snippets

#### Get all bookings for a room

```php
$bookings = SB_Accommodation_Database::get_room_bookings(123);
foreach ($bookings as $booking) {
    echo $booking['guest_name'] . ': ' . $booking['check_in_date'];
}
```

#### Check if specific dates available

```php
$available = SB_Accommodation_Database::check_availability(
    $room_id = 123,
    $check_in = '2025-06-15',
    $check_out = '2025-06-18'
);

if ($available) {
    echo "Available!";
} else {
    echo "Not available";
}
```

#### Calculate nights selected

```php
$nights = SB_Accommodation_Database::calculate_nights(
    '2025-06-15',
    '2025-06-18'
);
$price_per_night = get_post_meta(123, '_sb_price_per_night', true);
$total = $nights * $price_per_night;
echo "Total: " . $total;
```

---

**Ready to go live! 🎉**

Next: Create rooms → Add shortcode → Test bookings → Launch!
