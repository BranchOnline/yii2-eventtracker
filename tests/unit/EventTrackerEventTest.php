<?php

namespace branchonline\eventtracker\tests\unit;

use branchonline\eventtracker\EventTrackerEvent;
use Codeception\Test\Unit;
use yii\db\Connection;

class EventTrackerEventTest extends Unit {

    public function testGetDbWorksWithLateStaticBinding() {
        $this->assertSame(null, EventTrackerEvent::getDb());
        $connection = $this->bindConnection();
        $this->assertSame($connection, EventTrackerEvent::getDb());
    }

    public function testGetTableNameWorksWithLateStaticBinding() {
        $this->assertSame(null, EventTrackerEvent::tableName());
        $table_name = $this->bindTable();
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

    protected function bindConnection() {
        $connection            = new Connection();
        EventTrackerEvent::$db = $connection;
        return $connection;
    }

    protected function bindTable() {
        $table_name               = 'tracking.events_table';
        EventTrackerEvent::$table = $table_name;
        return $table_name;
    }

}
