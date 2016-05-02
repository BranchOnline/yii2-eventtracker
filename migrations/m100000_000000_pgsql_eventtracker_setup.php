<?php

use yii\db\Migration;

/**
 * Basic DB setup for the branch/eventtracker package. Creates the required tables by default in a new schema.
 *
 * IMPORTANT: THIS MIGRATION IS WRITTEN FOR POSTGRES.
 *
 * The migration assumes that the schema 'tracking' does not yet exist and will create it for you. It is recommended to
 * use a non existing schema because by default migrating down will destroy the complete schema.
 *
 * The complete tracking system can be setup completely independent of your other database activity, but adjust the
 * migration to your liking if you wish to make project specific connections with other tables.
 *
 * Be aware that this package was designed to function completely free of dependencies on other data that might change
 * over time, so be careful when adjusting this migration to link it with existing resources.
 *
 * @author Roelof Ruis <roelof@branchonline.nl>
 */
class m100000_000000_pgsql_eventtracker_setup extends Migration {

    public function safeUp() {
        // Create the new schema.
        $this->execute('CREATE SCHEMA tracking');

        // Create the event types table.
        $this->createTable('tracking.{{%event_type}}', [
            'id'          => $this->integer()->notNull(),
            'description' => $this->string(256),
        ]);
        $this->addPrimaryKey('pk_event_type', 'tracking.{{%event_type}}', 'id');

        // Create the event table.
        $this->createTable('tracking.{{%event}}', [
            'id'         => $this->bigPrimaryKey(),
            'timestamp'  => $this->bigInteger()->notNull(),
            'user_id'    => $this->bigInteger()->notNull(),
            'event_type' => $this->integer()->notNull(),
            'event_data json',
        ]);

        // Link event with event type.
        $this->addForeignKey('fk_event_event_type', 'tracking.{{%event}}', 'event_type', 'tracking.{{%event_type}}', 'id', 'RESTRICT', 'RESTRICT');

        // Make sure one user can only log one event at one time.
        $this->execute('ALTER TABLE tracking.{{%event}} ADD CONSTRAINT uq_event UNIQUE (timestamp, user_id, event_type)');

        // Use indexing to speed up searching by timestamp.
        $this->createIndex('idx_event_timestamp', 'tracking.{{%event}}', 'timestamp');

        // Track state
        $this->createTable('tracking.{{%state_key}}', [
            'id'          => $this->integer()->notNull(),
            'description' => $this->string(256),
        ]);
        $this->addPrimaryKey('pk_state_key', 'tracking.{{%state_key}}', 'id');

        // Create the state table.
        $this->createTable('tracking.{{%state}}', [
            'id'        => $this->bigPrimaryKey(),
            'timestamp' => $this->bigInteger()->notNull(),
            'user_id'   => $this->bigInteger()->notNull(),
            'state_key' => $this->integer()->notNull(),
            'state_value json NOT NULL',
        ]);

        // Link state with state key.
        $this->addForeignKey('fk_state_state_key', 'tracking.{{%state}}', 'state_key', 'tracking.{{%state_key}}', 'id', 'RESTRICT', 'RESTRICT');

        // Make sure one  user can only log one state at one time.
        $this->execute('ALTER TABLE tracking.{{%state}} ADD CONSTRAINT uq_state UNIQUE (timestamp, user_id, state_key)');

        // Use indexing to speed up searching by timestamp.
        $this->createIndex('idx_state_timestamp', 'tracking.{{%state}}', 'timestamp');
    }

    public function safeDown() {
        // Drop the schema cascaded so everything in it is destroyed.
        $this->execute('DROP SCHEMA tracking CASCADE');
    }
}
