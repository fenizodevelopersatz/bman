<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * BCMath money helper — scale 8 (BMAN), used anywhere a wallet or ledger
 * amount is added, subtracted, multiplied, or compared. Never do PHP float
 * arithmetic on money; float summation of DECIMAL(18,8)/DECIMAL(30,8) ledger
 * values drifts (e.g. 8.2 + 4.5 + 2.5 + 1.3 == 16.499999999999998 in IEEE754).
 * Values in, values out are numeric strings — cast to float only at the
 * final display boundary (number_format/json_encode), never mid-calculation.
 */
if (!class_exists('Money')) {
    final class Money
    {
        private const SCALE = 8;

        public static function add($a, $b, $scale = self::SCALE)
        {
            return bcadd((string) $a, (string) $b, $scale);
        }

        public static function sub($a, $b, $scale = self::SCALE)
        {
            return bcsub((string) $a, (string) $b, $scale);
        }

        public static function mul($a, $b, $scale = self::SCALE)
        {
            return bcmul((string) $a, (string) $b, $scale);
        }

        public static function div($a, $b, $scale = self::SCALE)
        {
            return bcdiv((string) $a, (string) $b, $scale);
        }

        public static function cmp($a, $b, $scale = self::SCALE)
        {
            return bccomp((string) $a, (string) $b, $scale); // -1, 0, 1
        }

        /** Round DOWN so the platform never pays/locks more than owed. */
        public static function floor8($v)
        {
            return bcadd((string) $v, '0', self::SCALE); // bc* truncates, never rounds up
        }

        public static function floor6($v)
        {
            return bcadd((string) $v, '0', 6);
        }

        /** Treat sub-dust as zero. */
        public static function isZero($v, $scale = self::SCALE)
        {
            return bccomp((string) $v, '0', $scale) === 0;
        }

        /** Clamp negative results to '0' (e.g. after a subtraction that could overshoot). */
        public static function floorZero($v, $scale = self::SCALE)
        {
            return self::cmp($v, '0', $scale) < 0 ? '0' : (string) $v;
        }
    }
}
