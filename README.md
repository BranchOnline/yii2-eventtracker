Yii2 Eventtracker
=================

[![Latest Stable Version](https://poser.pugx.org/branchonline/yii2-eventtracker/v/stable)](https://packagist.org/packages/branchonline/yii2-eventtracker)
[![Total Downloads](https://poser.pugx.org/branchonline/yii2-eventtracker/downloads)](https://packagist.org/packages/branchonline/yii2-eventtracker)
[![Latest Unstable Version](https://poser.pugx.org/branchonline/yii2-eventtracker/v/unstable)](https://packagist.org/packages/branchonline/yii2-eventtracker)
[![License](https://poser.pugx.org/branchonline/yii2-eventtracker/license)](https://packagist.org/packages/branchonline/yii2-eventtracker)

For now, this package requires you to use yii2 with a **Postgres** database!

The eventtracker package can be used when wanting to keep track of events (happening at a point in time) and state (persisting for some period) information without remodelling your database.

Keep track of events and state information via an application component.

The eventtracker allows extension of tracker functionality by hooking your own additional classes into the core functionality.

Installation
------------

Add

```json
"branchonline/yii2-eventtracker": "2.0"
```

to your composer.json

Usage
-----

Adjust the provided migration and make sure to insert the event types and state keys that you want to keep track of. Run the migration.

Create a class that extends ```branchonline\eventtracker\EventTypes``` and specify the event types as class consts.

Create a class that extends ```branchonline\eventtracker\StateKeys``` and specify the state keys as class consts.

Add

```php
'eventtracker' => [
    'class'        => 'branchonline\eventtracker\EventTracker',
    'types_config' => '', // Class extending EventTypes
    'keys_config'  => '', // Class extending StateKeys
]
```

To your application config.

If you want to add additional processing of events after they are handled, create a class that implements `PostEventInterface` and hook it into the configuration like so
```php
'eventtracker' => [
    'class'              => 'branchonline\eventtracker\EventTracker',
    'types_config'       => '', // Class extending EventTypes
    'keys_config'        => '', // Class extending StateKeys
    'post_event_handler' => '' // Your handler class, implementing the PostEventInterface
]
```

Now You can log your events and state by using the application component and do optional post-processing!

Examples
--------

###### Log an event or a state change

```php
// Log that MY_EVENT happened for the current user with the optional data.
Yii::$app->eventtracker->logEvent(EventTypes::MY_EVENT, ['optional' => "data"]);

// Log that the state of MY_KEY changed to ['x' => 100].
Yii::$app->eventtracker->logState(StateKeys::MY_KEY, ['x' => 100]);
```

###### Retrieve events or state

```php
// Get the events between 1 jan 2016 and 1 feb 2016 for user 1 and 2 of type MY_EVENT_1 or MY_EVENT_2.
Yii::$app->eventtracker->eventsBetween(
    TrackerTime::fromUnixTimestamp(1451606400),
    TrackerTime::fromUnixTimestamp(1454284800),
    [1, 2],
    [EventTypes::MY_EVENT_1, EventTypes::MY_EVENT_2]
);

// Get the state of MY_KEY_1 and MY_KEY_2 at 1 jan 2016.
Yii::$app->eventtracker->stateAt(
    TrackerTime::fromUnixTimestamp(1451606400),
    [StateKeys::MY_KEY_1, StateKeys::MY_KEY_2]
);
```
