
This folder is used to checkout Tsugi tools from other repositories like:

https://github.com/tsugitools/

https://github.com/tsugicontrib/

You need to include this folder in the places that Tsugi scans to look for tools
in your `config.php`:

    $CFG->tool_folders = array("admin", "mod");

You need to edit your `config.php` folder to point to this folder
if you want to auto-install into this folder using the Admin UI
in Tsugi.

    $CFG->install_folder = $CFG->dirroot.'/mod';

If you are installing Tsugi in a sub folder, sometimes you
want the tools to be installed and served from a folder in
the parent folder as in this example.

    $CFG->install_folder = $CFG->dirroot.'/../mod';
    $CFG->tool_folders = array("admin", "../mod");

See https://github.com/csev/py4e/ for a more complex example.  You will
note that an index.php and config.php in a `mod` folder make it so 
that when a user navigates to the `/mod` they are redirected
to the `store`.

