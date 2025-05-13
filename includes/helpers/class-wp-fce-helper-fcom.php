<?php
// File: includes/helpers/class-wp-fce-helper-fcom.php

use RuntimeException;

/**
 * @extends WP_FCE_Helper_Base<WP_FCE_Model_Fcom>
 */
class WP_FCE_Helper_Fcom extends WP_FCE_Helper_Base
{
    /**
     * Table name without WP prefix.
     *
     * @var string
     */
    protected static string $table       = 'fcom_spaces';

    /**
     * Model class this helper works with.
     *
     * @var class-string<WP_FCE_Model_Fcom>
     */
    protected static string $model_class = WP_FCE_Model_Fcom::class;

    /**
     * Fetch all FluentCommunity entities of type "community" (spaces), ordered by title.
     *
     * @return WP_FCE_Model_Fcom[]
     */
    public static function get_all_spaces(): array
    {
        return static::find(
            ['type' => 'community'],
            ['title' => 'ASC']
        );
    }

    /**
     * Fetch all FluentCommunity entities of type "course", ordered by title.
     *
     * @return WP_FCE_Model_Fcom[]
     */
    public static function get_all_courses(): array
    {
        return static::find(
            ['type' => 'course'],
            ['title' => 'ASC']
        );
    }
}
