<?php
// File: includes/helpers/class-wp-fce-helper-access-log.php

/**
 * @extends WP_FCE_Helper_Base<WP_FCE_Model_Access_Log>
 */
class WP_FCE_Helper_Access_Log extends WP_FCE_Helper_Base
{
    /**
     * Table name without WP prefix.
     *
     * @var string
     */
    protected static string $table       = 'fce_access_log';

    /**
     * Model class this helper works with.
     *
     * @var class-string<WP_FCE_Model_Access_Log>
     */
    protected static string $model_class = WP_FCE_Model_Access_Log::class;

    /**
     * Record a new access evaluation log entry.
     *
     * @param  int    $user_id
     * @param  string $entity_type
     * @param  int    $entity_id
     * @param  bool   $decision      true = granted, false = denied
     * @param  string $reason
     * @param  int    $source_id
     * @return int                    The new log entry ID.
     * @throws Exception              On DB error.
     */
    public static function record_log(
        int    $user_id,
        string $entity_type,
        int    $entity_id,
        bool   $decision,
        string $reason,
        int    $source_id
    ): int {
        /** @var WP_FCE_Model_Access_Log $log */
        $log = new static::$model_class();
        $log->user_id      = $user_id;
        $log->entity_type  = $entity_type;
        $log->entity_id    = $entity_id;
        $log->evaluated_at = new \DateTime(current_time('mysql'));
        $log->decision     = $decision;
        $log->reason       = $reason;
        $log->source_id    = $source_id;
        $log->save();

        return $log->get_id();
    }

    /**
     * Get all log entries for a given user and entity.
     *
     * @param  int    $user_id
     * @param  string $entity_type
     * @param  int    $entity_id
     * @return WP_FCE_Model_Access_Log[]
     */
    public static function get_logs_for_entity(
        int    $user_id,
        string $entity_type,
        int    $entity_id
    ): array {
        return static::find(
            [
                'user_id'     => $user_id,
                'entity_type' => $entity_type,
                'entity_id'   => $entity_id,
            ],
            ['evaluated_at' => 'DESC']
        );
    }

    /**
     * Delete all log entries for a given user and entity.
     *
     * @param  int    $user_id
     * @param  string $entity_type
     * @param  int    $entity_id
     * @return bool  True on success, false on failure.
     */
    public static function remove_logs_for_entity(
        int    $user_id,
        string $entity_type,
        int    $entity_id
    ): bool {
        $entries = static::find([
            'user_id'     => $user_id,
            'entity_type' => $entity_type,
            'entity_id'   => $entity_id,
        ]);

        $success = true;
        foreach ($entries as $entry) {
            if (! $entry->delete()) {
                $success = false;
            }
        }
        return $success;
    }
}
