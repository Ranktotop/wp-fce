<?php

/**
 * Represents a FluentCommunity Space or Course entry.
 */
class WP_FCE_Model_Fluent_Community_Entity
{
    private int $id;
    private string $title;
    private string $slug;
    private string $type;

    /**
     * Constructor for a FluentCommunity Entity (space, course, etc.)
     *
     * @param int    $id    The ID of the entity.
     * @param string $title The title.
     * @param string $slug  The slug.
     * @param string $type  The type (e.g. space_group, community, course).
     */
    public function __construct(int $id, string $title, string $slug, string $type)
    {
        $this->id    = $id;
        $this->title = $title;
        $this->slug  = $slug;
        $this->type  = $type;
    }

    /**
     * Get the ID of the entity.
     *
     * @return int The ID of the entity.
     */

    public function get_id(): int
    {
        return $this->id;
    }

    /**
     * Get the title of the entity.
     *
     * @return string The title of the entity.
     */
    public function get_title(): string
    {
        return $this->title;
    }

    /**
     * Get the slug of the entity.
     *
     * @return string The slug of the entity.
     */
    public function get_slug(): string
    {
        return $this->slug;
    }

    /**
     * Get the type of the entity.
     *
     * @return string The type of the entity.
     */

    public function get_type(): string
    {
        return $this->type;
    }

    /**
     * Checks if this entity is a course.
     *
     * @return bool
     */
    public function is_course(): bool
    {
        return $this->type === 'course';
    }

    /**
     * Checks if this entity is a space or group.
     *
     * @return bool
     */
    public function is_space(): bool
    {
        return in_array($this->type, ['space_group', 'community'], true);
    }
}
