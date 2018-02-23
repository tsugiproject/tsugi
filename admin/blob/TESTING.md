
How to Test The Blob Storage
----------------------------

Blob Storage is pretty complex since Tsugi supports three ways to store blobs.

* Multi instance store in the database (blob_file)
* Single instance store in the database (blob_blob)
* Single instance store in the file system


Every uploaded file has information like MIME type, file name, etc. in a row
in the blob_file table.

Since we regularly/automatically clean out the 12345 key, as a very 
special case, we store the blobs for 12345 in the `blob_file` in the content
column.  There is no attempt to do single store in this case.  If you want to override
this behavior, set the 

    $CFG->testblobs = false;  // Store all blobs in the single-instance store
    $CFG->testblobs = array('12345', '678910');  // More than one key blobs in blob_file
    $CFG->testblobs = '12345'; // Default


Whether blobs are in the database or on disk is controlled by:

    $CFG->dataroot = '/Users/csev/tsugi_blobs';

The default for this is `false` so blobs are stored in the database.


Test Harness
------------

The easiest way to test the blob store is to use the blob sample code from:

    https://github.com/tsugiproject/tsugi-php-samples

Check it out and install it per its instructions.

Test Scenario
-------------

You will need to look at the database to verify your tests.  Having a relatively empty
makes it easier.

Start with the defaults, dont set either `testblobs` or `dataroot`.

Make two copies of a file with different names.

* Run the test harness and launch the Blob tool - you are in the 12345 key.
* Upload both files and verify that there are two rows in `blob_file` and the blob
in both rows
* Look at each of the files in the browser and verify they both work
* Delete both files and verify that the `blob_file` rows are gone

Then set:

    $CFG->testblobs = false;  // To force 12345 to use the single instance store

Do not set `$CFG->dataroot`.

* Upload both files again.  There should be two rows in the `blob_file` and neither
should have a blob in the `content` column.  They should have the same value in the
`blob_id` column and in the `blob_blob` table there should be one row under the
`blob_id`
* Look at each of the files in the browser and verify they both work
* Navigate to `admin/blob` and run (you should not find any un referenced blobs)

    php blobcheck.php

    This is a dry run, use 'php blobcheck.php remove' to actually remove the blobs.
    # unreferenced blobs found=0 delete=0

* Delete both files and verify that the `blob_file` rows are gone - but the `blob_blob`
row is still there!
* Navigate to `admin/blob` and run (you should see the unreferenced blob)

    php blobcheck.php
    This is a dry run, use 'php blobcheck.php remove' to actually remove the blobs.
    DELETE 5 9dad808bd56037d66276e679f7401c08e8723603627abf8f2f7d63b5ff214fb7
    # unreferenced blobs found=1 delete=1

* This should *not* delete the blob - to do so, run:

    php blobcheck.php remove
    This IS NOT A DRILL!
    ...
    DELETE 5 9dad808bd56037d66276e679f7401c08e8723603627abf8f2f7d63b5ff214fb7
    # unreferenced blobs found=1 delete=1

* Check in `blob_blob` and make sure the row is not there.

Now set `$CFG->dataroot` and leave `testblobs` false.

* Upload both files again.  There should be two rows in the `blob_file` and neither
should have a blob in the `content` column.  They should have the same value in the
`path` column.  The `blob_id` should be null and there should be no row added to the
`blob_blob` table.  The file will have a path based ont he sha-256 of the file like:

    /Users/csev/tsugi_blobs/9d/ad/9dad808b...

* Check if the file exists and compare it to the original uploaded file.

* Look at each of the files in the Tsugi Blob Tool UI and verify they both work

* Navigate to `admin/blob` and run (you should not find any unreferenced files)

    php filecheck.php 
    This is a dry run, use 'php filecheck.php remove' to actually remove the files.
    # folders scan=2 skip=0 rm=0
    # files scan=1 skip=0 good=1 rm=0

* Delete both files in the UI and verify that the `blob_file` rows are gone - but the file
on disk is still there!
* Navigate to `admin/blob` and run (you should see the unreferenced file)

    php filecheck.php 
    This is a dry run, use 'php filecheck.php remove' to actually remove the files.
    rm /Users/csev/tsugi_blobs/9d/ad/9dad808bd56037d66276e679f7401c0....
    # folders scan=2 skip=0 rm=0
    # files scan=1 skip=0 good=0 rm=1

* This should *not* delete the file - to do so, run:

    php filecheck.php remove
    This IS NOT A DRILL!
    ...
    rm /Users/csev/tsugi_blobs/9d/ad/9dad808bd56037d66276e679f7401c08e8723603627abf8f2f7d63b5ff214fb7
    rmdir /Users/csev/tsugi_blobs/9d/ad
    rmdir /Users/csev/tsugi_blobs/9d
    # folders scan=2 skip=0 rm=2
    # files scan=1 skip=0 good=0 rm=1

* Check to make sure the file (and folders) are not there.



