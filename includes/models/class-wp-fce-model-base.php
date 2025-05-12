<?php

abstract class WP_FCE_Model_Base
{
    protected ?WP_FCE_Helper_Product $product_helper = null;
    protected ?WP_FCE_Helper_User $user_helper = null;
    protected ?WP_FCE_Helper_Ipn $ipn_helper = null;
    protected ?WP_FCE_Helper_Fluent_Community_Entity $space_helper = null;

    protected int $id;

    protected function product_helper(): WP_FCE_Helper_Product
    {
        return $this->product_helper ??= new WP_FCE_Helper_Product();
    }

    protected function user_helper(): WP_FCE_Helper_User
    {
        return $this->user_helper ??= new WP_FCE_Helper_User();
    }

    protected function ipn_helper(): WP_FCE_Helper_Ipn
    {
        return $this->ipn_helper ??= new WP_FCE_Helper_Ipn();
    }

    protected function space_helper(): WP_FCE_Helper_Fluent_Community_Entity
    {
        return $this->space_helper ??= new WP_FCE_Helper_Fluent_Community_Entity();
    }
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
