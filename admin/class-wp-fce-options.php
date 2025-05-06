<?php

/**
 * Membership-Einstellungen via Carbon Fields
 *
 * @package WP_Fluent_Community_Extreme
 */

if (! class_exists('WP_Fluent_Community_Extreme_Options')) {

    class WP_Fluent_Community_Extreme_Options
    {

        public function __construct()
        {
            add_action('after_setup_theme', [$this, 'boot']);
            add_action('carbon_fields_register_fields', [$this, 'fields']);
        }

        public function boot()
        {
            \Carbon_Fields\Carbon_Fields::boot();
        }

        public function fields()
        {
            \Carbon_Fields\Container::make(
                'theme_options',
                __('Membership-Einstellungen', 'wp-fce')
            )
                ->set_page_parent('options-general.php') // oder dein Plugin-Slug
                ->add_fields([
                    \Carbon_Fields\Field::make('text',     'plan_id',     __('Plan-ID',            'wp-fce')),
                    \Carbon_Fields\Field::make('checkbox', 'plan_active', __('Plan aktivieren',    'wp-fce')),
                    \Carbon_Fields\Field::make('textarea', 'welcome_msg', __('Willkommens-E-Mail', 'wp-fce')),
                ]);
        }
    }
}
