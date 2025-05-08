<?php

/**
 * Membership-Einstellungen via Carbon Fields
 *
 * @package WP_Fluent_Community_Extreme
 */

use Carbon_Fields\Container;
use Carbon_Fields\Field;

if (! class_exists('WP_Fluent_Community_Extreme_Options')) {

    class WP_Fluent_Community_Extreme_Options
    {

        public function boot() {}

        /**
         * Registriere alle Carbon Fields Container und Felder.
         *
         * @return void
         */
        public function fields()
        {
            // Hauptcontainer – notwendig für Menüstruktur
            $main = Container::make('theme_options', 'FluentCommunity Extreme')
                ->add_fields([
                    Field::make('html', 'fce_dummy', '<p><em>' . __('Bitte wähle eine Unterseite.', 'wp-fce') . '</em></p>')
                ]);

            // Unterseite: API
            Container::make('theme_options', 'API')
                ->set_page_parent($main)
                ->add_fields([
                    Field::make('text', 'ipn_secret_key', __('IPN Secret Key', 'wp-fce'))
                        ->set_attribute('type', 'password')
                        ->set_help_text(__('Geheimer Schlüssel zur Absicherung des IPN-Callbacks.', 'wp-fce')),
                ]);

            // Unterseite: Darstellung
            Container::make('theme_options', 'Darstellung')
                ->set_page_parent($main)
                ->add_fields([
                    Field::make('image', 'fce_orders_bg_image', __('Zahlungsseite-Hintergrund', 'wp-fce'))
                        ->set_value_type('url')
                        ->set_help_text(__('Wird als Hintergrundbild auf der Zahlungen-Seite angezeigt.')),
                ]);

            // Bestehende Metabox bleibt 1:1 erhalten
            Container::make('post_meta', __('Produkt-Mapping', 'wp-fce'))
                ->where('post_type', '=', 'product')
                ->add_fields([
                    Field::make('text', 'fce_external_id', __('Externe Produkt-ID', 'wp-fce'))
                        ->set_help_text(__('Hier die Produkt-ID eintragen, die der Zahlungsanbieter sendet (z.B. Digistore24 oder CopeCart).', 'wp-fce'))
                        ->set_required(true),

                    Field::make('set', 'fce_spaces', __('FluentCommunity Spaces zuordnen', 'wp-fce'))
                        ->set_options([$this, 'get_space_options'])
                        ->set_help_text(__('Wähle die Spaces, die durch den Kauf freigeschaltet werden sollen.'))
                        ->set_default_value([]),

                    Field::make('set', 'fce_courses', __('FluentCommunity Courses zuordnen', 'wp-fce'))
                        ->set_options([$this, 'get_course_options'])
                        ->set_help_text(__('Wähle die Kurse, die durch den Kauf freigeschaltet werden sollen.'))
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
