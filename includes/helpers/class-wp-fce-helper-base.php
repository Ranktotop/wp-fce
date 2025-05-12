<?php

abstract class WP_FCE_Helper_Base
{
    protected ?WP_FCE_Helper_Product $product_helper = null;
    protected ?WP_FCE_Helper_User $user_helper = null;
    protected ?WP_FCE_Helper_Ipn $ipn_helper = null;
    protected ?WP_FCE_Helper_Fluent_Community_Entity $space_helper = null;
    protected ?WP_FCE_Helper_Access_Override $access_helper = null;

    protected function product(): WP_FCE_Helper_Product
    {
        return $this->product_helper ??= new WP_FCE_Helper_Product();
    }

    protected function user(): WP_FCE_Helper_User
    {
        return $this->user_helper ??= new WP_FCE_Helper_User();
    }

    protected function ipn(): WP_FCE_Helper_Ipn
    {
        return $this->ipn_helper ??= new WP_FCE_Helper_Ipn();
    }

    protected function space(): WP_FCE_Helper_Fluent_Community_Entity
    {
        return $this->space_helper ??= new WP_FCE_Helper_Fluent_Community_Entity();
    }

    protected function access(): WP_FCE_Helper_Access_Override
    {
        return $this->access_helper ??= new WP_FCE_Helper_Access_Override();
    }

    /**
     * Get a single entity by ID.
     *
     * @param int $id
     * @return mixed
     */
    abstract public function get_by_id(int $id);

    /**
     * Get multiple entities by their IDs.
     *
     * @param int[] $ids
     * @return array
     */
    abstract public function get_by_ids(array $ids): array;

    /**
     * Get all entities.
     *
     * @return array
     */
    abstract public function get_all();
}
