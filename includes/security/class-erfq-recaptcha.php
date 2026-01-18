<?php
/**
 * Google reCAPTCHA integration
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Recaptcha
 *
 * Handles Google reCAPTCHA v3 verification
 */
class ERFQ_Recaptcha {

    /**
     * reCAPTCHA verify URL
     */
    const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * Minimum score threshold
     */
    const MIN_SCORE = 0.5;

    /**
     * Get site key
     *
     * @return string
     */
    public static function get_site_key() {
        return get_option('erfq_recaptcha_site_key', '');
    }

    /**
     * Get secret key
     *
     * @return string
     */
    public static function get_secret_key() {
        return get_option('erfq_recaptcha_secret_key', '');
    }

    /**
     * Check if reCAPTCHA is configured
     *
     * @return bool
     */
    public static function is_configured() {
        return !empty(self::get_site_key()) && !empty(self::get_secret_key());
    }

    /**
     * Render reCAPTCHA script and badge
     *
     * @return string HTML output
     */
    public static function render() {
        if (!self::is_configured()) {
            return '';
        }

        $site_key = self::get_site_key();

        $output = '<script src="https://www.google.com/recaptcha/api.js?render=' . esc_attr($site_key) . '"></script>';
        $output .= '<input type="hidden" name="erfq_recaptcha_token" id="erfq_recaptcha_token" value="">';

        return $output;
    }

    /**
     * Get JavaScript for executing reCAPTCHA
     *
     * @return string JavaScript code
     */
    public static function get_js() {
        if (!self::is_configured()) {
            return '';
        }

        $site_key = self::get_site_key();

        return "
            if (typeof grecaptcha !== 'undefined') {
                grecaptcha.ready(function() {
                    grecaptcha.execute('" . esc_js($site_key) . "', {action: 'erfq_submit'}).then(function(token) {
                        document.getElementById('erfq_recaptcha_token').value = token;
                    });
                });
            }
        ";
    }

    /**
     * Verify reCAPTCHA token
     *
     * @param string $token The reCAPTCHA token from frontend
     *
     * @return bool Whether verification passed
     */
    public function verify($token) {
        if (!self::is_configured()) {
            return true; // Pass if not configured
        }

        if (empty($token)) {
            return false;
        }

        $secret_key = self::get_secret_key();

        $response = wp_remote_post(self::VERIFY_URL, array(
            'body' => array(
                'secret'   => $secret_key,
                'response' => $token,
                'remoteip' => $this->get_client_ip(),
            ),
            'timeout' => 10,
        ));

        if (is_wp_error($response)) {
            // Log error but allow form submission
            error_log('ERFQ reCAPTCHA error: ' . $response->get_error_message());
            return true;
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (!$result || !isset($result['success'])) {
            return false;
        }

        // Check if successful
        if (!$result['success']) {
            return false;
        }

        // Check score (v3)
        if (isset($result['score']) && $result['score'] < self::MIN_SCORE) {
            return false;
        }

        // Check action
        if (isset($result['action']) && $result['action'] !== 'erfq_submit') {
            return false;
        }

        return true;
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    protected function get_client_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '';
    }
}
