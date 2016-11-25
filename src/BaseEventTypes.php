<?php

namespace branchonline\eventtracker;

use ReflectionClass;
use Yii;
use yii\base\Object;

/**
 * Provides an abstract class for handling event types.
 *
 * Extend this object to provide the project specific event types that you want to log.
 *
 * Implement the available event types as class constants. The constants should have an int value corresponding to their
 * event type ID in the database.
 *
 * @author Roelof Ruis <roelof@branchonline.nl>
 * @copyright Copyright (c) 2016, Branch Online
 * @package branchonline\eventtracker
 * @version 1.1
 */
abstract class BaseEventTypes extends Object {

    /**
     * Request the available event types of this object.
     *
     * Event type constants need to be prefixed with 'ET_' and should map a name to an integer. The function uses
     * caching if possible to speed up types lookup.
     *
     * @return array The available event types of this object.
     */
    final public static function types() {
        $cache_available = isset(Yii::$app->cache);
        $types = $cache_available ? Yii::$app->cache->get('tracking.event_types') : false;
        if (false === $types) {
            $refl_class = new ReflectionClass(static::className());
            $types = $refl_class->getConstants();
            foreach ($types as $name => $value) {
                if (strrpos($name, 'ET_', -strlen($name)) === false) {
                    unset($types[$name]);
                }
            }
        }
        if ($cache_available) {
            Yii::$app->cache->set('tracking.event_types', $types);
        }
        return $types;
    }

}
