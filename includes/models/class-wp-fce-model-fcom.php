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

    /**
     * Grant a user access to this entity. If the entity is a community/space, the user will be added to it with the given role.
     * If the entity is a course, the user will be enrolled in the course.
     *
     * @param int    $user_id The ID of the user to add.
     * @param string $role    The role to assign to the user, defaults to "member".
     * @param string $source  The source of the access grant, defaults to "by_automation".
     */
    public function grant_user_access(int $user_id, string $role = "member", string $source = "by_automation"): void
    {
        if ($this->is_space()) {
            \FluentCommunity\App\Services\Helper::addToSpace($this->get_id(), $user_id, $role, $source);
        } else if ($this->is_course()) {
            \FluentCommunity\Modules\Course\Services\CourseHelper::enrollCourse($this->get_id(), $user_id);
        }
    }

    /**
     * Revoke access to this course/space for a given user.
     *
     * @param int $user_id
     * @param string $source
     * @return void
     */
    public function revoke_user_access(int $user_id, string $source = "by_automation"): void
    {
        if ($this->is_space()) {
            \FluentCommunity\App\Services\Helper::removeFromSpace($this->get_id(), $user_id, $source);
        } else if ($this->is_course()) {
            \FluentCommunity\Modules\Course\Services\CourseHelper::leaveCourse($this->get_id(), $user_id);
        }
    }

    /**
     * Revoke access to this course/space for all users.
     *
     * @param string $source The source of the access revoke, defaults to "by_automation".
     * @return void
     */
    public function revoke_all_user_access(string $source = "by_automation"): void
    {
        $users = WP_FCE_Helper_User::get_all();
        foreach ($users as $user) {
            if ($this->is_space()) {
                \FluentCommunity\App\Services\Helper::removeFromSpace($this->get_id(), $user->get_id(), $source);
            } else if ($this->is_course()) {
                \FluentCommunity\Modules\Course\Services\CourseHelper::leaveCourse($this->get_id(), $user->get_id());
            }
        }
    }
}
