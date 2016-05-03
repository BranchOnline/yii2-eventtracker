<?php

namespace branchonline\eventtracker\tests\unit;

use branchonline\eventtracker\EventTracker;
use branchonline\eventtracker\BaseEventTypes;
use branchonline\eventtracker\BaseStateKeys;
use Yii;

/**
 * Tests the EventTracker class.
 *
 * @author Roelof Ruis <roelof@branchonline.nl>
 */
class EventTrackerTest extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        parent::setUp();
        static $config = [
            'id' => 'eventtracker-test',
            'basePath' => __DIR__,
        ];
        $config['components']['cache'] = 'yii\caching\DummyCache';
        $config['vendorPath'] = dirname(dirname(__DIR__)) . '/vendor';
        new \yii\console\Application($config);
    }

    public function tearDown() {
        parent::tearDown();
        Yii::$app = null;
    }

    public function testInstantiateTrackerWithoutArgs() {
        $this->setExpectedException('yii\base\InvalidConfigException');
        new EventTracker();
    }

    public function testInstantiateTrackerWithWrongClasses() {
        $this->setExpectedException('yii\base\InvalidConfigException');
        new EventTracker([
            'types_config' => 'yii\base\Object',
            'keys_config' => 'yii\base\Object',
        ]);
    }

    public function testInstantiateTrackerWithOneWrongClass() {
        $this->setExpectedException('yii\base\InvalidConfigException');
        new EventTracker([
            'types_config' => 'branchonline\eventtracker\tests\unit\MockEventTypes',
            'keys_config'  => 'yii\base\Object',
        ]);
    }

    public function testInstantiateTracker() {
        $tracker = $this->_buildFunctioningTracker();
        $this->assertInstanceOf('branchonline\eventtracker\EventTracker', $tracker);
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
        $tracker = $this->_buildFunctioningTracker();
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
        $tracker = $this->_buildFunctioningTracker();
        $tracker->logEvent($event_type, $event_data, $user_id);
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

    /**
     * @dataProvider providerLogLegalStateKeys
     */
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

    public function testEventsBetweenReturnsQuery() {
        $tracker = $this->_buildFunctioningTracker();
        $query   = $tracker->eventsBetween(0, 0, [], []);
        $this->assertInstanceOf('yii\db\Query', $query);
    }

    public function testStateAtReturnsQuery() {
        $tracker = $this->_buildFunctioningTracker();
        $query   = $tracker->stateAt(0, []);
        $this->assertInstanceOf('yii\db\Query', $query);
    }

    private function _buildFunctioningTracker() {
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
