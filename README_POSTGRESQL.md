
Tsugi support for PostgreSQL
============================

Tsugi is adding support for PostgreSQL as an experimental feature.  It is being build and tested
but until it is used in production, it should be considered experimental.

Tsugi PHP was built with MySQL from the beginning back in 2010.   Tsugi PHP will not use an ORM.
Of course without an ORM, portability is a challenge.  But at this point, it is likely that
Tsugi PHP will never support any database other than MySQL and PsotgreSQL.

With this as backdrop the experiment is to use the \Tsugi\Util\PDOX abstraction to transform
the (generally simpler) MySQL syntax into PostgreSQL automatically wherever possible and provide
ways for application developers and the code inside of Tsugi to write code for both variations
when automatic transformation is not feasible.

If you are interested in playing with PostgreSQL and Tsugi - please have a conversation on the
Tsugi developers list before starting out.

Effects on Tool Code (TL;DR)
----------------------------

The approach to this syntax transformation is to minimize the effects on tool code.
The dream is to write clean MySQL give PDOX meta data about the tables, and then just
let the syntax transformaton and the PDOX object to the rest.

The first time you switch to PostgreSQL - you may encounter errors in your CREATE
or other setup statement.  These need to be fixed and then you need to go back
and make sure you did not break MySQL.  You will be dropping a lot of tables
in test databases for a while.

Setup
-----

To create your PostgreSQL database follow these steps:

    psql postgres

    CREATE ROLE tsugi WITH LOGIN PASSWORD 'tsugipw';
    ALTER ROLE tsugi CREATEDB;

    CREATE DATABASE tsugidb;
    GRANT ALL PRIVILEGES ON DATABASE tsugidb TO tsugi;

To connect to the database use the following:

    psql postgres

    \list
    \connect tsugidb
    \dt

Change your `config.php` as follows:

    $CFG->pdo       = 'pgsql:host=127.0.0.1;port=5432;dbname=tsugidb'; // MAMP
    $CFG->dbuser    = 'tsugi';
    $CFG->dbpass    = 'tsugipw';

Then create your first set of PostgreSQL tables:

    cd tsugi/admin
    php upgrade.php

If it blows up - figure out why - it might be Tsugi table or a table from
one of your tools.   If it is Tsugi table reach out on the dev list and we will get it
fixed.   If it is your table (i.e. `database.php`) then you may be able to figure out
what is non portable, either you can fix the SQL in `database.php` or add a bit more
magic to `SQLDialect.php`.

Detecting Which Driver is Active
--------------------------------

Much of the work in the sytnax transformation is accomplished in the `\Tsugi\Util\PDOX`
class.  Form the beginning of Tsugi this has been an abstraction to clean up and simplify
the use of the`\PDO` class in PHP.   PDOX makes it much easier to write code that does
a good job checking all SQL statements for syntax errors and catching things in development
rather then in production.

With the addition of PostgreSQL syntax transformation, PDOX is the workhorse.  There is a new
attribute `$PDOX->sqlPatch` that allows for the registration of an SQL dialect translator.  Since
`PDOX` is intended for use outside of Tsugi, we find Tsugi's particular SQLDialect implementation
at `\Tsugi\Core\SqlDialect` the dialect is registered in the `\Tsugi\Core\LTIX` class in the
`getConnection()` method.

PDOX also lets you check which database is running in a portable way using

    $PDOX->isMySQL()
    $PDOX->isPgSQL()
    $PDOX->isSQLite()

Your application can check these methods and write driver specific queries.  If you do not
want the `SQLDialect` process to alter your SQL add a comment somewhere in your SQL as follows:

    /*PDOX SQLDialect */

You can use the following code to get the marker from `SQLDialect`.

    $sql = $sql . "\n" . \Tsugi\Core\SQLDialect::$marker;

Error Messages In Your Code
---------------------------

This feature is designed to have no impact on existing code that works on MySQL.   When in doubt,
PDOX just runs the query exactly the way you wrote it.  Sometimes it warns you in the log when it
sees something you should fix.  Here are some example log messages:

    $PDOX->upsertGetPKReturnError() LAST_INSERT_ID is a non-portable construct
    $PDOX->upsertGetPKReturnError() RETURNING is a non-portable construct

These are informative errors, but your tool will keep working as long as you are using MySQL.
PDOX notices this issue, warns you and then runs your SQL unchanged.

Now if you ran this code while connected to a PostgreSQL database, PDOX would give you an error and not
change your SQL. But quite often the next error will be an PostgreSQL syntax error because PDOX did not
convert your query to PostgreSQL and it will not be happy with your MySQL syntax.

Other errors are PostgreSQL specific and will only happen when you are running code
with a PostgreSQL database and the error mean PostgreSQL cannot do an upsert properly.  For
exmaple if you need a Meta entry for your table or a Meta comment for a query, you might see:

    $PDOX->upsertGetPKReturnError() needs "table" and "lk" entries in the $meta parameter for PostgreSQL

If you have not included a properly named value for the logical key in your `values` array you might see:

    $PDOX->upsertGetPKReturnError() missing :context_sha256 in the values array

If you see this message, it probably means you have not include *all* your logical keys in the Meta
information when your table has a UNIQUE together clause that includes more than one column:

    $PDOX->upsertGetPKReturnError() pre-SELECT expects 0 or 1 row, got 5
    $PDOX->upsertGetPKReturnError() post-SELECT expects exactly 1 row, got 2

The error messages can help guide you to make needed changes to your code.

Simple Syntax Transformation - SQLDialect
-----------------------------------------

The `\Tsugi\Core\SQLDialect` class in general patches each query before it is passed to the underlying
`\PDO` implementation.  You can look at SQLDialect to se what it is doing, but the following are
the general kinds of transformations you will find:

* MySQL backquotes are turned into PostgreSQL double quotes

* Data types are transformed using regular expressions - (BLOB -> BYTEA, TINYINT -> SMALLINT, etc.). These
transformations are applied to CREATE TABLE and ALTER statements

* CREATE statements are tweaked - things like "engine=InnoDB" are simply removed.  This is not too
sophisticates but meets the needs as long as tools don't get too tricky on CREATE statements.

* ALTER statements are the most difficult.  MySQL's ALTER is very nice - it mostly just reuses the syntax
used to define columns on the CREATE statement.   PostgreSQL's ALTER has different syntaxes for different
kinds of operations.  Sometimes one MySQL ALTER statement becomes several PostgreSQL ALTER statements.
Again the SQLDialect code hits most of the common cases - but you can write a MySQL ALTER statement to confuse
it.

Sometimes the SQLDialect can't fix a CREATE statement - we cover this next.

CREATE Statement Differences
----------------------------

Thankfully, PostgreSQL `CONSTRAINT` syntax seems pretty compatible with the MySQL `CONSTRAINT`
syntax for primary and foreign keys.  This kind of syntax just works with no transformation:

    file_id      INTEGER NOT NULL AUTO_INCREMENT,

    CONSTRAINT `{$CFG->dbprefix}lti_blob_file_pk` PRIMARY KEY (file_id),

Note the back-quotes that get fixed automatically by SQLDialect.

You can see some working cross-dialect examples at:

https://github.com/tsugiproject/tsugi/blob/master/admin/lti/database.php

Named *indexes* that are *not* constraints not supported as part of `CREATE TABLE` in PostgreSQL.

    CREATE TABLE ...

    INDEX `{$CFG->dbprefix}blob_indx_1` USING HASH ( file_sha256 ),
    INDEX `{$CFG->dbprefix}blob_indx_2` ( path (128) ),
    INDEX `{$CFG->dbprefix}blob_indx_4` ( context_id ),

So these need to be created separately in the `$DATABASE_POST_CREATE` processing
in your `database.php` code.  Make sure to test carefully with both databases before rolling
out because that code only runs when the table is first created.  If the CREATE works and the
post-create fails it will not re-run the post-create.

See:

https://github.com/tsugiproject/tsugi/blob/master/admin/blob/database.php

For an example of creating a named index using `$DATABASE_POST_CREATE`.

INSERT Processing and Duplicate Keys
------------------------------------

Probably the biggest problem to solve is how to handle "ON DUPLICATE KEY" and `lastInsertId()`
processing in a portable way.  Another issue is that in MySQL, you commonly want `lastInsertId()`
to give you the affected whether the "INSERT" clause happend or the "ON DUPLICATE KEY"
triggers.

Also PostgreSQL increments the AUTO INCREMENT sequence *before* it checks if there is a logical
key mismatch.  So if you use an `UPSERT` where more often the query is going to trigger an `UPDATE`,
your primary key sequences end up with lots of gaps.

I am glad I started with the MySQL upsert approach (clean, simple and 95% elegant) and adapted
it to the clunkier PostgreSQL approach inside the abstraction.

Here is some reading about this very different approaches for upsert between the two databases:

* https://www.php.net/manual/en/pdo.lastinsertid.php#102614
* https://dev.mysql.com/doc/refman/5.6/en/insert-on-duplicate.html
* https://stackoverflow.com/questions/10492566/lastinsertid-does-not-work-in-postgresql
* https://stackoverflow.com/questions/34708509/how-to-use-returning-with-on-conflict-in-postgresql
* https://stackoverflow.com/questions/37204749/serial-in-postgres-is-being-increased-even-though-i-added-on-conflict-do-nothing

The most important new code to make UPSERT work across both databases is to add meta data
to PDOX for each of the tables your tool creates in its `database.php`.  You can Meta entries
to the $PDOX variable after the `getConnection()` or `requireData()` calls to start up Tsugi in your code.

    $PDOX = LTIX::getConnection();
    $PDOX->addPDOXMeta("{$p}lti_key", array("pk" => "key_id", "lk" => array("key_sha256")));
    $PDOX->addPDOXMeta("{$p}lti_context", array("pk" => "context_id", "lk" => array("context_sha256", "key_id")));
    $PDOX->addPDOXMeta("{$p}lti_link", array("pk" => "link_id", "lk" => array("link_sha256", "context_id")));
    $PDOX->addPDOXMeta("{$p}cal_event", array("pk" => "event_id"));

    $LAUNCH = LTIX::requireData();
    $LAUNCH->pdox->addPDOXMeta("{$p}lti_key", array("pk" => "key_id", "lk" => array("key_sha256")));
    $LAUNCH->pdox->addPDOXMeta("{$p}lti_context", array("pk" => "context_id", "lk" => array("context_sha256", "key_id")));
    $LAUNCH->pdox->addPDOXMeta("{$p}lti_link", array("pk" => "link_id", "lk" => array("link_sha256", "context_id")));
    $LAUNCH->pdox->addPDOXMeta("{$p}cal_event", array("pk" => "event_id"));

You need to tell PDOX which column is the primary key of the table and which column(s) are
the logical keys for the table.  Some tables do not have a logical key.  If your table does not
have a logical key, you cannot use ON DUPLICATE KEY on that table. Of course On DUPLICATE KEY
by definition is a collision of logical key values so that is kind of moot.

The examples above are the LTIX tables - you do not have to add these particular meta
entries - they are already added by LTIX.  Only add Meta entries for tables you create
in *your* `database.php`.

You can also add Meta information as a comment on every INSERT statement using this syntax:

    INSERT INTO lti_context /*PDOX pk: context_id lk: context_sha256,key_id */

*Important:* Do not place any comment before the name of the table or you will
bypass all UPSERT processing in PDOX.   You can use this as a feature if you
want to write your own different SQL for each database.

You completely suppress the PDOX automatic UPSERT processing with the following syntax:

    INSERT /* upsert */ INTO my_table ... RETURNING ...

This (or any comment before the table name) will keep `PDOX->upsertGetPKReturnError()`
from being run on INSERT statements.

If you are using PostgreSQL and you are executing INSERT statements and PDOX is missing
the Meta entry for your table, you will get error logs and (probably) get SQL syntax errors.
Before you start editing your INSERT syntax - make sure you are adding the correct Meta entries
to PDOX or adding the coment to the query before calling INSERT.

Portable Upsert
---------------

When comparing the advantages and disadvantages of UPSERT between MySQL and PostgreSQL, MySQL
is almost always more elegant than PostgreSQL - the only thing that is better in PostgreSQL is the
`RETURNING` clause - but then - you can't use the PDO `lastInsertId()`.  Keep reading,
we will make everything pretty and easy to use. :)

The portable way to do UPSERT is to use the MySQL "INSERT ON DUPLICATE KEY" approach as follows:

    INSERT INTO {$p}lti_context
        ( context_key, context_sha256, title, key_id, created_at, updated_at ) VALUES
        ( :context_key, :context_sha256, :title, :key_id, NOW(), NOW() )
        ON DUPLICATE KEY UPDATE
        title=:title, updated_at = NOW();

Now sometimes in MySQL, you want to know the primary key that was affected by the UPDATE clause
and so you add a MySQL specific trick with `last_insert_id()`:

    INSERT INTO {$p}lti_context
        ( context_key, context_sha256, title, key_id, created_at, updated_at ) VALUES
        ( :context_key, :context_sha256, :title, :key_id, NOW(), NOW() )
        ON DUPLICATE KEY UPDATE
        context_id=LAST_INSERT_ID(context_id), title=:title, updated_at = NOW();

This is not pretty and very not portable between MySQL and PostgreSQL.  So the portable
PDOX way of doing this is *not* to include the `last_insert_id()` in the UPDATE list and provide
PDOX the Meta information (i.e. the primary key column) and let PDOX append the `last_insert_id()`
clause only when we are sending queries to a MySQL database.

And *then* when we are running the same "portable" query in PostgreSQL, it is transformed into:

    INSERT INTO {$p}lti_context
        ( context_key, context_sha256, title, key_id, created_at, updated_at ) VALUES
        ( :context_key, :context_sha256, :title, :key_id, NOW(), NOW() )
        ON DUPLICATE KEY UPDATE
        title=:title, updated_at = NOW()
        RETURNING context_id;

PDOX also overrides the `lastInsertId()` method to get the generated key using the PostgreSQL
pattern when we are using PostgreSQL.  So the portable way to do an UPSERT with PDOX  is as follows:

    ...  Sometime in the past
    $PDOX->addPDOXMeta("{$p}lti_context", array("pk" => "context_id", "lk" => array("context_sha256", "key_id")));
    ...

    $PDOX->queryDie("INSERT INTO {$p}lti_context
        ( context_key, context_sha256, title, key_id, created_at, updated_at ) VALUES
        ( :context_key, :context_sha256, :title, :key_id, NOW(), NOW() )
        ON DUPLICATE KEY UPDATE
        title=:title, updated_at = NOW();", $value_array);

    $context_id = $PDOX->lastInsertId();

Literally this code that is slightly simpler than the MySQL way of doing it is portable between
SQLite, MySQL, and PostgreSQL.  It means that unless you are doing the `last_insert_id()` trick
you do not have to change your SQL or PHP code *at all* beyond adding the Meta information.  And
if you are using the `last_insert_id()` trick you simply have to remove it to become portable.

Note that the substitution values `:context_sha256` and `:key_id` must match the column names of
the logical keys *exactly* because we need these values for SELECT statements that will be auto generated
to implement UPSERT in PostgreSQL.

Handling Prepared Statements
----------------------------

The `queryDie()` and `queryReturnError()` automatically do a lot of transformation to support
UPSERT use cases.  But sometimes you need to use `prepare()` and `execute()` explicitly.  You are
responsibile for writing portable code when using `prepare()` and `execute()`.  For example in
the following code from the Tsugi's BlobUtil support, you can see the check for PgSQL and adding the
RETURNING clause:

                $fp = fopen($filename, "rb");
                $sql = "INSERT INTO {$CFG->dbprefix}blob_blob
                    (blob_sha256, content, created_at)
                    VALUES (?, ?, NOW())";
                if ( $PDOX->isPgSQL() ) $sql .= "\n RETURNING blob_id";
                $stmt = $PDOX->prepare($sql);

                $stmt->bindParam(1, $sha256);
                $stmt->bindParam(2, $fp, \PDO::PARAM_LOB);
                $PDOX->beginTransaction();
                $stmt->execute();
                $blob_id = 0+$PDOX->lastInsertId();
                $PDOX->commit();
                @fclose($fp);

But what *is* cool is that `lastInsertId()` knows that you are connected to PostgreSQL and uses
the RETURNING pattern to get the primary key (assuming you added RETURNING before prepare) so
you don't need any if-the-else code after `execute()` runs.

PostgreSQL UPSERT Implementation Detail
---------------------------------------

The MySQL portable UPSERT is pretty simple.  Add the `last_insert_id()` trick to any INSERT
with an ON DUPLICATE KEY UPDATE clause.

It is not so simple for PostgreSQL upsert.  Sometimes when we use UPSERT it is "almost always an INSERT"
other times it is an "almost always an UPDATE".  In real PostgreSQL you would write quite different
SQL for these cases to avoid the "sequence gap" problem.

* https://stackoverflow.com/questions/37204749/serial-in-postgres-is-being-increased-even-though-i-added-on-conflict-do-nothing

Aside: At this point real PostgreSQL fans would say 'who cares about your sequences having gaps'.  I say,
'The gaps will be really large when we are doing the "almost always an UPDATE" use case a few billion times on a table
with 250K real rows'.  The the real PostgreSQL fans would say 'Of course! You should write completely
different PostgreSQL-specific highly tweaked SQL for the two cases'. I say, 'but portable..'.  They say,
'No one should ever use MySQL'.  I say, 'sigh'.

But we want to use one syntax (like MySQL does) for both use cases.  As a result, the PDOX implementation breaks
PostgreSQL UPSERT into several separate steps:

* Do a SELECT of the primary key with the logical key(s) in a WHERE clause to see if the record already exists

* If the primary key is not found by the SELECT, perform the INSERT part of the query adding a
RETURNING clause and then check to see if the INSERT worked.
If the INSERT worked (most common case), capture the resulting primary key and return.
If there are multiple INSERTs racing towards the database with the same duplicate key combination,
the later INSERT will fail due to duplicate logical keys, even if the initial SELECT missed because
of a race condition.

* If the INSERT failed because a row with the duplicate logical key(s) are already there because the
current INSERT lost the race, run another SELECT to get the primary key.  This one will work because
one way or another the INSERT is telling us the key exists.

* Since we did not do an INSERT, run the UPDATE clause using the primary key in the WHERE clause.

While this seems like a lot of steps, assuming solid indexes on primary keys, this is the fastest you can do it
without causing PostgreSQL sequence gaps for "update mostly" use cases.

While you might think we should add transactions to avoid race conditions, the race conditions in this approach
are no worse than two INSERT ON DUPLICATE KEY statements racing towards a MySQL server.  All you get is eventual
consistency with "last UPDATE wins" semantics - even in MySQL.  And the extra queries are *only* using logical
keys and primary keys - so they will (or should) be indexed and highly cached.

P.S. If your application requirements need to support multiple simultaneous racing DELETEs and INSERTs
aimed at rows with the same logical key(s) then all bets are of no matter how you build this.  This
turns out to be a pretty common user case in gaming applications but to implement that correctly
you need transactions and non-portable SQL.

Restarting with a Clean PostgreSQL Database
-------------------------------------------

If you are doing any significant development of portable SQL and checking to see if your code
works on PostgreSQL you will need to drop all your tables a few times.
Here is some SQL to get you a set of DROP commands:

    SELECT
        'DROP TABLE IF EXISTS "' || tablename || '" CASCADE;'
    FROM
        pg_tables WHERE schemaname = 'public';

This will give you a series of commands like:

    DROP TABLE IF EXISTS "lti_key" CASCADE;
    DROP TABLE IF EXISTS "lms_plugins" CASCADE;
    DROP TABLE IF EXISTS "lti_context" CASCADE;
    DROP TABLE IF EXISTS "lti_issuer" CASCADE;

Then of course you need to rebuild the Tsugi tables with:

    cd tsugi/admin
    php upgrade.php

That upgrade code will get a lot of testing - which is nice.

Overall Summary
---------------

This is a work in progress.   As you wander into porting your tools to
PostgreSQL - feel free to reach out to the Tsugi dev list to make sure that
when you see an error in your SQL that it might not be your error
and instead be a bug in the Tsugi PDOX or SQLDialect code.  One way or
another it is always good to get a bit of help
and so others can benefit from your experience.





