<?php
/**
 * Rate Limiter
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Rate_Limiter
 *
 * Prevents form submission spam through rate limiting
 */
class ERFQ_Rate_Limiter {

    /**
     * Table name
     *
     * @var string
     */
    protected $table_name;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'erfq_rate_limits';
    }

    /**
     * Check if submission is allowed
     *
     * @param int $form_id Form ID
     *
     * @return bool True if allowed, false if rate limited
     */
    public function check($form_id) {
        if (!$this->is_enabled()) {
            return true;
        }

        $ip = $this->get_client_ip();
        $limit = $this->get_limit();
        $period = $this->get_period();

        $count = $this->get_submission_count($ip, $form_id, $period);

        if ($count >= $limit) {
            return false;
        }

        // Record this submission
        $this->record_submission($ip, $form_id);

        return true;
    }

    /**
     * Check if rate limiting is enabled
     *
     * @return bool
     */
    protected function is_enabled() {
        return get_option('erfq_rate_limit_enabled', '1') === '1';
    }

    /**
     * Get submission limit
     *
     * @return int
     */
    protected function get_limit() {
        return absint(get_option('erfq_rate_limit_count', 5));
    }

    /**
     * Get rate limit period in minutes
     *
     * @return int
     */
    protected function get_period() {
        return absint(get_option('erfq_rate_limit_period', 60));
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    protected function get_client_ip() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        );

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

        return '0.0.0.0';
    }

    /**
     * Get submission count for IP and form in period
     *
     * @param string $ip      IP address
     * @param int    $form_id Form ID
     * @param int    $period  Period in minutes
     *
     * @return int
     */
    protected function get_submission_count($ip, $form_id, $period) {
        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$period} minutes"));

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name}
            WHERE ip_address = %s
            AND form_id = %d
            AND submission_time > %s",
            $ip,
            $form_id,
            $since
        ));

        return absint($count);
    }

    /**
     * Record a submission
     *
     * @param string $ip      IP address
     * @param int    $form_id Form ID
     */
    protected function record_submission($ip, $form_id) {
        global $wpdb;

        $wpdb->insert(
            $this->table_name,
            array(
                'ip_address'      => $ip,
                'form_id'         => $form_id,
                'submission_time' => current_time('mysql'),
            ),
            array('%s', '%d', '%s')
        );
    }

    /**
     * Clean up old records
     *
     * @param int $days Days to keep records
     */
    public function cleanup($days = 7) {
        global $wpdb;

        $before = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name}
            WHERE submission_time < %s",
            $before
        ));
    }

    /**
     * Get remaining submissions for current IP
     *
     * @param int $form_id Form ID
     *
     * @return int
     */
    public function get_remaining($form_id) {
        if (!$this->is_enabled()) {
            return -1; // Unlimited
        }

        $ip = $this->get_client_ip();
        $limit = $this->get_limit();
        $period = $this->get_period();

        $count = $this->get_submission_count($ip, $form_id, $period);

        return max(0, $limit - $count);
    }

    /**
     * Reset rate limit for an IP
     *
     * @param string $ip      IP address
     * @param int    $form_id Form ID (optional, null for all forms)
     */
    public function reset($ip, $form_id = null) {
        global $wpdb;

        if ($form_id) {
            $wpdb->delete(
                $this->table_name,
                array(
                    'ip_address' => $ip,
                    'form_id'    => $form_id,
                ),
                array('%s', '%d')
            );
        } else {
            $wpdb->delete(
                $this->table_name,
                array('ip_address' => $ip),
                array('%s')
            );
        }
    }
}
