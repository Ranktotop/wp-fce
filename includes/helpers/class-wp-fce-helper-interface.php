<?php

interface WP_FCE_Helper_Interface
{
    /**
     * Get a single entity by ID.
     *
     * @param int $id
     * @return mixed
     */
    public function get_by_id(int $id);

    /**
     * Get multiple entities by their IDs.
     *
     * @param int[] $ids
     * @return array
     */
    public function get_by_ids(array $ids): array;

    /**
     * Get all entities.
     *
     * @return array
     */
    public function get_all();
}
