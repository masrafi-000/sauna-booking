<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SB_Settings {

    public static function init() {
        add_action( 'admin_menu',  [ __CLASS__, 'add_menu' ] );
        add_action( 'admin_init',  [ __CLASS__, 'register_settings' ] );
    }

    public static function add_menu() {
        add_submenu_page(
            'edit.php?post_type=sauna_product',
            'Sauna Booking Settings',
            'Settings',
            'manage_options',
            'sb-settings',
            [ __CLASS__, 'render_page' ]
        );
    }

    public static function register_settings() {
        $options = [
            'sb_stripe_publishable_key',
            'sb_stripe_secret_key',
            'sb_currency',
            'sb_currency_symbol',
        ];
        foreach ( $options as $opt ) {
            register_setting( 'sb_settings_group', $opt, 'sanitize_text_field' );
        }
    }

    public static function render_page() {
        if ( ! current_user_can('manage_options') ) return;
        ?>
        <div class="wrap">
            <h1>Sauna Booking Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('sb_settings_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>Stripe Publishable Key</th>
                        <td><input type="text" name="sb_stripe_publishable_key" value="<?php echo esc_attr(get_option('sb_stripe_publishable_key')); ?>" class="regular-text" placeholder="pk_live_..." /></td>
                    </tr>
                    <tr>
                        <th>Stripe Secret Key</th>
                        <td><input type="password" name="sb_stripe_secret_key" value="<?php echo esc_attr(get_option('sb_stripe_secret_key')); ?>" class="regular-text" placeholder="sk_live_..." /></td>
                    </tr>
                    <tr>
                        <th>Currency Code</th>
                        <td><input type="text" name="sb_currency" value="<?php echo esc_attr(get_option('sb_currency','PHP')); ?>" class="small-text" placeholder="PHP" /></td>
                    </tr>
                    <tr>
                        <th>Currency Symbol</th>
                        <td><input type="text" name="sb_currency_symbol" value="<?php echo esc_attr(get_option('sb_currency_symbol','₱')); ?>" class="small-text" placeholder="₱" /></td>
                    </tr>
                </table>
                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        <?php
    }
}
