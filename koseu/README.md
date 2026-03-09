Koseu History
-------------

Back in the day, Dr. Chuck had an idea to make an LMS that could be included in any PHP site
using composer.  Koseu was that LMS.  Koseu had big dreams.  Koseu liked Lumen until the Lumen
projects stopped liking Lumen.

It just turned out that keeping the few bits that were Koseu separate from Tsugi (those bits were
useless without Tsugi BTW) was pretty pointless.

So in Early 2026, the decision was made to merge koseu-php and tsugi-php into the main tsugi repo.
Most of the controllers that lived in the Koseu namespace were moved into the Tsugi name space.

The only thing left in this folder is a legacy class to wake up all the controllers.  All the work
is really done in \Tsugi\Controllers\Tsugi and this class just is a legacy for some routing code
in koseu.php or tsugi.php across the Tsugi universe.

You can see the old Koseu at https://github.com/tsugiproject/koseu-php
