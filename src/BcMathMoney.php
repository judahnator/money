<?php

namespace Votemike\Money;


use InvalidArgumentException;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Intl\NumberFormatter\NumberFormatter;

final class BcMathMoney extends MoneyProvider
{

    public function abs(): Money
    {
        if (bccomp($this->amount, 0) === -1) {
            return $this->inv();
        }
        return new Money($this->amount, $this->currency);
    }

    public function add(Money $money): Money
    {
        $this->assertCurrencyMatches($money);

        return new Money(bcadd($this->amount, $money->getAmount()), $this->currency);
    }

    /**
     * @param float $operator
     *
     * @return Money
     */
    public function divide(float $operator): Money
    {
        if (bccomp($operator, 0) === 0) {
            throw new InvalidArgumentException('Cannot divide by zero');
        }
        return new Money(
            bcdiv(
                $this->amount,
                $operator,
                Intl::getCurrencyBundle()
                    ->getFractionDigits($this->currency)
            ),
            $this->currency
        );
    }

    /**
     * Returns a rounded string with the currency symbol
     *
     * @param bool $displayCountryForUS Set to true if you would like 'US$' instead of just '$'
     *
     * @return string
     */
    public function format(bool $displayCountryForUS = false): string
    {
        $formatter = new NumberFormatter('en', NumberFormatter::CURRENCY);

        if ($displayCountryForUS && $this->currency === 'USD') {

            if (bccomp($this->amount, 0) >= 0) {
                return 'US' . $formatter->formatCurrency($this->amount, $this->currency);
            }
            return '-US' . $formatter->formatCurrency(-$this->amount, $this->currency);
        }

        return $formatter->formatCurrency($this->amount, $this->currency);
    }

    /**
     * Returns a rounded number without currency
     * If the number is negative, the currency is within parentheses
     */
    public function formatForAccounting(): string
    {
        $amount = $this->getRoundedAmount();
        $negative = (bccomp($amount, 0) === -1);
        if ($negative) {
            $amount = bcmul($amount, -1, static::bcscale($amount));
        }
        $amount = number_format($amount, Intl::getCurrencyBundle()->getFractionDigits($this->currency));
        return $negative ? '(' . $amount . ')' : $amount;
    }

    /**
     * Returns a string consisting of the currency symbol, a rounded int and a suffix
     * e.g. $33k instead of $3321.12
     */
    public function formatShorthand(): string
    {
        $amount = self::bcround($this->amount);
        $negative = (bccomp($amount, 0) === -1);
        if ($negative) {
            $amount = bcmul($amount, -1);
        }
        if ($amount) {
            $power = self::bcfloor(log10($amount));
            $power -= $power % 3; // get nearest thousand power
            if ($power > 1) {
                $amount = self::bcround(bcdiv($amount, bcpow(10, $power), $power));
                $unitIndex = $power / 3;
            }else {
                $unitIndex = 0;
            }
            $unit = ['', 'k', 'm', 'bn', 'tn'][$unitIndex];
        }else {
            $unit = '';
        }
        return
            ($negative ? '-' : '').
            Intl::getCurrencyBundle()->getCurrencySymbol($this->currency, 'en').
            $amount.
            $unit;

    }

    /**
     * The same as format() except that positive numbers always include the + sign
     *
     * @param bool $displayCountryForUS Set to true if you would like 'US$' instead of just '$'
     *
     * @return string
     */
    public function formatWithSign(bool $displayCountryForUS = false): string
    {
        $string = $this->format($displayCountryForUS);

        if (bccomp($this->amount, 0) !== 1) {
            return $string;
        }

        return '+' . $string;
    }

    /**
     * Returns the amount rounded to the correct number of decimal places for that currency
     */
    public function getRoundedAmount(): float
    {
        $fractionDigits = Intl::getCurrencyBundle()->getFractionDigits($this->currency);
        $roundingIncrement = Intl::getCurrencyBundle()->getRoundingIncrement($this->currency);

        $value = self::bcround($this->amount, $fractionDigits);

        // Swiss rounding
        if (0 < $roundingIncrement && 0 < $fractionDigits) {
            $roundingFactor = $roundingIncrement / pow(10, $fractionDigits);
            $value = bcmul(self::bcround(bcdiv($value, $roundingFactor)), $roundingFactor);
        }

        return $value;
    }

    /**
     * Invert the amount
     */
    public function inv(): Money
    {
        return $this->multiply(-1);
    }

    /**
     * @param float $operator
     *
     * @return Money
     */
    public function multiply($operator): Money
    {
        return new Money(
            bcmul(
                $this->amount,
                $operator,
                max(self::bcscale($this->amount), self::bcscale($operator))
            ),
            $this->currency
        );
    }

    /**
     * A number between 0 and 100
     *
     * @param float $percentage
     *
     * @return Money
     */
    public function percentage($percentage): Money
    {
        return $this->multiply($percentage / 100);
    }

    /**
     * @param Money $money
     *
     * @return Money
     */
    public function sub(Money $money) : Money
    {
        $this->assertCurrencyMatches($money);

        return new Money(bcsub($this->amount, $money->getAmount()), $this->currency);
    }

    private static function bcfloor($number)
    {
        if (strpos($number, '.') !== false) {
            if (preg_match("~\.[0]+$~", $number)) return bcround($number, 0);
            if ($number[0] != '-') return bcadd($number, 0, 0);
            return bcsub($number, 1, 0);
        }
        return $number;
    }

    private static function bcround($number, $precision = 0)
    {
        if (strpos($number, '.') === false) {
            return $number;
        }

        list($left, $right) = explode('.', $number);

        // we don't care about the full number
        $right = substr($right, 0, $precision + 1);

        $digits = str_split($right);

        while (count($digits) > $precision) {
            $lastChar = array_pop($digits);
            if ($lastChar >= 5) {

                $position = 1;
                $resolved = false;
                while (!$resolved) {

                    // We went all the way up.
                    if ((count($digits) - $position) < 0) {
                        if ($left[0] === '-') {
                            return $lastChar >= 5 ? $left - 1 : $left;
                        }
                        return $left + 1;
                    }

                    $digit =& $digits[count($digits) - $position];
                    $digit++;
                    if ($digit === 10) {
                        $digit = 0;
                        $position++;
                    }else {
                        $resolved = true;
                    }
                    unset($digit);
                }

            }
        }

        $right = implode('', $digits);

        return "{$left}".($right ? ".{$right}" : '');
    }

    private static function bcscale($number): int
    {
        if (strpos($number, '.') === false) {
            return 0;
        }
        list ($left, $right) = explode('.', $number);
        return strlen($right);
    }
}