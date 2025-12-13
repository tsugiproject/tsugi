<?php

namespace Tsugi\Util;

/**
 * Some really small, simple, and self-contained utility public static functions
 */
class FakeName {

    final const ADJECTIVES = [
        'Able','Accurate','Alert','Attentive','Balanced','Calm','Careful','Centered','Clear','Collected',
        'Composed','Considerate','Consistent','Contained','Curious','Decisive','Deliberate','Direct','Discrete','Diligent',
        'Earnest','Efficient','Even','Exact','Fair','Firm','Focused','Gentle','Grounded','Humble',
        'Intent','Judicious','Kind','Level','Logical','Measured','Methodical','Mindful','Moderate','Modest',
        'Neutral','Nimble','Observant','Open','Orderly','Patient','Perceptive','Plain','Poised','Practical',
        'Precise','Prepared','Quiet','Rational','Ready','Reflective','Reliable','Reserved','Resolute','Responsive',
        'Sincere','Sound','Stable','Steady','Subtle','Supportive','Systematic','Thoughtful','Thorough','Timely',
        'Tranquil','Trustworthy','Unassuming','Understanding','Upright','Useful','Vigilant','Warm','Wary','Wise',

        'Adaptable','Anchored','Assured','Attuned','Balanced','Careful','Clearheaded','Coherent','Considered','Delicate',
        'Dependable','Detached','Disciplined','Economical','Elastic','Evenhanded','Exacting','Flexible','Frank','Genuine',
        'Harmonious','Honest','Impartial','Intentional','Measured','Natural','Objective','Ordered','Patient','Plainspoken',
        'Prudent','Reasoned','Reflective','Regular','Reserved','Sensible','Serene','Steadfast','Temperate','Unbiased',
        'Watchful','Wellplaced','Welltimed','Whole','Yielding','Settled','Centered','Grounded','Lucid','Balanced'
     ];

    final const ANIMALS = [
        'Antelope','Badger','Beaver','Bison','Butterfly','Camel','Caribou','Cat','Cheetah','Crane',
        'Deer','Dolphin','Dove','Duck','Eagle','Egret','Elk','Falcon','Finch','Fox',
        'Frog','Gazelle','Goat','Goose','Heron','Horse','Hummingbird','Ibex','Ibis','Jay',
        'Kestrel','Koala','Lark','Lynx','Mallard','Manatee','Marten','Moose','Moth','Newt',
        'Otter','Owl','Panda','Parrot','Pelican','Penguin','Plover','Quail','Rabbit','Raven',
        'Robin','Salamander','Seal','Shearwater','Shrike','Sparrow','Squirrel','Starling','Stork','Swan',
        'Tern','Thrush','Tortoise','Toucan','Trout','Turkey','Turtle','Vole','Wallaby','Weasel',
        'Wigeon','Wolf','Woodcock','Woodpecker','Wren','Yak','Zebra','Chipmunk','Porpoise',

        'Anchovy','Anole','Auk','Barracuda','Bobcat','Bonobo','Bream','Bunting','Capybara',
        'Cormorant','Courser','Cuttlefish','Darter','Dormouse','Eland','Fieldfare','Firefly','Gannet','Grouse',
        'Hare','Harrier','Kingfisher','Lamprey','Leafhopper','Lemming','Loach','Magpie','Mink','Murre',
        'Nuthatch','Oriole','Osprey','Pangolin','Pipit','Puffin','Rail','Redstart','Roedeer','Sandpiper',
        'Skylark','Slowworm','Snipe','Sole','Springbok','Stonechat','Sunbird','Teal','Treefrog','Waxwing',
        'Whimbrel','Whitefish','Wombat','Yellowhammer','Zebu','Zander','Pika','Jerboa','Tamarin','Duiker'
    ];

    /**
     * Generate a deterministic fake name based on a seed value
     *
     * This method generates a consistent, human-readable fake name by combining
     * a randomly selected adjective and animal name. The selection is deterministic
     * based on the provided seed value, meaning the same seed will always produce
     * the same name.
     *
     * The name format is: "{Adjective} {Animal}" (e.g., "Able Antelope", "Wise Wolf")
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



