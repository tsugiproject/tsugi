<?php

namespace Tsugi\Util;

/**
 * Some really small, simple, and self-contained utility public static functions
 */
class FakeName {

    final const ADJECTIVES = [
        'Active','Aligned','Anchored','Applied','Attentive','Balanced','Baseline',
        'Careful','Centered','Clear','Coherent','Consistent','Contained','Contextual',
        'Deliberate','Defined','Directed','Discrete','Documented',
        'Economical','Even','Explicit','Exact',
        'Focused','Formal','Framed','Functional',
        'Grounded','Handled','Incremental','Indexed','Intentional',
        'Level','Limited','Linear','Local','Logical',
        'Measured','Methodical','Moderate','Modular',
        'Neutral','Noted','Observed','Operational','Ordered',
        'Plain','Positioned','Prepared','Primary','Procedural',
        'Qualified','Referenced','Regular','Related',
        'Scoped','Secondary','Selective','Sequential','Situated',
        'Specified','Stable','Structured','Subtle','Supported',
        'Systematic','Technical','Temporal','Traceable','Typical',
        'Unbiased','Unified','Validated','Variable','Visible',

        // extra depth
        'Abstracted','Aggregated','Annotated','Bounded','Calibrated',
        'Classified','Compared','Constrained','Delimited','Derived',
        'Enumerated','Estimated','Examined','Filtered','Grouped',
        'Identified','Isolated','Mapped','Normalized','Outlined',
        'Parsed','Reduced','Separated','Summarized','Tracked',

        // Nerdy / systems
        'Binary','Canonical','Deterministic',
        'Idempotent','Immutable','Orthogonal',
        'Portable','Predictable','Queryable','Serializable',
        'Stateless','Stateful','Synchronous','Versioned',
        'Composable','Invariant','Monotonic','Referential','Reproducible',
        'Convergent','Declarative','Idiosyncratic',
        'Interoperable','Isomorphic','Probabilistic','Observable',

         // AI / data
         'Stochastic','Predictive','Generative','Embedded','Latent',
         'Parametric','Trained','Ephemeral', 'Symmetric',
     ];

    final const ANIMALS = [
        // Birds
        'Finch','Sparrow','Wren','Thrush','Starling','Robin','Lark','Jay','Ibis','Egret','Heron',
        'Duck','Teal','Quail','Plover','Sandpiper','Mallard','Kestrel','Grouse','Magpie','Murre',
        'Nuthatch','Oriole','Osprey','Pipit','Puffin','Rail','Redstart','Roedeer','Skylark','Snipe',
        'Sole','Stonechat','Sunbird','Waxwing','Whimbrel','Wigeon',

        // Mammals
        'Deer','Goat','Sheep','Rabbit','Hare','Caribou','Elk','Ibex','Capybara',
        'Bobcat','Bonobo','Koala','Marten','Mink','Wombat','Pangolin','Jerboa','Tamarin','Pika','Chipmunk','Otter',

        // Amphibians / Reptiles
        'Frog','Newt','Salamander','Tortoise','Terrapin','Treefrog',

        // Fish
        'Minnow','Bream','Whitefish','Trout','Goby','Barracuda','Cuttlefish','Darter','Loach','Lamprey','Anchovy',

        // Invertebrates / insects
        'Anole','Auk','Bunting','Cormorant','Courser','Leafhopper',

        // Nerdy Nouns
        'Array', 'Matrix', 'Vector', 'Bit', 'Byte', 'Packet',
        'Thread', 'Core', 'Process',
    ];

    /**
     * Generate a deterministic fake name based on a seed value
     *
     * This method generates a consistent, human-readable fake name by combining
     * a randomly selected adjective and animal name. The selection is deterministic
     * based on the provided seed value, meaning the same seed will always produce
     * the same name.
     *
     * The name format is: "{Adjective} {Animal}" (e.g., "Active Antelope", "Neutral Trout")
     *
     * @param mixed $seed The seed value used to generate the name. Can be any type
     *                    that can be converted to a string (e.g., user ID, email, etc.).
     *                    The seed is hashed using CRC32 with '42' appended to ensure
     *                    consistent results.
     *
     * @return string A fake name consisting of an adjective and animal name separated
     *                by a space.
     *
     * @example
     * <code>
     * echo FakeName::getname($USER->id);  // Output: "Able Antelope"
     * echo FakeName::getname('test@example.com');  // Output: "Wise Wolf"
     * </code>
     */
    public static function getname($seed) {
        $seed = crc32($seed.'42');
        mt_srand($seed);

        $name =
            self::ADJECTIVES[array_rand(self::ADJECTIVES)] . ' ' .
            self::ANIMALS[array_rand(self::ANIMALS)];
        return $name;
    }

}



