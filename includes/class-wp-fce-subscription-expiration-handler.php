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
        global $wpdb;
        $table     = $wpdb->prefix . 'fce_user_product_subscriptions';
        //Add 3 buffer days to expiration date
        $threshold = date('Y-m-d H:i:s', strtotime('-3 days'));

        $expired = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE paid_until <= %s AND expired_flag = 0",
            $threshold
        ));

        foreach ($expired as $sub) {
            $user_id    = (int) $sub->user_id;
            $product_id = (int) $sub->product_id;

            // Spaces entziehen
            $space_ids = WP_FCE_CPT_Product::get_spaces($product_id);
            foreach ($space_ids as $space_id) {
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table} AS s
                     JOIN {$wpdb->prefix}fce_product_space_map AS m
                       ON s.product_id = m.product_id
                     WHERE s.user_id = %d
                       AND m.space_id = %d
                       AND s.paid_until > %s
                       AND s.expired_flag = 0",
                    $user_id,
                    $space_id,
                    current_time('mysql')
                ));
                if (0 === (int) $count) {
                    \FluentCommunity\App\Services\Helper::removeFromSpace($space_id, $user_id, 'by_automation');
                }
            }

            // Kurse entziehen
            $course_ids = WP_FCE_CPT_Product::get_courses($product_id);
            foreach ($course_ids as $course_id) {
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table} AS s
                     JOIN {$wpdb->prefix}fce_product_course_map AS m
                       ON s.product_id = m.product_id
                     WHERE s.user_id = %d
                       AND m.course_id = %d
                       AND s.paid_until > %s
                       AND s.expired_flag = 0",
                    $user_id,
                    $course_id,
                    current_time('mysql')
                ));
                if (0 === (int) $count) {
                    \FluentCommunity\Modules\Course\Services\CourseHelper::leaveCourse($course_id, $user_id);
                }
            }

            // Markiere verarbeitet
            $wpdb->update(
                $table,
                ['expired_flag' => 1, 'updated_at' => current_time('mysql')],
                ['id'           => $sub->id]
            );
        }
    }
}
