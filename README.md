Yii2 Eventtracker
=================

For now, this package requires you to use yii2 with a **Postgres** database!

The eventtracker package can be used when wanting to keep track of events (happening at a point in time) and state (persisting for some period) information without remodelling your database.

Keep track of events and state information via an application component.

Installation
------------

Add

```json
"branchonline/yii2-eventtracker": "dev-master"
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
    'class' => 'branchonline\eventtracker\EventTracker',
    'types_config' => '', // Class extending EventTypes
    'keys_config'  => '', // Class extending StateKeys
]
```

To your application config.

Now You can log your events and state by using the application component!

Examples
--------

###### Log an event or a state change

```php
// Log that MY_EVENT happened for the current user with the optional data.
Yii::$app->eventtracker->logEvent(EventTypes::MY_EVENT, ['optional' => "Json encoded data"]);

// Log that the state of MY_KEY changed to ['x' => 100].
Yii::$app->eventtracker->logState(StateKeys::MY_KEY, ['x' => 100]);
```