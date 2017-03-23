<?php

namespace branchonline\eventtracker;

use ReflectionClass;
use Yii;
use yii\base\Object;

/**
 * Provides an abstract class for handling state keys.
 *
 * Extend this object to provide the project specific state keys that you want to be able to track.
 *
 * Implement the available state keys as class constants. The constants should have an int value corresponding to their
 * state key ID in the database.
 *
 * @author Roelof Ruis <roelof@branchonline.nl>
 * @copyright Copyright (c) 2016, Branch Online
 * @package branchonline\eventtracker
 * @version 2.0
 */
abstract class BaseStateKeys extends Object {

    /**
     * Request the available state keys of this object.
     *
     * State key constants need to be prefixed with 'SK_' and should map a name to an integer. The function uses
     * caching if possible to speed up types lookup.
     *
     * @return array The available state keys of this object.
     */
    final public static function keys(): array {
        $cache_available = isset(Yii::$app->cache);
        $types = $cache_available ? Yii::$app->cache->get('tracking.state_keys') : false;
        if (false === $types) {
            $refl_class = new ReflectionClass(static::className());
            $types = $refl_class->getConstants();
            foreach ($types as $name => $value) {
                if (strrpos($name, 'SK_', -strlen($name)) === false) {
                    unset($types[$name]);
                }
            }
        }
        if ($cache_available) {
            Yii::$app->cache->set('tracking.state_keys', $types);
        }
        return $types;
    }

}
