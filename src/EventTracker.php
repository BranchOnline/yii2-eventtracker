<?php

namespace branchonline\eventtracker;

use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\Connection;
use Yii;
use yii\base\InvalidParamException;
use yii\db\IntegrityException;
use yii\db\Query;
use yii\di\Instance;

/**
 * The event tracking component. Add this component to your web components if you want to be able to track state and
 * events and use them later on to calculate statistics.
 *
 * @author Roelof Ruis <roelof@branchonline.nl>
 * @copyright Copyright (c) 2016, Branch Online
 * @package branchonline\eventtracker
 * @version 2.0
 */
class EventTracker extends Component {

    /**
     * A configuration array specifying which class to use to find the available event types, or a string directly
     * specifying the class.
     *
     * @var mixed The event types class indication.
     */
    public $types_config;

    /**
     * A configuration array specifying which class to use to find the available state keys, or a string directly
     * specifying the class.
     *
     * @var mixed The state keys class indication.
     */
    public $keys_config;

    /**
     * @var string The name of the DB table to store the event content.
     * Please refer to the migration included in this package to view table structure and setup.
     */
    public $event_table = 'tracking.{{%event}}';

    /**
     * @var string The name of the DB table to store the state content.
     * Please refer to the migration included in this package to view the table structure and setup.
     */
    public $state_table = 'tracking.{{%state}}';

    /**
     * @var Connection|array|string The DB connection object or the application component ID of the DB connection.
     * After the DbCache object is created, if you want to change this property, you should only assign it
     * with a DB connection object.
     * Starting from version 2.0.2, this can also be a configuration array for creating the object.
     */
    public $db = 'db';

    /**
     * A configuration array specifying which class to use as the post event handler, or a string directly specifying
     * the class. This class should implement the [[PostEventInterface]].
     *
     * @var mixed The post event handler configuration.
     */
    public $post_event_handler;

    /** @var BaseEventTypes Internally holds the event types instance. */
    private $_event_types;

    /** @var BaseStateKeys Internally holds the state keys instance. */
    private $_state_keys;

    /**
     * @var PostEventInterface|null Internally holds the post event handler instance, or null if no extra handling
     * is required.
     */
    private $_post_event_handler;

    /**
     * Initializes a new event tracker object.
     *
     * @throws InvalidConfigException If invalid configurations are set.
     */
    public function init() {
        parent::init();
        if (null === $this->types_config || null === $this->keys_config) {
            throw new InvalidConfigException('Both $types_config and $keys_config should be set for the EventTracker');
        }
        $event_types_instance = Yii::createObject($this->types_config);
        if (!$event_types_instance instanceof BaseEventTypes) {
            throw new InvalidConfigException('$types_config should indicate a class subclassing BaseEventTypes to specify the event types.');
        }
        $state_keys_instance = Yii::createObject($this->keys_config);
        if (!$state_keys_instance instanceof BaseStateKeys) {
            throw new InvalidConfigException('$keys_config should indicate a class subclassing BaseStateKeys to specify the state keys.');
        }
        $this->_state_keys  = $state_keys_instance;
        $this->_event_types = $event_types_instance;
        $this->db = Instance::ensure($this->db, Connection::className());
        if (null !== $this->post_event_handler){
            $handler = Yii::createObject($this->post_event_handler);
            if (!$handler instanceof PostEventInterface) {
                throw new InvalidConfigException('$post_event_handler should indicate a class implementing the PostEventInterface.');
            }
            $this->_post_event_handler = $handler;
        }
        EventTrackerEvent::$db    = $this->db;
        EventTrackerEvent::$table = $this->event_table;
    }

    /**
     * Get the available event types. An associative array is returned with the const names as key and the event ID as
     * the value.
     *
     * @return array An associative array with the available event types.
     */
    public function eventTypesAvailable(): array {
        return $this->_event_types->types();
    }

    /**
     * Get the available state keys. An associative array is returned with the const names as key and the state key ID
     * as the value.
     *
     * @return array An associative array with the available state keys.
     */
    public function stateKeysAvailable(): array {
        return $this->_state_keys->keys();
    }

    /**
     * Log an event. Optionally specify event data and/or a user ID. If you do not specify the user ID, the event will
     * be logged for the currently active user.
     *
     * If you have handlers that hook into the logging process, you can explicitly enable/disable these handlers by
     * using the $run_handlers parameter of this function.
     *
     * @param integer  $event_type   The event type specified by its ID. It is recommended to set this using a class
     * constant from your EventTypes object.
     * @param mixed    $event_data   Any event data that should be added and can be encoded into JSON format. Can be
     * NULL in which case no data will be added.
     * @param int|null $user_id      Optionally specify a user for which to log the event. If NULL the currently
     * logged in user will be used.
     * @param bool     $run_handlers Optionally specify whether or not to run post event handlers (If any are
     * configured). Defaults to TRUE.
     * @return bool Whether the event was successfully logged.
     * @throws InvalidParamException Whenever the event data could not be encoded into JSON format or event_type is not
     * a valid event ID.
     * @throws Exception Whenever no user_id is given and there is no authenticated or existing user.
     * @throws IntegrityException Whenever the event could not be inserted into the database.
     */
    public function logEvent($event_type, $event_data = null, $user_id = null, bool $run_handlers = true): bool {
        if (null !== $event_data) {
            json_encode($event_data);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidParamException('The event data could not be encoded into JSON format.');
            }
        }

        if (!is_integer($event_type) || !in_array($event_type, $this->eventTypesAvailable())) {
            throw new InvalidParamException('The event type ID is invalid.');
        }

        $event = new EventTrackerEvent([
            'timestamp'  => TrackerTime::fromCurrentTime()->getIntValue(),
            'event_data' => $event_data,
            'event_type' => $event_type,
        ]);

        if (null !== $user_id && is_integer($user_id)) {
            $event->user_id = $user_id;
        } else {
            $user = Yii::$app->get('user', false);
            if (null === $user || $user->isGuest) {
                throw new Exception('Cannot log event for non-existing or non-authenticated user.');
            }
            $event->user_id = $user->id;
        }

        if ($event->save()) {
            if (true === $run_handlers && $this->_post_event_handler instanceof PostEventInterface) {
                $this->_post_event_handler->afterLogEvent($event);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Log a state. Given a specific state ID specify the current value it is in. This can be any value as long as it
     * can be JSON encoded.
     *
     * Optionally specify a user ID. If you do not specify the user ID, the state change will be logged for the
     * currently active user.
     *
     * @param integer      $state_key   The state key. It is recommended to set this using a const from your StateKeys
     * object.
     * @param mixed        $state_value Any data that specifies the current state of the value for the given key.
     * @param integer|null $user_id     Optionally specify a user for which to log the state change. If NULL the
     * currently logged in user will be used.
     * @return bool Whether the state change was successfully logged.
     * @throws InvalidParamException Whenever the state value could not be encoded into JSON format or $state_key is not
     * a valid state key ID.
     * @throws Exception Whenever no user_id is given and there is no authenticated or existing user.
     * @throws IntegrityException Whenever the event could not be inserted into the database.
     */
    public function logState($state_key, $state_value, $user_id = null): bool {
        $state_value = json_encode($state_value);
        if (false === $state_value) {
            throw new InvalidParamException('The state value could not be encoded into JSON format.');
        }

        if (!is_integer($state_key) || !in_array($state_key, $this->stateKeysAvailable())) {
            throw new InvalidParamException('The state key ID is invalid.');
        }

        $params = [
            'timestamp'   => TrackerTime::fromCurrentTime()->getIntValue(),
            'state_key'   => $state_key,
            'state_value' => $state_value,
        ];

        if (null !== $user_id && is_integer($user_id)) {
            $params['user_id'] = $user_id;
        } else {
            $user = Yii::$app->get('user', false);
            if (null === $user || $user->isGuest) {
                throw new Exception('Cannot log state for non-existing or non-authenticated user.');
            }
            $params['user_id'] = $user->id;
        }

        return ($this->db->createCommand()->insert($this->state_table, $params)->execute() > 0);
    }

    /**
     * Providing two UNIX timestamps, finds all the events that lie in between and returns them via a query.
     *
     * Please be aware that you might use each() or batch() on the returned query if you expect the result set to be
     * large.
     *
     * @param TrackerTime $start       The starting timestamp.
     * @param TrackerTime $until       The ending timestamp.
     * @param array       $users       Optionally filter the query to only contain events for selected users.
     * @param array       $event_types Optionally filter the query to only contain selected event types.
     * @return Query The query.
     */
    public function eventsBetween(TrackerTime $start, TrackerTime $until, array $users = [], array $event_types = []): Query {
        $query = (new Query())
            ->select(['timestamp', 'user_id', 'event_type', 'event_data'])
            ->from($this->event_table)
            ->where("timestamp BETWEEN :low AND :high")
            ->andFilterWhere(['in', 'user_id', $users])
            ->andFilterWhere(['in', 'event_type', $event_types]);

        $query->addParams([
            ':low'  => $start->getIntValue(),
            ':high' => $until->getIntValue(),
        ]);
        return $query;
    }

    /**
     * Given a timestamp (and optional states) retrieves the state at that moment via a query. The query will by default
     * contain all available state keys. If at the given time a state key has never been assigned a value, a NULL will
     * be returned as the value.
     *
     * By default the query results are indexed by state key for quicker usage.
     *
     * @param TrackerTime $time       The time at which to get the state.
     * @param array       $state_keys Optionally filter the query to only contain selected state keys.
     * @return Query The query.
     */
    public function stateAt(TrackerTime $time, array $state_keys = []): Query {
        $query = (new Query())
            ->select(['DISTINCT ON (state_key) state_key', 'state_value'])
            ->from($this->state_table)
            ->where("timestamp <= :time")
            ->orderBy(['state_key' => SORT_ASC, 'timestamp' => SORT_DESC]);

        $query->addParams([
            ':time' => $time->getIntValue(),
        ]);

        $available_keys = [];
        foreach ($this->stateKeysAvailable() as $key => $id) {
            $available_keys[] = $id;
        }

        $keys_string = implode('),(', $available_keys);
        $outer_query = (new Query())
            ->select(['keys.column1 AS state_key', 'state_value'])
            ->from("(VALUES ($keys_string)) AS keys")
            ->leftJoin(['state' => $query], 'state.state_key = keys.column1')
            ->andFilterWhere(['in', 'keys.column1', $state_keys])
            ->indexBy('state_key');
        return $outer_query;
    }

}
