<?php

namespace Kauri\Loan\Test;


use Kauri\Loan\PaymentPeriods;
use Kauri\Loan\Period;
use Kauri\Loan\PeriodInterface;
use PHPUnit\Framework\TestCase;


class PaymentPeriodsTest extends TestCase
{
    /**
     * @dataProvider periodsData
     * @param $averagePeriodLength
     * @param $paymentPeriods
     */
    public function testPaymentPeriods($averagePeriodLength, $paymentPeriods)
    {
        $periodsCollection = new PaymentPeriods($averagePeriodLength);
        $noOfPayments = count($paymentPeriods);
        $totalLength = 0;

        $this->assertEquals(0, $periodsCollection->getNoOfPeriods());
        $this->assertTrue(empty($periodsCollection->getPeriods()));

        foreach ($paymentPeriods as $periodLength) {
            $periodMock = $this->getMockPeriod($periodLength);
            $periodsCollection->add($periodMock);
            $totalLength = $totalLength + $periodLength;
        }

        $periods = $periodsCollection->getPeriods();
        $period = current($periods);
        $length = $period->getLength();

        $this->assertEquals($noOfPayments, $periodsCollection->getNoOfPeriods());
        $this->assertTrue(!empty($periodsCollection->getPeriods()));

        $this->assertEquals($totalLength / $length,
            $periodsCollection->getNumberOfPeriods($period, $periodsCollection::CALCULATION_MODE_EXACT));
        $this->assertEquals($noOfPayments,
            $periodsCollection->getNumberOfPeriods($period,
                $periodsCollection::CALCULATION_MODE_EXACT_INTEREST));
        $this->assertEquals($noOfPayments,
            $periodsCollection->getNumberOfPeriods($period, $periodsCollection::CALCULATION_MODE_AVERAGE));

        $this->assertEquals($length,
            $periodsCollection->getRatePerPeriod($period, 360,
                $periodsCollection::CALCULATION_MODE_EXACT));
        $this->assertEquals($length,
            $periodsCollection->getRatePerPeriod($period, 360,
                $periodsCollection::CALCULATION_MODE_EXACT_INTEREST));
        $this->assertEquals($averagePeriodLength,
            $periodsCollection->getRatePerPeriod($period, 360,
                $periodsCollection::CALCULATION_MODE_AVERAGE));
    }

    /**
     * @dataProvider periodsData
     * @param int $averagePeriodLength
     * @param array $paymentPeriods
     */
    public function testPeriod(int $averagePeriodLength, array $paymentPeriods)
    {
        $periodsCollection = new PaymentPeriods($averagePeriodLength);

        foreach ($paymentPeriods as $periodLength) {
            $periodMock = $this->getMockPeriod($periodLength);
            $periodsCollection->add($periodMock);
        }

        $reversedPeriods = array_reverse($paymentPeriods);

        $periods = $periodsCollection->getPeriods();

        foreach ($periods as $p) {
            array_pop($reversedPeriods);
            $exactPeriodsLength = $periodsCollection->getExactPeriodsLength();
            $averagePeriodsLength = $periodsCollection->getAveragePeriodsLength();

            $this->assertEquals($exactPeriodsLength, array_sum($paymentPeriods));
            $this->assertEquals($averagePeriodsLength, $averagePeriodLength * count($paymentPeriods));
        }
    }

    public function periodsData()
    {
        return [
            [7, [6, 5, 3, 9]],
            [30, [29, 30, 31, 30, 28]]
        ];
    }

    /**
     * @expectedException \Exception
     */
    public function testRatePerPeriodException()
    {
        $periodsCollection = new PaymentPeriods(1);
        $periodsCollection->getRatePerPeriod($this->getMockPeriod(3), 10, 10);
    }

    /**
     * @expectedException \Exception
     */
    public function testNumberOfPeriodsException()
    {
        $periodsCollection = new PaymentPeriods(1);
        $periodsCollection->getNumberOfPeriods($this->getMockPeriod(3), 10);
    }

    /**
     * @param $length
     * @return PeriodInterface
     */
    private function getMockPeriod($length)
    {
        $stub = $this->getMockBuilder(Period::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLength'])
            ->getMock();

        $stub->method('getLength')
            ->willReturn($length);

        return $stub;
    }

}
