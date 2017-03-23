<?php

namespace branchonline\eventtracker;

use yii\db\ActiveRecord;
use yii\db\Connection;
use yii\helpers\Json;

/**
 * The event tracker event active record is returned by the event tracker and provides access to the event information
 * for further processing.
 *
 * @author Roelof Ruis <roelof@branchonline.nl>
 * @copyright Copyright (c) 2016, Branch Online
 * @package branchonline\eventtracker
 * @version 2.0
 */
class EventTrackerEvent extends ActiveRecord {

    /** @var string The table name so it can be set through late static binding. */
    public static $table;

    /** @var Connection The connection so it can be set through late static binding. */
    public static $db;

    /** @inheritdoc */
    public function rules(): array {
        return [
            [['timestamp', 'user_id', 'event_type'], 'required'],
            [['timestamp', 'user_id', 'event_type'], 'integer'],
            ['event_data', 'safe'],
        ];
    }

    /**
     * Overrides the default table name, so it can be set by the tracker class through late static binding.
     *
     * @return string The table name or null if no table name is available.
     */
    public static function tableName() {
        return static::$table;
    }

    /**
     * Overrides the default get DB function, so the connection can be set by the tracker through late static binding.
     *
     * @return Connection|null The connection to be used or null if no connection is available.
     */
    public static function getDb() {
        return static::$db;
    }

}
