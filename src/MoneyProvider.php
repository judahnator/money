<?php

namespace Votemike\Money;

use DomainException;
use InvalidArgumentException;
use Symfony\Component\Intl\Intl;

abstract class MoneyProvider implements MoneyInterface
{

    /**
     * @var float|int|string
     */
    protected $amount;

    /**
     * @var string
     */
    protected $currency;

    /**
     * @param float|int|string $amount
     * @param string $currency
     */
    final public function __construct($amount, string $currency)
    {
        if (!is_numeric($amount)) {
            throw new InvalidArgumentException('Money only accepts numeric amounts');
        }

        if (!array_key_exists($currency, Intl::getCurrencyBundle()->getCurrencyNames())) {
            throw new InvalidArgumentException($currency . ' is not a supported currency');
        }

        $this->amount = $amount;
        $this->currency = $currency;
    }

    final public function __toString(): string
    {
        return $this->format();
    }

    /**
     * @param Money $money
     */
    protected function assertCurrencyMatches(Money $money)
    {
        if ($this->currency !== $money->getCurrency()) {
            throw new DomainException('Currencies must match');
        }
    }

    final public function getAmount()
    {
        return $this->amount;
    }

    final public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Returns rounded clone of Money object, rounded to the correct number of decimal places for that currency
     */
    public function round(): Money
    {
        return new Money($this->getRoundedAmount(), $this->currency);
    }

    /**
     * Pass in an array of percentages to allocate Money in those amounts.
     * Final entry in array gets any remaining units.
     * If the percentages total less than 100, remaining money is allocated to an extra return value.
     *
     * By default, the amounts are rounded to the correct number of decimal places for that currency. This can be disabled by passing false as the second argument.
     *
     * @param float[] $percentages An array of percentages that must total 100 or less
     * @param bool    $round
     *
     * @return Money[]
     */
    public function split(array $percentages, bool $round = true): array
    {
        $totalPercentage = array_sum($percentages);
        if ($totalPercentage > 100) {
            throw new InvalidArgumentException('Only 100% can be allocated');
        }
        $amounts = [];
        $total = 0;
        if (!$round) {
            foreach ($percentages as $percentage) {
                $share = $this->percentage($percentage);
                $total += $share->getAmount();
                $amounts[] = $share;
            }
            if ($totalPercentage != 100) {
                $amounts[] = new Money($this->amount - $total, $this->currency);
            }
            return $amounts;
        }

        $count = 0;

        if ($totalPercentage != 100) {
            $percentages[] = 0; //Dummy record to trigger the rest of the amount being assigned to a final pot
        }

        foreach ($percentages as $percentage) {
            ++$count;
            if ($count == count($percentages)) {
                $amounts[] = new Money($this->amount - $total, $this->currency);
            } else {
                $share = $this->percentage($percentage)->round();
                $total += $share->getAmount();
                $amounts[] = $share;
            }
        }

        return $amounts;
    }
}
