<?php

namespace branchonline\eventtracker\tests\unit;

use branchonline\eventtracker\BaseEventTypes;
use Codeception\Test\Unit;

class BaseEventTypesTest extends Unit {

    public function testEmptyTypes() {
        $data = MockEmptyEventTypes::types();
        $this->assertEmpty($data);
    }

    public function testReturnRelevantTypes() {
        $data = MockSomeEventType::types();
        $this->assertSame($data, [
            'ET_EVENT_1' => 1,
            'ET_EVENT_2' => 2,
            'ET_EVENT_3' => 3,
            'ET_EVENT_4' => 'string'
        ]);
    }

}

class MockEmptyEventTypes extends BaseEventTypes {}

class MockSomeEventType extends BaseEventTypes {

    const ET_EVENT_1 = 1;
    const ET_EVENT_2 = 2;
    const ET_EVENT_3 = 3;

    // Though it is not the intended use of BaseEventTypes, it is not the responsibility of
    // this class to figure out that 'string' is unusable by the tracker object.
    const ET_EVENT_4 = 'string';

    const UNRELATED_CONST = 'unrelated value';

}
