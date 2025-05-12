<?php

class WP_FCE_Helper_Fluent_Community_Entity extends WP_FCE_Helper_Base
{

    //******************************** */
    //************ GETTER ************ */
    //******************************** */

    /**
     * Lade mehrere Space-/Course-Entities anhand ihrer IDs.
     *
     * @param int[] $ids
     * @return WP_FCE_Model_Space[]
     */
    public function get_by_ids(array $ids): array
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

        if (empty($rows) || !is_array($rows)) {
            return [];
        }

        return array_map(fn($row) => new WP_FCE_Model_Fluent_Community_Entity(
            (int) $row['id'],
            $row['title'],
            $row['slug'],
            $row['type']
        ), $rows);
    }

    /**
     * Lade eine einzelne Space-/Course-Entity anhand ihrer ID.
     *
     * @param int $id
     * @return WP_FCE_Model_Fluent_Community_Entity
     * @throws \Exception Wenn keine Entity gefunden wurde.
     */
    public function get_by_id(int $id): WP_FCE_Model_Fluent_Community_Entity
    {
        return WP_FCE_Model_Fluent_Community_Entity::load_by_id($id);
    }

    /**
     * Lade alle Space-/Course-Entities.
     *
     * @return WP_FCE_Model_Fluent_Community_Entity[]
     */
    public function get_all(): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            "SELECT id, title, slug, type FROM {$wpdb->prefix}fcom_spaces ORDER BY title ASC",
            ARRAY_A
        );

        return array_map(fn($row) => new WP_FCE_Model_Fluent_Community_Entity(
            (int) $row['id'],
            $row['title'],
            $row['slug'],
            $row['type']
        ), $rows);
    }

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
}
