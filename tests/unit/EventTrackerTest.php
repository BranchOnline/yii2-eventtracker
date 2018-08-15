<?php

namespace branchonline\eventtracker\tests\unit;

use branchonline\eventtracker\EventTracker;
use branchonline\eventtracker\BaseEventTypes;
use branchonline\eventtracker\BaseStateKeys;
use branchonline\eventtracker\EventTrackerEvent;
use branchonline\eventtracker\PostEventInterface;
use branchonline\eventtracker\TrackerTime;
use Codeception\Test\Unit;
use Yii;
use yii\db\Connection;

/**
 * Tests the EventTracker class.
 *
 * @author Roelof Ruis <roelof@branchonline.nl>
 */
class EventTrackerTest extends Unit {

    public function setUp() {
        parent::setUp();
        static $config = [
            'id'       => 'eventtracker-test',
            'basePath' => __DIR__,
        ];
        $config['components']['db']    = include('db.php');
        $config['components']['cache'] = 'yii\caching\DummyCache';
        $config['vendorPath']          = dirname(dirname(__DIR__)) . '/vendor';
        new \yii\console\Application($config);
    }

    public function tearDown() {
        parent::tearDown();
        Yii::$app = null;
    }

    public function testInstantiateTrackerWithoutArgs() {
        $this->expectException('yii\base\InvalidConfigException');
        $this->expectExceptionMessage('Both $types_config and $keys_config should be set for the EventTracker');
        new EventTracker();
    }

    public function testInstantiateTrackerWithWrongClasses() {
        $this->expectException('yii\base\InvalidConfigException');
        $this->expectExceptionMessage('$types_config should indicate a class subclassing BaseEventTypes to specify the event types.');
        new EventTracker([
            'types_config' => 'yii\base\Object',
            'keys_config' => 'yii\base\Object',
        ]);
    }

    public function testInstantiateTrackerWithOneWrongClass() {
        $this->expectException('yii\base\InvalidConfigException');
        $this->expectExceptionMessage('$keys_config should indicate a class subclassing BaseStateKeys to specify the state keys.');
        new EventTracker([
            'types_config' => 'branchonline\eventtracker\tests\unit\MockEventTypes',
            'keys_config'  => 'yii\base\Object',
        ]);
    }

    public function testInstantiateTrackerWithWrongHandler() {
        $this->expectException('yii\base\InvalidConfigException');
        $this->expectExceptionMessage('$post_event_handler should indicate a class implementing the PostEventInterface');
        new EventTracker([
            'types_config'       => 'branchonline\eventtracker\tests\unit\MockEventTypes',
            'keys_config'        => 'branchonline\eventtracker\tests\unit\MockStateKeys',
            'db'                 => 'yii\db\Connection',
            'post_event_handler' => 'yii\base\Object',
        ]);
    }

    public function testInstantiateTracker() {
        $tracker = $this->_buildFunctioningTracker();
        $this->assertInstanceOf('branchonline\eventtracker\EventTracker', $tracker);
        $this->assertTrue(EventTrackerEvent::$db instanceof Connection, 'Late static binding of $db in EventTrackerEvent failed.');
        $this->assertSame('tracking.{{%event}}', EventTrackerEvent::$table, 'Late static binding of $table in EventTrackerEvent failed.');
    }

    public function testEventTypesAvailable() {
        $tracker = $this->_buildFunctioningTracker();
        $available_types = $tracker->eventTypesAvailable();
        $this->assertSame($available_types, [
            'ET_EVENT_1' => 1,
            'ET_EVENT_2' => 2,
            'ET_EVENT_3' => 3,
        ]);
    }

    public function testStateKeysAvailable() {
        $tracker = $this->_buildFunctioningTracker();
        $available_keys = $tracker->stateKeysAvailable();
        $this->assertSame($available_keys, [
            'SK_KEY_1' => 1,
            'SK_KEY_2' => 2,
            'SK_KEY_3' => 3,
        ]);
    }

    /**
     * @dataProvider providerLogIllegalTypeEvents
     */
    public function testLogIllegalTypeEvents($event_type, $event_data, $user_id, $err_message) {
        $tracker = $this->_buildFunctioningTracker(false);
        $message = null;
        try {
            $tracker->logEvent($event_type, $event_data, $user_id);
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }
        $this->assertEquals($message, $err_message);
    }

    public function providerLogIllegalTypeEvents() {
        return [
            [null,     null,      null, 'The event type ID is invalid.'],
            ['string', null,      null, 'The event type ID is invalid.'],
            [1.1,      null,      null, 'The event type ID is invalid.'],
            [5,        null,      null, 'The event type ID is invalid.'],
            [1,        tmpfile(), null, 'The event data could not be encoded into JSON format.'],
            [1,        null,      null, 'Cannot log event for non-existing or non-authenticated user.'],
            [1,        null,      1.1,  'Cannot log event for non-existing or non-authenticated user.'],
        ];
    }

    /**
     * @dataProvider providerLogLegalTypeEvents
     */
    public function testLogLegalTypeEvents($event_type, $event_data, $user_id) {
        $tracker = $this->_buildFunctioningTracker(false);
        EventTrackerEvent::deleteAll([]);
        $tracker->logEvent($event_type, $event_data, $user_id);
        $logged_event = EventTrackerEvent::findOne([
            'event_type' => $event_type,
            'user_id' => $user_id
        ]);
        $this->assertTrue($logged_event instanceof EventTrackerEvent);
        $this->assertSame($event_data, $logged_event->event_data);
    }

    /** @dataProvider providerLogLegalStateKeys */
    public function testTriggerPostEvent($event_type, $event_data, $user_id) {
        $tracker = $this->_buildFunctioningTrackerWithPostEventHandler($this->once());
        $tracker->logEvent($event_type, $event_data, $user_id);
    }

    /** @dataProvider providerLogLegalStateKeys */
    public function testDisableTriggerPostEvent($event_type, $event_data, $user_id) {
        $tracker = $this->_buildFunctioningTrackerWithPostEventHandler($this->never());
        $tracker->logEvent($event_type, $event_data, $user_id, false);
    }

    public function providerLogLegalTypeEvents() {
        return [
            [1, null, 1],
            [3, ['a' => 3], 2],
        ];
    }

    /**
     * @dataProvider providerLogIllegalStateKeys
     */
    public function testLogIllegalStateKeys($state_key, $state_value, $user_id, $err_message) {
        $tracker = $this->_buildFunctioningTracker();
        $message = null;
        try {
            $tracker->logState($state_key, $state_value, $user_id);
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }
        $this->assertEquals($message, $err_message);
    }

    public function providerLogIllegalStateKeys() {
        return [
            [null,     null,      null, 'The state key ID is invalid.'],
            ['string', null,      null, 'The state key ID is invalid.'],
            [1.1,      null,      null, 'The state key ID is invalid.'],
            [5,        null,      null, 'The state key ID is invalid.'],
            [1,        tmpfile(), null, 'The state value could not be encoded into JSON format.'],
            [1,        null,      null, 'Cannot log state for non-existing or non-authenticated user.'],
            [1,        null,      1.1,  'Cannot log state for non-existing or non-authenticated user.'],
        ];
    }

    /** @dataProvider providerLogLegalStateKeys */
    public function testLogLegalStateKeys($state_key, $state_value, $user_id) {
        $tracker = $this->_buildFunctioningTracker();
        $tracker->logState($state_key, $state_value, $user_id);
    }

    public function providerLogLegalStateKeys() {
        return [
            [1, null, 1],
            [3, ['a' => 3], 2],
        ];
    }

    public function testEventsBetweenQuery() {
        $start_unix_time = 123456789;
        $end_unix_time   = 223456789;
        $tracker = $this->_buildFunctioningTracker();
        $query   = $tracker->eventsBetween(TrackerTime::fromUnixTimestamp($start_unix_time), TrackerTime::fromUnixTimestamp($end_unix_time), [], []);

        $this->assertInstanceOf('yii\db\Query', $query);

        $expected_query = 'SELECT "timestamp", "user_id", "event_type", "event_data" FROM tracking."tbl_event"'
            . ' WHERE timestamp BETWEEN ' . $start_unix_time . '0000 AND ' . $end_unix_time . '0000';

        $this->assertEquals($expected_query, $query->createCommand()->getRawSql());
    }

    public function testStateAtQuery() {
        $unix_time = 123456789;

        $tracker = $this->_buildFunctioningTracker();
        $query   = $tracker->stateAt(TrackerTime::fromUnixTimestamp($unix_time), []);
        $this->assertInstanceOf('yii\db\Query', $query);

        $expected_query = 'SELECT "keys"."column1" AS "state_key", "state_value" FROM (VALUES (1), (2), (3)) AS keys'
            . ' LEFT JOIN (SELECT DISTINCT ON (state_key) state_key, "state_value" FROM tracking."tbl_state"'
            . ' WHERE timestamp <= ' . $unix_time . '0000 ORDER BY "state_key", "timestamp" DESC) "state"'
            . ' ON state.state_key = keys.column1';

        $this->assertEquals($expected_query, $query->createCommand()->getRawSql());
    }

    private function _buildFunctioningTracker($mock_connection = true) {
        if ($mock_connection) {
            $mock_connection = $this->getMockBuilder('yii\db\Connection')->getMock();
            $mock_command    = $this->getMockBuilder('yii\db\Command')->getMock();
            $mock_command->expects($this->any())
                ->method('insert')
                ->will($this->returnValue($mock_command));
            $mock_connection->expects($this->any())
                ->method('createCommand')
                ->will($this->returnValue($mock_command));
            return new EventTracker([
                'types_config' => 'branchonline\eventtracker\tests\unit\MockEventTypes',
                'keys_config'  => 'branchonline\eventtracker\tests\unit\MockStateKeys',
                'db'           => $mock_connection,
            ]);
        } else {
            return new EventTracker([
                'types_config' => 'branchonline\eventtracker\tests\unit\MockEventTypes',
                'keys_config'  => 'branchonline\eventtracker\tests\unit\MockStateKeys',
            ]);
        }
    }

    private function _buildFunctioningTrackerWithPostEventHandler($expects) {
        $injected_handler = $this->getMockBuilder('branchonline\eventtracker\tests\unit\DummyHandler')->getMock();
        $injected_handler->expects($expects)
            ->method('afterLogEvent')
            ->will($this->returnValue(null));
        return new EventTracker([
            'types_config'       => 'branchonline\eventtracker\tests\unit\MockEventTypes',
            'keys_config'        => 'branchonline\eventtracker\tests\unit\MockStateKeys',
            'post_event_handler' => [
                // A trick to make sure the call can be checked through mocking. The class cannot be an object, but the
                // mocked object can be inserted as an argument.
                'class'            => 'branchonline\eventtracker\tests\unit\DummyHandler',
                'injected_handler' => $injected_handler
            ],
        ]);
    }

}

// Using a mock class implementing the PostEventInterface.
class DummyHandler implements PostEventInterface {

    public $injected_handler;

    public function afterLogEvent(EventTrackerEvent $event) {
        $this->injected_handler->afterLogEvent($event); // Passing the call on to the mocked class.
    }

}

// Using a mock class extending the BaseEventTypes because class constants cannot be mocked.
class MockEventTypes extends BaseEventTypes {

    const ET_EVENT_1 = 1;
    const ET_EVENT_2 = 2;
    const ET_EVENT_3 = 3;

}

class MockStateKeys extends BaseStateKeys {

    const SK_KEY_1 = 1;
    const SK_KEY_2 = 2;
    const SK_KEY_3 = 3;

}
