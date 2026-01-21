<?php
// I took this from somewhere - I think www.php.net comments but it is not there

namespace Tsugi\Util;

/**
 * This provides a deterministic pseudo-random sequence that is seedable
 *
 * Since PHP 5.2.1 mt_srand() and mt_rand() don't generate 
 * predictable sequences since PHP 5.2.1.  The following code:
 * 
 * $code = 12345;
 * $MT = new \Tsugi\Util\Mersenne_Twister($code);
 * for($i=0; $i < 30; $i++ ) {
 *     $ran = $MT->getNext(0,6);
 *     echo $ran." ";
 * }
 * 
 * Will always print out:
 * 
 * 2 3 3 6 5 1 5 3 4 2 1 6 5 4 0 6 0 2 0 3 5 6 6 3 1 3 2 6 3 3 
 * 
 * Note that this is written for a 64-bit system and will generate
 * different sequences on 32 and 64 bit systems.
 */

class Mersenne_Twister
{
  const MAX = 0x8fffffff;
  private $state = array ();
  private $index = 0;

  public function __construct($seed = null) {
    if ($seed === null)
      $seed = mt_rand();

    $this->setSeed($seed);
  }

  public function setSeed($seed) {
    $this->state[0] = $seed & 0xffffffff;

    for ($i = 1; $i < 624; $i++) {
      $this->state[$i] = (((0x6c078965 * ($this->state[$i - 1] ^ ($this->state[$i - 1] >> 30))) + $i)) & 0xffffffff;
    }

    $this->index = 0;
  }

  private function generateTwister() {
    for ($i = 0; $i < 624; $i++) {
      $y = (($this->state[$i] & 0x1) + ($this->state[$i] & 0x7fffffff)) & 0xffffffff;
      $this->state[$i] = ($this->state[($i + 397) % 624] ^ ($y >> 1)) & 0xffffffff;

      if (($y % 2) == 1) {
        $this->state[$i] = ($this->state[$i] ^ 0x9908b0df) & 0xffffffff;
      }
    }
  }

/**
 * Get the next pseudo-random number in the sequence
 *
 * Returns the next pseudo-random number in the range specified.
 * The $max and $min are inclusive.
 * If $max and $min are omitted a large integer is returned.
 * 
 * @param $min The low end of the range (optional)
 * @param $max The high end of the range (optional)
 */
  public function getNext($min = null, $max = null) {
    if (($min === null && $max !== null) || ($min !== null && $max === null))
      throw new Exception('Invalid arguments');

    if ($this->index === 0) {
      $this->generateTwister();
    }

    $y = $this->state[$this->index];
    $y = ($y ^ ($y >> 11)) & 0xffffffff;
    $y = ($y ^ (($y << 7) & 0x9d2c5680)) & 0xffffffff;
    $y = ($y ^ (($y << 15) & 0xefc60000)) & 0xffffffff;
    $y = ($y ^ ($y >> 18)) & 0xffffffff;

    $this->index = ($this->index + 1) % 624;

    if ($min === null && $max === null)
      return $y;

    $range = abs($max - $min);

    return min($min, $max) + ($y % ($range + 1));
  }

/**
 * Return a truncated Gaussian for this class
 *
 * @param int $max The maximum value we want to return - the returned value is in 0..$max.
 * @param float $lambda Controls the steepness of the curve (i.e. the average
 * of the returned values).  If this is 0.01
 * it is almost a uniform distribution with an average of about 0.4*$max;  If this 
 * if this is 1.0, over 2/3 of the time this function will return 0 (i.e. a very quick drop-off).  
 * Values in the range 0.1 - 0.4 return gaussian distros that are distinctly exponential in drop-off
 * and have a reasonably interesting average.
 */
// http://stats.stackexchange.com/questions/68274/how-to-sample-from-an-exponential-distribution-using-rejection-sampling-in-php
  public function gaussian($max, $lambda = 0.2) {
    $tau = $max;
    $u = 1.0 * $this->getNext(0, self::MAX) / self::MAX;
    $x = - log(1 - (1 - exp(- $lambda * $tau)) * $u) / $lambda;
    $ran = (int) $x;
    if ( $ran >= $max ) $ran=$max-1; // If $tao and $lambda are wack
    return $ran;
  }

/**
 * Shuffle an array using this class
 */
// http://stackoverflow.com/questions/6557805/randomize-a-php-array-with-a-seed
// http://bost.ocks.org/mike/algorithms/#shuffling
  public function shuffle($arr)
  {
    $new = $arr;
    for ($i = count($new) - 1; $i > 0; $i--)
    {
        $j = $this->getNext(0,$i);
        $tmp = $new[$i];
        $new[$i] = $new[$j];
        $new[$j] = $tmp;
    }
    return $new;
  }
}
