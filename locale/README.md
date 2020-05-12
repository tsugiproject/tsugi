To Edit these, you can use poedit, or just go in and hand-edit the 
po file add a translation and then run a command like:

    msgfmt master.po --output-file=master.mo

This will take the text version of the file and update the binary
version - PHP actually reads the binary version so if you are testing
locally - you need to run `msgfmt` before you press refresh on your page.

Installation

    brew install gettext    # Macintosh 


    apt-get install -y gettext  # Ununtu / Linux


