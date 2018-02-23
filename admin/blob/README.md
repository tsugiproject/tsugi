

Sample Executions
-----------------

    $ php blobcheck.php
    This is a dry run, use 'php blobcheck.php remove' to actually remove the blobs.
    DELETE 4 69893b55bd8c9c5c53df72e3ea7e325cd2df8729d87b4dedce43630d668e6e1b
    # unreferenced blobs found=1 delete=1

    $ php blobcheck.php remove
    This IS NOT A DRILL!
    ...
    DELETE 4 69893b55bd8c9c5c53df72e3ea7e325cd2df8729d87b4dedce43630d668e6e1b
    # unreferenced blobs found=1 delete=1

    $ php blobcheck.php 
    This is a dry run, use 'php blobcheck.php remove' to actually remove the blobs.
    # unreferenced blobs found=0 delete=0

    $ php filecheck.php 
    This is a dry run, use 'php filecheck.php remove' to actually remove the files.
    rm /Users/csev/tsugi_blobs/7c/95/7c954bc7bcf1939d02917bef65cc52e7bdf98548f526218ef633152934334967
    This is a dry run, use 'php filecheck.php remove' to actually remove the files.
    # folders scan=4 skip=0 rm=0
    # files scan=2 skip=0 good=1 rm=1

    $ php filecheck.php remove
    This IS NOT A DRILL!
    ...
    rm /Users/csev/tsugi_blobs/7c/95/7c954bc7bcf1939d02917bef65cc52e7bdf98548f526218ef633152934334967
    rmdir /Users/csev/tsugi_blobs/7c/95
    rmdir /Users/csev/tsugi_blobs/7c
    # folders scan=4 skip=0 rm=2
    # files scan=2 skip=0 good=1 rm=1

    $ php filecheck.php 
    This is a dry run, use 'php filecheck.php remove' to actually remove the files.
    This is a dry run, use 'php filecheck.php remove' to actually remove the files.
    # folders scan=2 skip=0 rm=0
    # files scan=1 skip=0 good=1 rm=0

