<?php

namespace Votemike\Money;

interface MoneyInterface
{
    public function abs(): Money;

    public function add(Money $money): Money;

    /**
     * @param float|string $operator
     * @return Money
     */
    public function divide($operator): Money;

    /**
     * Returns a rounded string with the currency symbol
     *
     * @param bool $displayCountryForUS Set to true if you would like 'US$' instead of just '$'
     * @return string
     */
    public function format(bool $displayCountryForUS = false): string;

    /**
     * Returns a rounded number without currency
     * If the number is negative, the currency is within parentheses
     */
    public function formatForAccounting(): string;

    /**
     * Returns a string consisting of the currency symbol, a rounded int and a suffix
     * e.g. $33k instead of $3321.12
     */
    public function formatShorthand(): string;

    /**
     * The same as format() except that positive numbers always include the + sign
     *
     * @param bool $displayCountryForUS Set to true if you would like 'US$' instead of just '$'
     * @return string
     */
    public function formatWithSign(bool $displayCountryForUS = false): string;

    /**
     * @return float|int|string
     */
    public function getAmount();

    public function getCurrency(): string;

    /**
     * Returns the amount rounded to the correct number of decimal places for that currency
     */
    public function getRoundedAmount(): float;

    /**
     * Invert the amount
     */
    public function inv(): Money;

    /**
     * @param float $operator
     * @return Money
     */
    public function multiply($operator): Money;

    /**
     * A number between 0 and 100
     *
     * @param float $percentage
     * @return Money
     */
    public function percentage($percentage): Money;

    /**
     * Returns rounded clone of Money object, rounded to the correct number of decimal places for that currency
     */
    public function round(): Money;

    /**
     * Pass in an array of percentages to allocate Money in those amounts.
     * Final entry in array gets any remaining units.
     * If the percentages total less than 100, remaining money is allocated to an extra return value.
     *
     * By default, the amounts are rounded to the correct number of decimal places for that currency. This can be disabled by passing false as the second argument.
     *
     * @param float[] $percentages An array of percentages that must total 100 or less
     * @param bool $round
     * @return Money[]
     */
    public function split(array $percentages, bool $round = true): array;

    /**
     * @param Money $money
     * @return Money
     */
    public function sub(Money $money): Money;
}
