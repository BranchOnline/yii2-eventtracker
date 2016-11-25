<?php

namespace branchonline\eventtracker;

/**
 * All objects that want to hook into the event tracking to further process the tracked event data should extend from
 * this interface.
 *
 * @see EventTracker::post_event_handler To set an event handler.
 *
 * @author Roelof Ruis <roelof@branchonline.nl>
 * @copyright Copyright (c) 2016, Branch Online
 * @package branchonline\eventtracker
 * @version 1.1
 */
interface PostEventInterface {

    /**
     * The after log event is called by the EventTracker after the process of logging an event is completed, and the
     * EventTrackerEvent instances that was just logged is provided to this function.
     *
     * @param EventTrackerEvent $event The event that was just logged.
     * @return void
     */
    public function afterLogEvent(EventTrackerEvent $event);

}

