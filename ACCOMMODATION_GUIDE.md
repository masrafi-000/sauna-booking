# Accommodation Booking System - Implementation Guide

## Overview

The accommodation booking system allows guests to book rooms on a day-by-day basis. Key features:

- Rooms with configurable nightly rates
- Date-range availability checking
- Support for 1-2 occupants per room
- Stripe payment integration
- Admin booking management

## Files Created

### Core Classes

1. **class-accommodation-post-type.php** - Room type CPT registration and meta fields
2. **class-accommodation-database.php** - Database schema and queries
3. **class-accommodation-ajax.php** - AJAX endpoints for booking workflow
4. **class-accommodation-shortcode.php** - Frontend room display shortcode
5. **class-accommodation-admin.php** - Admin panel for viewing bookings

### Templates

- **single-accommodation-room.php** - Individual room page with booking form

### Tests

- **tests/test-accommodation.php** - Comprehensive test suite (run via WordPress)

## Database Schema

```sql
Table: wp_accommodation_bookings

Columns:
- id (BIGINT) - Primary key
- room_type_id (BIGINT) - Links to accommodation_room post
- check_in_date (DATE) - Guest check-in date
- check_out_date (DATE) - Guest check-out date
- occupant_count (INT) - Number of guests (1 or 2)
- guest_name (VARCHAR) - Name of booking guest
- guest_email (VARCHAR) - Contact email
- guest_phone (VARCHAR) - Contact phone
- total_amount (DECIMAL) - Total cost (nights × price_per_night)
- stripe_pi_id (VARCHAR) - Stripe payment intent ID
- stripe_status (VARCHAR) - Stripe status (pending, succeeded, failed)
- booking_status (VARCHAR) - Local status (pending, confirmed, cancelled)
- notes (TEXT) - Admin notes
- created_at (DATETIME) - Booking creation timestamp

Indexes:
- PRIMARY KEY (id)
- KEY room_dates (room_type_id, check_in_date, check_out_date) - For availability queries
- KEY guest_email (guest_email) - For finding guest bookings
- KEY booking_status (booking_status) - For filtering by status
```

## Post Type: accommodation_room

### Meta Fields

- `_sb_price_per_night` - Nightly rate (e.g., 80.00)
- `_sb_room_category` - Room type (Standard, Deluxe, etc.)
- `_sb_max_occupants` - Max guests allowed (default: 2)
- `_sb_description` - Room description
- `_sb_amenities` - Comma-separated amenities
- `_sb_gallery` - Image URLs (one per line)

### Admin Interface

- Create/edit rooms in WordPress admin
- Configure pricing, category, occupancy limits
- Add descriptions, amenities, and gallery images
- View all bookings via "Accommodations → Bookings"

## Frontend Usage

### Display Rooms

```php
[accommodation_rooms columns="3" per_page="9"]
```

**Shortcode Attributes:**

- `columns` - Grid layout (2, 3, or 4 columns)
- `per_page` - Number of rooms to display
- `ids` - Specific room IDs (comma-separated)

### Example

```php
[accommodation_rooms columns="3" per_page="6"]
```

## Booking Workflow

### 1. User Views Room Page

- Displays room details, amenities, gallery
- Shows nightly price
- Booking form with date picker

### 2. Select Check-in/Check-out Dates

```
Check-in: 2025-06-15
Check-out: 2025-06-18
```

- Auto-calculates 3 nights
- Shows "Available ✓" message
- Disables past dates

### 3. Select Number of Guests

- 1 or 2 guests (based on max_occupants setting)

### 4. Enter Guest Info

- Full name
- Email address
- Phone number

### 5. checkout Payment

- Button triggers AJAX to create Stripe payment intent
- Form submits booking data to server
- Pre-creates booking with "pending" status

### 6. Stripe Payment

- Stripe dialog appears
- Guest enters card details
- Payment processes

### 7. Confirmation

- On success: booking status → "confirmed"
- Confirmation email sent to guest
- Redirect to thank-you page

## AJAX Endpoints

### acc_check_availability

**Purpose:** Verify room is available for selected dates

**Request:**

```javascript
{
  action: 'acc_check_availability',
  nonce: '...',
  room_id: 123,
  check_in: '2025-06-15',
  check_out: '2025-06-18'
}
```

**Response (Success):**

```javascript
{
  success: true,
  data: {
    available: true,
    nights: 3,
    check_in: '2025-06-15',
    check_out: '2025-06-18'
  }
}
```

### acc_create_payment_intent

**Purpose:** Create Stripe payment intent and pre-create booking

**Request:**

```javascript
{
  action: 'acc_create_payment_intent',
  nonce: '...',
  room_id: 123,
  check_in: '2025-06-15',
  check_out: '2025-06-18',
  occupants: 2,
  guest_name: 'John Doe',
  guest_email: 'john@example.com',
  guest_phone: '123-456-7890'
}
```

**Response (Success):**

```javascript
{
  success: true,
  data: {
    client_secret: '...',
    payment_intent: 'pi_...',
    booking_id: 456,
    amount: 240.00,
    currency: 'EUR'
  }
}
```

### acc_confirm_booking

**Purpose:** Finalize booking after payment succeeds

**Request:**

```javascript
{
  action: 'acc_confirm_booking',
  nonce: '...',
  booking_id: 456,
  pi_id: 'pi_...'
}
```

### acc_get_booked_dates

**Purpose:** Get all booked date ranges for a room (for calendar UI)

**Request:**

```javascript
{
  action: 'acc_get_booked_dates',
  nonce: '...',
  room_id: 123
}
```

**Response:**

```javascript
{
  success: true,
  data: {
    booked_dates: [
      { check_in_date: '2025-06-10', check_out_date: '2025-06-15' },
      { check_in_date: '2025-06-20', check_out_date: '2025-06-25' }
    ]
  }
}
```

## Class Methods

### SB_Accommodation_Database

#### `calculate_nights( $check_in, $check_out )`

**Returns:** Integer number of nights between dates

```php
$nights = SB_Accommodation_Database::calculate_nights( '2025-06-15', '2025-06-18' );
// Returns: 3
```

#### `check_availability( $room_id, $check_in, $check_out )`

**Returns:** Boolean - true if no conflicts

```php
$available = SB_Accommodation_Database::check_availability( 123, '2025-06-15', '2025-06-18' );
// Returns: true or false
```

#### `get_booked_dates( $room_id )`

**Returns:** Array of booking date ranges

```php
$bookings = SB_Accommodation_Database::get_booked_dates( 123 );
// Returns: [
//   ['check_in_date' => '2025-06-10', 'check_out_date' => '2025-06-15'],
//   ...
// ]
```

#### `create_booking( $data )`

**Returns:** Booking ID (int) or 0 on failure

```php
$booking_id = SB_Accommodation_Database::create_booking( [
    'room_type_id'   => 123,
    'check_in_date'  => '2025-06-15',
    'check_out_date' => '2025-06-18',
    'occupant_count' => 2,
    'guest_name'     => 'John Doe',
    'guest_email'    => 'john@example.com',
    'guest_phone'    => '123-456-7890',
    'total_amount'   => 240.00,
] );
```

#### `get_booking( $booking_id )`

**Returns:** Array with booking details or null

```php
$booking = SB_Accommodation_Database::get_booking( 456 );
// Returns: [
//   'id' => 456,
//   'room_type_id' => 123,
//   'guest_name' => 'John Doe',
//   'check_in_date' => '2025-06-15',
//   'check_out_date' => '2025-06-18',
//   ...
// ]
```

#### `get_room_bookings( $room_id, $limit = 100 )`

**Returns:** Array of all bookings for a room

```php
$bookings = SB_Accommodation_Database::get_room_bookings( 123, 50 );
```

#### `get_all_bookings( $limit = 100, $offset = 0 )`

**Returns:** Array of all bookings (admin use)

```php
$bookings = SB_Accommodation_Database::get_all_bookings( 100, 0 );
```

#### `update_booking_status( $booking_id, $status, $stripe_status = '' )`

**Updates:** Booking status in database

```php
SB_Accommodation_Database::update_booking_status( 456, 'confirmed', 'succeeded' );
```

## Testing

Run the comprehensive test suite:

### Via WordPress Admin

1. Go to Plugins → Plugin File Editor
2. Create a page/post with this code:

```php
<?php
if ( file_exists( WP_PLUGIN_DIR . '/sauna-booking/tests/test-accommodation.php' ) ) {
    require_once WP_PLUGIN_DIR . '/sauna-booking/tests/test-accommodation.php';
}
```

3. View the page in frontend (or use wp-cli)

### Via WP-CLI

```bash
wp eval-file wp-content/plugins/sauna-booking/tests/test-accommodation.php
```

### Test Coverage

- Post type registration
- Database table creation
- Column verification
- Index verification
- Availability logic
- Date calculations
- Booking CRUD operations
- Shortcode rendering
- AJAX endpoint registration

## Configuration

### Required Settings

These should be set in WordPress options (Admin → Settings):

- `sb_currency` - Currency code (e.g., 'EUR', 'USD')
- `sb_currency_symbol` - Currency symbol (e.g., '€', '$')
- `sb_stripe_public_key` - Stripe Public Key
- `sb_stripe_secret_key` - Stripe Secret Key

### Optional Settings

- Email templates for confirmations (handled by wp_mail)
- Checkout page URL (for redirects)

## Common Tasks

### Create a New Room Type

1. Go to WordPress Admin
2. Click "Accommodations" → "Add New"
3. Enter room title and description
4. Set price per night
5. Select room category
6. Configure max occupants
7. Add amenities list
8. Upload/link gallery images
9. Publish

### View Bookings

1. Go to WordPress Admin
2. Click "Accommodations" → "Bookings"
3. See all bookings sorted by check-in date
4. Check status (pending, confirmed, cancelled)

### Check Room Availability

1. Frontend: Select dates on booking form
2. Red message = unavailable
3. Green message = available with night count

### Cancel a Booking

1. Go to "Accommodations" → "Bookings"
2. Update booking_status to 'cancelled' in database
3. (Optional) Add admin interface for manual cancellations

## Troubleshooting

### Dates Not Validating

- Ensure date inputs are in YYYY-MM-DD format
- Check browser console for client-side errors

### Stripe Payment Fails

- Verify API keys in WordPress settings
- Check Stripe account has proper permissions
- View server logs for 4xx/5xx responses

### Bookings Not Saving

- Confirm database table exists (run tests)
- Check WordPress user is logged in for forms
- Verify nonce in form matches AJAX callback

### Template Not Loading

- Ensure `single-accommodation-room.php` file exists
- Check post type slug is 'accommodation_room'
- Verify rewrite rules flushed on plugin activation

## Performance Considerations

### Queries

- `check_availability()` uses indexed date range queries
- Prevents slow full-table scans
- Limit results with LIMIT clause where possible

### Caching

- Consider caching booked dates for popular rooms
- Invalidate cache on each booking

### Scale

- For 1000+ bookings, add database partitioning
- Consider archive table for old bookings

## Security

### Nonce Verification

- All AJAX endpoints verify `acc_nonce`
- CSRF protection on forms

### Input Sanitization

- All inputs sanitized via sanitize\_\* functions
- Email validation via sanitize_email

### Payment

- Stripe PI verification on confirmation
- Server-side amount validation

## Future Enhancements

1. **Calendar View** - Visual date picker showing availability
2. **Discount Codes** - Apply promotional discounts
3. **Multi-night Discounts** - Lower rates for longer stays
4. **Cancellation Policy** - Refund schedules
5. **Email Templates** - Customizable confirmation emails
6. **SMS Notifications** - Send booking confirmations via SMS
7. **iCalendar Export** - Sync with calendar apps
8. **Availability Rules** - Minimum stay length, blackout dates
9. **Guest Reviews** - Rating system for rooms

## Support

For issues:

1. Check test suite results
2. Review error logs
3. Verify all settings configured
4. Check compatibility with theme

---

**System Ready for Testing!** ✓
