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

        /**
         * Registriere alle Carbon Fields Container und Felder.
         *
         * @return void
         */
        public function fields()
        {
            // Product mapping meta box
            \Carbon_Fields\Container::make(
                'post_meta',
                __('Produkt-Mapping', 'wp-fce')
            )
                ->where('post_type', '=', 'product')
                ->add_fields([
                    // Use a set of checkboxes for Spaces (allows clearing all selections)
                    \Carbon_Fields\Field::make('set', 'fce_spaces', __('FluentCommunity Spaces zuordnen', 'wp-fce'))
                        ->set_options([$this, 'get_space_options'])
                        ->set_help_text(__('Wähle die Spaces, die durch den Kauf freigeschaltet werden sollen.', 'wp-fce'))
                        ->set_required(false)
                        ->set_default_value([]),

                    // Use a set of checkboxes for Courses
                    \Carbon_Fields\Field::make('set', 'fce_courses', __('FluentCommunity Courses zuordnen', 'wp-fce'))
                        ->set_options([$this, 'get_course_options'])
                        ->set_help_text(__('Wähle die Kurse, die durch den Kauf freigeschaltet werden sollen.', 'wp-fce'))
                        ->set_required(false)
                        ->set_default_value([]),
                ]);
        }

        /**
         * Liefert alle Spaces als ID=>Name Array (mit Fallbacks).
         *
         * @return array<int,string>
         */
        public function get_space_options(): array
        {
            $spaces = \FluentCommunity\App\Functions\Utility::getSpaces();
            $options = [];

            foreach ($spaces as $space) {
                // Hauptlabel: name
                $label = $space->name ?? '';

                // Fallback auf einen title-Key
                if (empty($label) && isset($space->title)) {
                    $label = $space->title;
                }

                // Fallback auf den Slug
                if (empty($label) && isset($space->slug)) {
                    $label = $space->slug;
                }

                // Als letzte Not-Lösung die ID
                if (empty($label)) {
                    $label = sprintf('#%d', $space->id);
                }

                $options[$space->id] = $label;
            }

            return $options;
        }

        /**
         * Liefert alle Courses als ID=>Titel Array.
         *
         * @return array<int,string>
         */
        public function get_course_options(): array
        {
            $courses = \FluentCommunity\App\Functions\Utility::getCourses();
            $options = [];
            foreach ($courses as $course) {
                $options[$course->id] = $course->title ?? $course->name;
            }
            return $options;
        }
    }
}
