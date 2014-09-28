
Practice for Effective Resource Handle in the TC
================================================

In order to solve problems of CC 2.x import and export as well as making sure that re-registration
updates existing tools rather than spawning new tools, it is important for the LMS to have a
per-tool unique key.

Since Tsugi is intended to be hosting many clones of the same tool many different places and
expecting to just one of many LTI 2.0 tool providers interacting with an LMS, the construction
of this TC-internal unique tool key is important.

Each Tsugi instance will host many different tools and each tool will have a unique code
within that instance:

    resource_handler->resource_type->code

Some example codes are mod_map, mod_attend, mod_pythonauto - these codes are based on the path
where the tool is hosted.

Each Tsugi instance will provide a

    product_instance->guid

Per the LTI specs this guid is to be:

A globally unique identifier for the service provider. As a best practice, this value should match
an Internet domain name assigned by ICANN, but any globally unique identifier is acceptable.

So this will change per instance of Tsugi to be something like:

    lti-tools.dr-chuck.com
    pr4e.dr-chuck.com

So the key used inside the TC will look something like:

    lti-tools.dr-chuck.com/mod_map

An LMS might use the second half of this to search for a logal tool registration for a "mod_map"
on a Common Cartridge import.  And perhaps an import for a "lti-tools.dr-chuck.com/mod_map" might
get linked to "lti.school.edu/mod_map" based on the LMS import heuristics.

Because of this there is a default, overridable prefix added to the resource_handler codes.  This
value is controlled by the configuration variable:

    $CFG->resource_type_prefix = 'tsugi_';

Its default is "tsugi_" - so the real resource key for my map tool hosted on my LTI server
will look more like:

    lti-tools.dr-chuck.com/tsugi_mod_map

Tsugi administrators can play with this prefix and even completely override the code for a particular
tool in the register.php file so tweak how LMS systems and defeat heurititcs that try to match resource
links to existing tools during a CC import.  If for example, I wanted to host a map application that
was so modified that I did not want to have "tsugi_mod_map" placements linked to it, through either
the Tsugi-wide prefix or the code value in register.php my super-duper map might have a key like one of
the following:

    lti-tools.dr-chuck.com/super_mod_map
    lti-tools.dr-chuck.com/tsugi_super_map

It really depends on how LMS system behave as to how we want to tweak these handler codes - but Tsugi
has controls in place that allow tweakage as necessary.

