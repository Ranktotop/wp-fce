<?php

abstract class WP_FCE_Model_Base
{
    protected int $id;
    /**
     * Factory method to load an entity by ID.
     *
     * @param int $id
     * @return mixed
     */
    abstract public static function load_by_id(int $id);

    /**
     * Get id of entity.
     *
     * @return int
     */
    public function get_id(): int
    {
        return $this->id;
    }
}
