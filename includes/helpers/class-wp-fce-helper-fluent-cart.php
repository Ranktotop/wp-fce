<?php

/**
 * File: includes/helpers/class-wp-fce-helper-fluent-cart.php
 *
 * Read-only bridge to FluentCart. Every method degrades gracefully if FluentCart
 * is not installed or its internal API changes.
 *
 * @package WP_Fluent_Community_Extreme
 */

if (! defined('ABSPATH')) {
    exit;
}

class WP_FCE_Helper_Fluent_Cart
{

    /**
     * Check whether FluentCart is active.
     *
     * @return bool
     */
    public static function is_active(): bool
    {
        return class_exists('\FluentCart\App\Models\Customer');
    }

    /**
     * Check whether the user has at least one real purchase in FluentCart.
     * Customers are matched by WordPress user ID or, for guest checkouts, by email.
     *
     * @param WP_FCE_Model_User $user
     * @return bool
     */
    public static function user_has_orders(WP_FCE_Model_User $user): bool
    {
        if (!self::is_active()) {
            return false;
        }

        try {
            $customer_ids = \FluentCart\App\Models\Customer::query()
                ->where('user_id', $user->get_id())
                ->orWhere('email', $user->get_email())
                ->pluck('id')
                ->toArray();

            if (empty($customer_ids)) {
                return false;
            }

            return \FluentCart\App\Models\Order::query()
                ->whereIn('customer_id', $customer_ids)
                ->whereIn('payment_status', \FluentCart\App\Helpers\Status::getReportStatuses())
                ->exists();
        } catch (\Throwable $e) {
            fce_log('FluentCart order lookup failed: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Get the URL of the FluentCart customer account page.
     *
     * @return string|false False if FluentCart is inactive or no customer account page is configured.
     */
    public static function get_customer_account_url(): string|false
    {
        if (!self::is_active()) {
            return false;
        }

        try {
            $url = \FluentCart\App\Services\TemplateService::getCustomerProfileUrl();
            return $url !== '' ? $url : false;
        } catch (\Throwable $e) {
            fce_log('FluentCart customer account URL lookup failed: ' . $e->getMessage(), 'error');
            return false;
        }
    }
}
