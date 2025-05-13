<?php
// File: includes/models/class-wp-fce-model-fcom.php

/**
 * Model for entries in fcom_spaces (FluentCommunity spaces/courses).
 *
 * Note: CRUD (save/delete) is disabled for this model.
 */
class WP_FCE_Model_Fcom extends WP_FCE_Model_Base
{
    /**
     * Base table name without WP prefix.
     *
     * @var string
     */
    protected static string $table = 'fcom_spaces';

    /**
     * Columns and their WPDB format strings.
     *
     * @var array<string,string>
     */
    protected static array $db_fields = [
        'id'    => '%d',
        'title' => '%s',
        'slug'  => '%s',
        'type'  => '%s',
    ];

    /**
     * Columns that should never be mass-updated.
     *
     * @var string[]
     */
    protected static array $guarded = ['id'];

    /** @var int|null Primary key */
    public ?int $id = null;

    /** @var string */
    public string $title = '';

    /** @var string */
    public string $slug = '';

    /** @var string */
    public string $type = '';

    /**
     * Override save to disable CRUD for this model.
     *
     * @throws \LogicException Always.
     */
    public function save(): void
    {
        throw new \LogicException('Save is disabled for Fcom entities.');
    }

    /**
     * Override delete to disable CRUD for this model.
     *
     * @throws \LogicException Always.
     */
    public function delete(): void
    {
        throw new \LogicException('Delete is disabled for Fcom entities.');
    }

    /**
     * Check if this entity is a course.
     *
     * @return bool
     */
    public function is_course(): bool
    {
        return $this->type === 'course';
    }

    /**
     * Check if this entity is a community/space.
     *
     * @return bool
     */
    public function is_space(): bool
    {
        return $this->type === 'community';
    }

    /**
     * Get the title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return $this->title;
    }

    /**
     * Get the slug.
     *
     * @return string
     */
    public function get_slug(): string
    {
        return $this->slug;
    }

    /**
     * Get the type.
     *
     * @return string
     */
    public function get_type(): string
    {
        return $this->type;
    }
}
