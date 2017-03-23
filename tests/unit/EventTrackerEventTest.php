<?php

namespace branchonline\eventtracker\tests\unit;

use branchonline\eventtracker\EventTrackerEvent;
use Codeception\Test\Unit;
use yii\db\Connection;

class EventTrackerEventTest extends Unit {

    public function testGetDbWorksWithLateStaticBinding() {
        $this->assertSame(null, EventTrackerEvent::getDb());
        $connection            = new Connection();
        EventTrackerEvent::$db = $connection;
        $this->assertSame($connection, EventTrackerEvent::getDb());
    }

    public function testGetTableNameWorksWithLateStaticBinding() {
        $this->assertSame(null, EventTrackerEvent::tableName());
        $table_name               = 'tracking.events_table';
        EventTrackerEvent::$table = $table_name;
        $this->assertSame($table_name, EventTrackerEvent::tableName());
    }

    public function testRulesMatch() {
        $event = new EventTrackerEvent();
        $this->assertSame([
            [['timestamp', 'user_id', 'event_type'], 'required'],
            [['timestamp', 'user_id', 'event_type'], 'integer'],
            ['event_data', 'safe'],
        ], $event->rules());
    }

}
