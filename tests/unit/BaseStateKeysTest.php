<?php

namespace branchonline\eventtracker\tests\unit;

use branchonline\eventtracker\BaseStateKeys;

class BaseStateKeysTest extends \PHPUnit_Framework_TestCase {

    public function testEmptyKeys() {
        $data = MockEmptyStateKeys::keys();
        $this->assertEmpty($data);
    }

    public function testReturnRelevantKeys() {
        $data = MockSomeStateKeys::keys();
        $this->assertSame($data, [
            'SK_KEY_1' => 1,
            'SK_KEY_2' => 2,
            'SK_KEY_3' => 3,
            'SK_KEY_4' => 'string',
        ]);
    }

}

class MockEmptyStateKeys extends BaseStateKeys {}

class MockSomeStateKeys extends BaseStateKeys {

    const SK_KEY_1 = 1;
    const SK_KEY_2 = 2;
    const SK_KEY_3 = 3;

    // Though it is not the intended use of BaseStateKeys, it is not the responsibility of
    // this class to figure out that 'string' is unusable by the tracker object.
    const SK_KEY_4 = 'string';

    const UNRELATED_CONST = 'unrelated value';

}
