<?php

namespace branchonline\eventtracker\tests\unit;

use branchonline\eventtracker\TrackerTime;
use Codeception\Test\Unit;
use InvalidArgumentException;

/**
 * @author Roelof Ruis <roelof@branchonline.nl>
 */
class TrackerTimeTest extends Unit {

    public function testGetCurrent() {
        $current_time = TrackerTime::getCurrent();
        $this->assertTrue($current_time instanceof TrackerTime);
        $this->assertSame(strlen((string) time()) + 4, strlen($current_time->getValue()));
    }

    /** @dataProvider fromUnixTimestampProvider */
    public function testFromUnixTimestamp($value, $expected) {
        if (false === $expected) {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Invalid tracker time value given.');
            TrackerTime::fromUnixTimestamp($value);
        } else {
            $time = TrackerTime::fromUnixTimestamp($value);
            $this->assertSame($expected, $time->getValue());
        }
    }

    public function fromUnixTimestampProvider() {
        return [
            [false, false],
            [null, false],
            ['ab', false],
            [0, false],
            ['0', false],
            ['10', '100000'],
            [10, '100000'],
            [123456789, '1234567890000'],
            ['123456789', '1234567890000'],
        ];
    }

    /** @dataProvider fromTrackerTimestampProvider */
    public function testFromTrackerTimestamp($value, $expected) {
        if (false === $expected) {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Invalid tracker time value given.');
            TrackerTime::fromTrackerTimestamp($value);
        } else {
            $time = TrackerTime::fromTrackerTimestamp($value);
            $this->assertSame($expected, $time->getValue());
        }
    }

    public function fromTrackerTimestampProvider() {
        return [
            ['1', '1'],
            ['2', '2'],
            ['10', '10'],
            ['123456789123456789', '123456789123456789'],
            [
                '123456789123456789123456789123456789123456789123456789123456789123456789123456789123456789123456789123456789',
                '123456789123456789123456789123456789123456789123456789123456789123456789123456789123456789123456789123456789'
            ],
            ['01', false],
            ['a string', false],
            ['10a', false],
            ['0b00', false],
        ];
    }

}