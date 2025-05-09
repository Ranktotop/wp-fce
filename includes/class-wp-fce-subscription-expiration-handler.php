<?php

/**
 * Handler für das Entfernen abgelaufener Subscriptions.
 *
 * @since 1.0.0
 */
class WP_FCE_Subscription_Expiration_Handler
{

    /**
     * Cron-Callback: Remove expired subscriptions (+3 Tage Puffer).
     *
     * @since 1.0.0
     */
    public static function check_expirations(): void
    {
        $threshold = strtotime('-3 days');

        $helper_ipn = new WP_FCE_Helper_Ipn();

        $combinations = $helper_ipn->get_distinct_ipn_email_product_combinations();

        foreach ($combinations as $entry) {
            $email = $entry['user_email'];
            $product_id = $entry['external_product_id'];

            $ipns = $helper_ipn->get_ipns_by_product_and_email($product_id, $email);
            if (empty($ipns)) {
                continue;
            }

            /** @var WP_FCE_Model_Ipn $latest_ipn */
            $latest_ipn = $ipns[0];

            // überspringen, wenn IPN bezahlt ist
            $paid_until = $latest_ipn->get_paid_until_timestamp();
            if ($paid_until === null || $paid_until > $threshold) {
                continue;
            }

            // Zugriff entziehen
            $user = get_user_by('email', $email);
            if (!$user) {
                continue;
            }

            $mapping = $latest_ipn->get_product_mapping();
            if (!$mapping) {
                continue;
            }

            foreach ($mapping['space_ids'] as $space_id) {
                \FluentCommunity\App\Services\Helper::removeFromSpace($space_id, $user->ID, 'by_automation');
            }

            foreach ($mapping['course_ids'] as $course_id) {
                \FluentCommunity\Modules\Course\Services\CourseHelper::leaveCourse($course_id, $user->ID);
            }
        }
    }
}
