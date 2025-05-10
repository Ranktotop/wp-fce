<?php

/**
 * Helper to fetch FluentCommunity entities (spaces, courses) from wp_fcom_spaces.
 */
class WP_FCE_Helper_Fluent_Community_Entity
{
    /**
     * Fetch all FluentCommunity entities of type "space".
     *
     * @return WP_FCE_Model_Fluent_Community_Entity[]
     */
    public function get_all_spaces(): array
    {
        global $wpdb;

        $rows = $wpdb->get_results("
			SELECT id, title, slug, type
			FROM {$wpdb->prefix}fcom_spaces
			WHERE type = 'community'
			ORDER BY title ASC
		", ARRAY_A);

        return array_map(fn($row) => new WP_FCE_Model_Fluent_Community_Entity(
            (int) $row['id'],
            $row['title'],
            $row['slug'],
            $row['type']
        ), $rows);
    }

    /**
     * Fetch all FluentCommunity entities of type "course".
     *
     * @return WP_FCE_Model_Fluent_Community_Entity[]
     */
    public function get_all_courses(): array
    {
        global $wpdb;

        $rows = $wpdb->get_results("
			SELECT id, title, slug, type
			FROM {$wpdb->prefix}fcom_spaces
			WHERE type = 'course'
			ORDER BY title ASC
		", ARRAY_A);

        return array_map(fn($row) => new WP_FCE_Model_Fluent_Community_Entity(
            (int) $row['id'],
            $row['title'],
            $row['slug'],
            $row['type']
        ), $rows);
    }

    /**
     * Lade mehrere Space-/Course-Entities anhand ihrer IDs.
     *
     * @param int[] $ids
     * @return WP_FCE_Model_Space[]
     */
    public function get_spaces_by_ids(array $ids): array
    {
        global $wpdb;
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '%d'));

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, title, slug, type FROM {$wpdb->prefix}fcom_spaces WHERE id IN ($placeholders)",
                ...$ids
            ),
            ARRAY_A
        );

        return array_map(fn($row) => new WP_FCE_Model_Fluent_Community_Entity(
            (int) $row['id'],
            $row['title'],
            $row['slug'],
            $row['type']
        ), $rows);
    }
}
