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
        'parent_id' => '%d',
        'title' => '%s',
        'slug'  => '%s',
        'type'  => '%s',
        'privacy' => '%s',
    ];

    /**
     * Columns that should never be mass-updated.
     *
     * @var string[]
     */
    protected static array $guarded = ['id', 'parent_id'];

    /** @var int|null Primary key */
    public ?int $id = null;

    /** @var int|null */
    public ?int $parent_id = null;

    /** @var string */
    public string $title = '';

    /** @var string */
    public string $slug = '';

    /** @var string */
    public string $type = '';

    /** @var string */
    public string $privacy = '';

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
     * Check if this entity is a group.
     *
     * @return bool
     */
    public function is_group(): bool
    {
        return $this->type === 'space_group';
    }

    /**
     * Get the title.
     *
     * @param bool    $include_group Whether to include the group title.
     * @return string
     */
    public function get_title(bool $include_group = false): string
    {
        if ($include_group) {
            $group = $this->get_parent();
            if ($group) {
                return $this->title . " (" . $group->get_title() . ")";
            }
        }
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
     * Get the privacy setting.
     *
     * @return string
     */
    public function get_privacy(): string
    {
        return $this->privacy;
    }

    /**
     * Get the parent.
     *
     * @return WP_FCE_Model_Fcom|null
     */
    public function get_parent(): ?WP_FCE_Model_Fcom
    {
        $parent_id = $this->parent_id;
        if ($parent_id === null) {
            return null;
        }
        return WP_FCE_Model_Fcom::load_by_id($parent_id);
    }

    public function get_group_title(): string
    {
        $parent = $this->get_parent();
        if ($parent === null) {
            return '';
        }
        return $parent->get_title();
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
