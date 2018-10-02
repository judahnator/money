<?php

namespace Votemike\Money;


use InvalidArgumentException;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Intl\NumberFormatter\NumberFormatter;

final class BasicMoney extends MoneyProvider
{

    public function abs(): Money
    {
        return new Money(abs($this->amount), $this->currency);
    }

    public function add(Money $money): Money
    {
        $this->assertCurrencyMatches($money);

        return new Money($this->amount + $money->getAmount(), $this->currency);
    }

    /**
     * @param float $operator
     *
     * @return Money
     */
    public function divide($operator): Money
    {
        if ($operator == 0) {
            throw new InvalidArgumentException('Cannot divide by zero');
        }
        return new Money($this->amount / $operator, $this->currency);
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
            if ($this->amount >= 0) {
                return 'US' . $formatter->formatCurrency((float)$this->amount, $this->currency);
            }
            return '-US' . $formatter->formatCurrency((float)(-$this->amount), $this->currency);
        }
        return $formatter->formatCurrency((float)$this->amount, $this->currency);
    }

    /**
     * Returns a rounded number without currency
     * If the number is negative, the currency is within parentheses
     */
    public function formatForAccounting(): string
    {
        $amount = $this->getRoundedAmount();
        $negative = 0 > $amount;
        if ($negative) {
            $amount *= -1;
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
        $amount = $this->amount;
        $negative = 0 > $amount;
        if ($negative) {
            $amount *= -1;
        }
        $units = ['', 'k', 'm', 'bn', 'tn'];
        $power = $amount > 0 ? floor(log(round($amount), 1000)) : 0;
        $ret = Intl::getCurrencyBundle()->getCurrencySymbol($this->currency, 'en').round($amount / pow(1000, $power), 0). $units[$power];
        return $negative ? '-'.$ret : $ret;
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

        if ($this->amount <= 0) {
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

        $value = round($this->amount, $fractionDigits);

        // Swiss rounding
        if (0 < $roundingIncrement && 0 < $fractionDigits) {
            $roundingFactor = $roundingIncrement / pow(10, $fractionDigits);
            $value = round($value / $roundingFactor) * $roundingFactor;
        }

        return $value;
    }

    /**
     * Invert the amount
     */
    public function inv(): Money
    {
        return new Money(-$this->amount, $this->currency);
    }

    /**
     * @param float $operator
     *
     * @return Money
     */
    public function multiply($operator): Money
    {
        return new Money($this->amount * $operator, $this->currency);
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
        return new Money(($this->amount * $percentage) / 100, $this->currency);
    }

    /**
     * @param Money $money
     *
     * @return Money
     */
    public function sub(Money $money) : Money
    {
        $this->assertCurrencyMatches($money);

        return new Money($this->amount - $money->getAmount(), $this->currency);
    }
}