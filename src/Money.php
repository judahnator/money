<?php namespace Votemike\Money;


class Money implements MoneyInterface
{

    /**
     * @var MoneyProvider
     */
    private $provider;

    /**
     * @param float|int|string $amount
     * @param string $currency
     */
    public function __construct($amount, string $currency)
    {
        if (extension_loaded('bcmath')) {
            $this->provider = new BcMathMoney($amount, $currency);
        }else {
            $this->provider = new BasicMoney($amount, $currency);
        }
    }

    final public function __toString(): string
    {
        return $this->format();
    }

    /**
     * Returns a positive clone of Money object
     */
    public function abs(): Money
    {
        return $this->provider->abs();
    }

    public function add(Money $money): Money
    {
        return $this->provider->add($money);
    }

    /**
     * @param float $operator
     * @return static
     */
    public function divide(float $operator): Money
    {
        return $this->provider->divide($operator);
    }

    /**
     * Returns a rounded string with the currency symbol
     *
     * @param bool $displayCountryForUS Set to true if you would like 'US$' instead of just '$'
     * @return string
     */
    public function format(bool $displayCountryForUS = false): string
    {
        return $this->provider->format($displayCountryForUS);
    }

    /**
     * Returns a rounded number without currency
     * If the number is negative, the currency is within parentheses
     */
    public function formatForAccounting(): string
    {
        return $this->provider->formatForAccounting();
    }

    /**
     * Returns a string consisting of the currency symbol, a rounded int and a suffix
     * e.g. $33k instead of $3321.12
     */
    public function formatShorthand(): string
    {
        return $this->provider->formatShorthand();
    }

    /**
     * The same as format() except that positive numbers always include the + sign
     *
     * @param bool $displayCountryForUS Set to true if you would like 'US$' instead of just '$'
     * @return string
     */
    public function formatWithSign(bool $displayCountryForUS = false): string
    {
        return $this->provider->formatWithSign($displayCountryForUS);
    }

    public function getAmount(): float
    {
        return $this->provider->getAmount();
    }

    public function getCurrency(): string
    {
        return $this->provider->getCurrency();
    }

    /**
     * Returns the amount rounded to the correct number of decimal places for that currency
     */
    public function getRoundedAmount(): float
    {
        return $this->provider->getRoundedAmount();
    }

    /**
     * Invert the amount
     */
    public function inv(): Money
    {
        return $this->provider->inv();
    }

    /**
     * @param float $operator
     * @return static
     */
    public function multiply($operator): Money
    {
        return $this->provider->multiply($operator);
    }

    /**
     * A number between 0 and 100
     *
     * @param float $percentage
     * @return static
     */
    public function percentage($percentage): Money
    {
        return $this->provider->percentage($percentage);
    }

    /**
     * Returns rounded clone of Money object, rounded to the correct number of decimal places for that currency
     */
    public function round(): Money
    {
        return $this->provider->round();
    }

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
    public function split(array $percentages, bool $round = true): array
    {
        return $this->provider->split($percentages, $round);
    }

    /**
     * @param Money $money
     * @return static
     */
    public function sub(Money $money): Money
    {
        return $this->provider->sub($money);
    }

}
