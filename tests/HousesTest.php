<?php

namespace SwissEph\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SwissEph\Ayanamsa;
use SwissEph\Catalog;
use SwissEph\DeltaT;
use SwissEph\Houses;

final class HousesTest extends TestCase
{
    #[DataProvider('houseNameProvider')]
    public function testHouseNameMatchesSwissEphemeris(string|int $hsys, string $expected): void
    {
        self::assertSame($expected, Houses::houseName($hsys));
    }

    public function testEqualCuspsFromAscendant(): void
    {
        $cusps = Houses::equalCusps(123.4, 90.0, Houses::HSYS_EQUAL);

        self::assertEqualsWithDelta(123.4, $cusps[1], 1e-12);
        self::assertEqualsWithDelta(153.4, $cusps[2], 1e-12);
        self::assertEqualsWithDelta(93.4, $cusps[12], 1e-12);
    }

    public function testEqualCuspsFlipsAscendantWhenItIsBelowHorizon(): void
    {
        $cusps = Houses::equalCusps(270.0, 90.0, Houses::HSYS_EQUAL_ASC);

        self::assertEqualsWithDelta(90.0, $cusps[1], 1e-12);
        self::assertEqualsWithDelta(120.0, $cusps[2], 1e-12);
        self::assertEqualsWithDelta(60.0, $cusps[12], 1e-12);
    }

    public function testEqualMcCusps(): void
    {
        $cusps = Houses::equalCusps(123.4, 201.5, Houses::HSYS_EQUAL_MC);

        self::assertEqualsWithDelta(291.5, $cusps[1], 1e-12);
        self::assertEqualsWithDelta(201.5, $cusps[10], 1e-12);
        self::assertEqualsWithDelta(231.5, $cusps[11], 1e-12);
        self::assertEqualsWithDelta(261.5, $cusps[12], 1e-12);
    }

    public function testEqualAriesCusps(): void
    {
        $cusps = Houses::equalCusps(123.4, 201.5, Houses::HSYS_EQUAL_ARIES);

        self::assertSame(0.0, $cusps[1]);
        self::assertSame(30.0, $cusps[2]);
        self::assertSame(330.0, $cusps[12]);
    }

    public function testWholeSignCusps(): void
    {
        $cusps = Houses::equalCusps(359.9, 201.5, Houses::HSYS_WHOLE_SIGN);

        self::assertSame(330.0, $cusps[1]);
        self::assertSame(0.0, $cusps[2]);
        self::assertSame(300.0, $cusps[12]);
    }

    public function testEqualCuspsRejectsNonEqualSystem(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Houses::equalCusps(123.4, 201.5, Houses::HSYS_PLACIDUS);
    }

    public function testHousesPlacidusCuspsMatchSwissEphemeris(): void
    {
        $result = Houses::houses(2451545.0, 59.0, 30.0, Houses::HSYS_PLACIDUS);
        $cusps = $result['cusps'];
        $ascmc = $result['ascmc'];

        self::assertEqualsWithDelta(86.811765333333, $cusps[1], 1e-4);
        self::assertEqualsWithDelta(99.717949055556, $cusps[2], 1e-4);
        self::assertEqualsWithDelta(112.387588055556, $cusps[3], 1e-4);
        self::assertEqualsWithDelta(128.040521666667, $cusps[4], 1e-4);
        self::assertEqualsWithDelta(152.429186944444, $cusps[5], 1e-4);
        self::assertEqualsWithDelta(201.600786416667, $cusps[6], 1e-4);
        self::assertEqualsWithDelta(266.811765333333, $cusps[7], 1e-4);
        self::assertEqualsWithDelta(279.717949055556, $cusps[8], 1e-4);
        self::assertEqualsWithDelta(292.387588055556, $cusps[9], 1e-4);
        self::assertEqualsWithDelta(308.040521666667, $cusps[10], 1e-4);
        self::assertEqualsWithDelta(332.429186944444, $cusps[11], 1e-4);
        self::assertEqualsWithDelta(21.600786416667, $cusps[12], 1e-4);

        self::assertEqualsWithDelta(310.457072444444, $ascmc[2], 1e-4);
    }

    public function testAxesFromArmcMatchSwissEphemeris(): void
    {
        $result = Houses::housesArmc(310.4570724, 59.0, 23.4376767, Houses::HSYS_EQUAL);
        $ascmc = $result['ascmc'];

        self::assertEqualsWithDelta(86.811765333333, $ascmc[0], 1e-7);
        self::assertEqualsWithDelta(308.040521666667, $ascmc[1], 1e-7);
        self::assertEqualsWithDelta(310.4570724, $ascmc[2], 1e-12);
        self::assertEqualsWithDelta(214.699781472222, $ascmc[3], 1e-7);
        self::assertEqualsWithDelta(42.906647861111, $ascmc[4], 1e-7);
        self::assertEqualsWithDelta(25.505211722222, $ascmc[5], 1e-7);
        self::assertEqualsWithDelta(54.718285888889, $ascmc[6], 1e-7);
        self::assertEqualsWithDelta(205.505211722222, $ascmc[7], 1e-7);
    }

    public function testHousesArmcEqualCuspsMatchSwissEphemeris(): void
    {
        $result = Houses::housesArmc(310.4570724, 59.0, 23.4376767, Houses::HSYS_EQUAL);
        $cusps = $result['cusps'];

        self::assertEqualsWithDelta(86.811765333333, $cusps[1], 1e-7);
        self::assertEqualsWithDelta(116.811765333333, $cusps[2], 1e-7);
        self::assertEqualsWithDelta(356.811765333333, $cusps[10], 1e-7);
        self::assertEqualsWithDelta(56.811765333333, $cusps[12], 1e-7);
    }

    public function testHousesArmcEqualMcCuspsMatchSwissEphemeris(): void
    {
        $result = Houses::housesArmc(310.4570724, 59.0, 23.4376767, Houses::HSYS_EQUAL_MC);
        $cusps = $result['cusps'];

        self::assertEqualsWithDelta(38.040521666667, $cusps[1], 1e-7);
        self::assertEqualsWithDelta(308.040521666667, $cusps[10], 1e-7);
        self::assertEqualsWithDelta(338.040521666667, $cusps[11], 1e-7);
        self::assertEqualsWithDelta(8.040521666667, $cusps[12], 1e-7);
    }

    public function testHouseArmcWholeSignCuspsMatchSwissEphemeris(): void
    {
        $result = Houses::housesArmc(310.4570724, 59.0, 23.4376767, Houses::HSYS_WHOLE_SIGN);
        $cusps = $result['cusps'];

        self::assertSame(60.0, $cusps[1]);
        self::assertSame(90.0, $cusps[2]);
        self::assertSame(330.0, $cusps[10]);
        self::assertSame(30.0, $cusps[12]);
    }

    public function testHousesArmcPorphyryCuspsMatchSwissEphemeris(): void
    {
        $result = Houses::housesArmc(310.4570724, 59.0, 23.4376767, Houses::HSYS_PORPHYRY);
        $cusps = $result['cusps'];

        self::assertEqualsWithDelta(86.811765333333, $cusps[1], 1e-7);
        self::assertEqualsWithDelta(100.554684111111, $cusps[2], 1e-7);
        self::assertEqualsWithDelta(114.297602888889, $cusps[3], 1e-7);
        self::assertEqualsWithDelta(128.040521666667, $cusps[4], 1e-7);
        self::assertEqualsWithDelta(174.297602888889, $cusps[5], 1e-7);
        self::assertEqualsWithDelta(220.554684111111, $cusps[6], 1e-7);
        self::assertEqualsWithDelta(266.811765333333, $cusps[7], 1e-7);
        self::assertEqualsWithDelta(280.554684111111, $cusps[8], 1e-7);
        self::assertEqualsWithDelta(294.297602888889, $cusps[9], 1e-7);
        self::assertEqualsWithDelta(308.040521666667, $cusps[10], 1e-7);
        self::assertEqualsWithDelta(354.297602888889, $cusps[11], 1e-7);
        self::assertEqualsWithDelta(40.554684111111, $cusps[12], 1e-7);
    }

    public function testHousesArmcMorinusCuspsMatchSwissEphemeris(): void
    {
        $result = Houses::housesArmc(310.4570724, 59.0, 23.4376767, Houses::HSYS_MORINUS);
        $cusps = $result['cusps'];

        self::assertEqualsWithDelta(38.040521666667, $cusps[1], 1e-7);
        self::assertEqualsWithDelta(68.849424194444, $cusps[2], 1e-7);
        self::assertEqualsWithDelta(101.373899611111, $cusps[3], 1e-7);
        self::assertEqualsWithDelta(132.906647861111, $cusps[4], 1e-7);
        self::assertEqualsWithDelta(161.960853805556, $cusps[5], 1e-7);
        self::assertEqualsWithDelta(189.611087805556, $cusps[6], 1e-7);
        self::assertEqualsWithDelta(218.040521666667, $cusps[7], 1e-7);
        self::assertEqualsWithDelta(248.849424194444, $cusps[8], 1e-7);
        self::assertEqualsWithDelta(281.373899611111, $cusps[9], 1e-7);
        self::assertEqualsWithDelta(312.906647861111, $cusps[10], 1e-7);
        self::assertEqualsWithDelta(341.960853805556, $cusps[11], 1e-7);
        self::assertEqualsWithDelta(9.611087805556, $cusps[12], 1e-7);
    }

    public function testHousesArmcRegiomontanusCuspsMatchSwissEphemeris(): void
    {
        $result = Houses::housesArmc(310.4570724, 59.0, 23.4376767, Houses::HSYS_REGIOMONTANUS);
        $cusps = $result['cusps'];

        $delta = 2e-7;

        self::assertEqualsWithDelta(86.811765333333, $cusps[1], $delta);
        self::assertEqualsWithDelta(105.783055444444, $cusps[2], $delta);
        self::assertEqualsWithDelta(116.835358694444, $cusps[3], $delta);
        self::assertEqualsWithDelta(128.040521666667, $cusps[4], $delta);
        self::assertEqualsWithDelta(147.919017944444, $cusps[5], $delta);
        self::assertEqualsWithDelta(208.886143111111, $cusps[6], $delta);
        self::assertEqualsWithDelta(266.811765333333, $cusps[7], $delta);
        self::assertEqualsWithDelta(285.783055444444, $cusps[8], $delta);
        self::assertEqualsWithDelta(296.835358694444, $cusps[9], $delta);
        self::assertEqualsWithDelta(308.040521666667, $cusps[10], $delta);
        self::assertEqualsWithDelta(327.919017944444, $cusps[11], $delta);
        self::assertEqualsWithDelta(28.886143111111, $cusps[12], $delta);
    }

    public function testHousesArmcSripatiCuspsMatchSwissEphemeris(): void
    {
        $result = Houses::housesArmc(310.4570724, 59.0, 23.4376767, Houses::HSYS_SRIPATI);
        $cusps = $result['cusps'];
        $delta = 2e-7;

        self::assertEqualsWithDelta(63.683224644586, $cusps[1], $delta);
        self::assertEqualsWithDelta(93.683224644586, $cusps[2], $delta);
        self::assertEqualsWithDelta(107.426143439552, $cusps[3], $delta);
        self::assertEqualsWithDelta(121.169062234518, $cusps[4], $delta);
        self::assertEqualsWithDelta(151.169062234518, $cusps[5], $delta);
        self::assertEqualsWithDelta(197.426143439552, $cusps[6], $delta);
        self::assertEqualsWithDelta(243.683224644586, $cusps[7], $delta);
        self::assertEqualsWithDelta(273.683224644586, $cusps[8], $delta);
        self::assertEqualsWithDelta(287.426143439552, $cusps[9], $delta);
        self::assertEqualsWithDelta(301.169062234518, $cusps[10], $delta);
        self::assertEqualsWithDelta(331.169062234518, $cusps[11], $delta);
        self::assertEqualsWithDelta(17.426143439552, $cusps[12], $delta);
    }

    public function testHousesArmcTopocentricCuspsMatchSwissEphemeris(): void
    {
        $result = Houses::housesArmc(310.4570724, 59.0, 23.4376767, Houses::HSYS_TOPOCENTRIC);
        $cusps = $result['cusps'];
        $delta = 2e-7;

        self::assertEqualsWithDelta(86.811765333333, $cusps[1], $delta);
        self::assertEqualsWithDelta(98.116525638889, $cusps[2], $delta);
        self::assertEqualsWithDelta(111.490531472222, $cusps[3], $delta);
        self::assertEqualsWithDelta(128.040521666667, $cusps[4], $delta);
        self::assertEqualsWithDelta(152.550586000000, $cusps[5], $delta);
        self::assertEqualsWithDelta(201.492354777778, $cusps[6], $delta);
        self::assertEqualsWithDelta(266.811765333333, $cusps[7], $delta);
        self::assertEqualsWithDelta(278.116525638889, $cusps[8], $delta);
        self::assertEqualsWithDelta(291.490531472222, $cusps[9], $delta);
        self::assertEqualsWithDelta(308.040521666667, $cusps[10], $delta);
        self::assertEqualsWithDelta(332.550586000000, $cusps[11], $delta);
        self::assertEqualsWithDelta(21.492354777778, $cusps[12], $delta);
    }

    public function testHousesArmcCampanusCuspsMatchSwissEphemeris(): void
    {
        $result = Houses::housesArmc(310.4570724, 59.0, 23.4376767, Houses::HSYS_CAMPANUS);
        $cusps = $result['cusps'];
        $delta = 2e-7;

        self::assertEqualsWithDelta(86.811765333333, $cusps[1], $delta);
        self::assertEqualsWithDelta(112.797232833333, $cusps[2], $delta);
        self::assertEqualsWithDelta(121.503451194444, $cusps[3], $delta);
        self::assertEqualsWithDelta(128.040521666667, $cusps[4], $delta);
        self::assertEqualsWithDelta(136.860940777778, $cusps[5], $delta);
        self::assertEqualsWithDelta(163.823908833333, $cusps[6], $delta);
        self::assertEqualsWithDelta(266.811765333333, $cusps[7], $delta);
        self::assertEqualsWithDelta(292.797232833333, $cusps[8], $delta);
        self::assertEqualsWithDelta(301.503451194444, $cusps[9], $delta);
        self::assertEqualsWithDelta(308.040521666667, $cusps[10], $delta);
        self::assertEqualsWithDelta(316.860940777778, $cusps[11], $delta);
        self::assertEqualsWithDelta(343.823908833333, $cusps[12], $delta);
    }

    public function testHousesArmcAlcabitiusCuspsMatchSwissEphemeris(): void
    {
        $result = Houses::housesArmc(310.4570724, 59.0, 23.4376767, Houses::HSYS_ALCABITIUS);
        $cusps = $result['cusps'];
        $delta = 2e-7;

        self::assertEqualsWithDelta(86.811765333333, $cusps[1], $delta);
        self::assertEqualsWithDelta(100.268408388889, $cusps[2], $delta);
        self::assertEqualsWithDelta(113.931466611111, $cusps[3], $delta);
        self::assertEqualsWithDelta(128.040521666667, $cusps[4], $delta);
        self::assertEqualsWithDelta(175.438321472222, $cusps[5], $delta);
        self::assertEqualsWithDelta(223.625446166667, $cusps[6], $delta);
        self::assertEqualsWithDelta(266.811765333333, $cusps[7], $delta);
        self::assertEqualsWithDelta(280.268408388889, $cusps[8], $delta);
        self::assertEqualsWithDelta(293.931466611111, $cusps[9], $delta);
        self::assertEqualsWithDelta(308.040521666667, $cusps[10], $delta);
        self::assertEqualsWithDelta(355.438321472222, $cusps[11], $delta);
        self::assertEqualsWithDelta(43.625446166667, $cusps[12], $delta);
    }

    public function testHousesArmcKochCuspsMatchSwissEphemeris(): void
    {
        $result = Houses::housesArmc(310.4570724, 59.0, 23.4376767, Houses::HSYS_KOCH);
        $cusps = $result['cusps'];
        $delta = 2e-7;

        self::assertEqualsWithDelta(86.811765333333, $cusps[1], $delta);
        self::assertEqualsWithDelta(102.728900500000, $cusps[2], $delta);
        self::assertEqualsWithDelta(115.893137694444, $cusps[3], $delta);
        self::assertEqualsWithDelta(128.040521666667, $cusps[4], $delta);
        self::assertEqualsWithDelta(190.314827500000, $cusps[5], $delta);
        self::assertEqualsWithDelta(242.489001000000, $cusps[6], $delta);
        self::assertEqualsWithDelta(266.811765333333, $cusps[7], $delta);
        self::assertEqualsWithDelta(282.728900500000, $cusps[8], $delta);
        self::assertEqualsWithDelta(295.893137694444, $cusps[9], $delta);
        self::assertEqualsWithDelta(308.040521666667, $cusps[10], $delta);
        self::assertEqualsWithDelta(10.314827500000, $cusps[11], $delta);
        self::assertEqualsWithDelta(62.489001000000, $cusps[12], $delta);
    }

    public function testHousesArmcPullenSdCuspsMatchSwissEphemeris(): void
    {
        $result = Houses::housesArmc(310.4570724, 59.0, 23.4376767, Houses::HSYS_PULLEN_SD);
        $cusps = $result['cusps'];
        $delta = 2e-7;

        self::assertEqualsWithDelta(86.811765333333, $cusps[1], $delta);
        self::assertEqualsWithDelta(104.618954416667, $cusps[2], $delta);
        self::assertEqualsWithDelta(110.233332583333, $cusps[3], $delta);
        self::assertEqualsWithDelta(128.040521666667, $cusps[4], $delta);
        self::assertEqualsWithDelta(170.233332583333, $cusps[5], $delta);
        self::assertEqualsWithDelta(224.618954416667, $cusps[6], $delta);
        self::assertEqualsWithDelta(266.811765333333, $cusps[7], $delta);
        self::assertEqualsWithDelta(284.618954416667, $cusps[8], $delta);
        self::assertEqualsWithDelta(290.233332583333, $cusps[9], $delta);
        self::assertEqualsWithDelta(308.040521666667, $cusps[10], $delta);
        self::assertEqualsWithDelta(350.233332583333, $cusps[11], $delta);
        self::assertEqualsWithDelta(44.618954416667, $cusps[12], $delta);
    }

    public function testHousesArmcPullenSrCuspsMatchSwissEphemeris(): void
    {
        $result = Houses::housesArmc(310.4570724, 59.0, 23.4376767, Houses::HSYS_PULLEN_SR);
        $cusps = $result['cusps'];
        $delta = 2e-7;

        self::assertEqualsWithDelta(86.811765333333, $cusps[1], $delta);
        self::assertEqualsWithDelta(102.458919361111, $cusps[2], $delta);
        self::assertEqualsWithDelta(112.393367638889, $cusps[3], $delta);
        self::assertEqualsWithDelta(128.040521666667, $cusps[4], $delta);
        self::assertEqualsWithDelta(166.857217777778, $cusps[5], $delta);
        self::assertEqualsWithDelta(227.995069222222, $cusps[6], $delta);
        self::assertEqualsWithDelta(266.811765333333, $cusps[7], $delta);
        self::assertEqualsWithDelta(282.458919341111, $cusps[8], $delta);
        self::assertEqualsWithDelta(292.393367638889, $cusps[9], $delta);
        self::assertEqualsWithDelta(308.040521666667, $cusps[10], $delta);
        self::assertEqualsWithDelta(346.857217777778, $cusps[11], $delta);
        self::assertEqualsWithDelta(47.995069222222, $cusps[12], $delta);
    }

    public function testHousesArmcCarterCuspsMatchSwissEphemeris(): void
    {
        $result = Houses::housesArmc(310.4570724, 59.0, 23.4376767, Houses::HSYS_CARTER);
        $cusps = $result['cusps'];
        $delta = 2e-7;

        self::assertEqualsWithDelta(86.811765333333, $cusps[1], $delta);
        self::assertEqualsWithDelta(114.605879750000, $cusps[2], $delta);
        self::assertEqualsWithDelta(144.219605722222, $cusps[3], $delta);
        self::assertEqualsWithDelta(176.214176166667, $cusps[4], $delta);
        self::assertEqualsWithDelta(208.547451333333, $cusps[5], $delta);
        self::assertEqualsWithDelta(238.755517944444, $cusps[6], $delta);
        self::assertEqualsWithDelta(266.811765333333, $cusps[7], $delta);
        self::assertEqualsWithDelta(294.605879750000, $cusps[8], $delta);
        self::assertEqualsWithDelta(324.219605722222, $cusps[9], $delta);
        self::assertEqualsWithDelta(356.214176166667, $cusps[10], $delta);
        self::assertEqualsWithDelta(28.547451333333, $cusps[11], $delta);
        self::assertEqualsWithDelta(58.755517944444, $cusps[12], $delta);
    }

    public function testHousesArmcMeridianCuspsMatchSwissEphemeris(): void
    {
        $result = Houses::housesArmc(310.4570724, 59.0, 23.4376767, Houses::HSYS_MERIDIAN);
        $cusps = $result['cusps'];
        $delta = 2e-7;

        self::assertEqualsWithDelta(42.906647861111, $cusps[1], $delta);
        self::assertEqualsWithDelta(71.960853805556, $cusps[2], $delta);
        self::assertEqualsWithDelta(99.611087805556, $cusps[3], $delta);
        self::assertEqualsWithDelta(128.040521666667, $cusps[4], $delta);
        self::assertEqualsWithDelta(158.849424194444, $cusps[5], $delta);
        self::assertEqualsWithDelta(191.373899611111, $cusps[6], $delta);
        self::assertEqualsWithDelta(222.906647861111, $cusps[7], $delta);
        self::assertEqualsWithDelta(251.960853805556, $cusps[8], $delta);
        self::assertEqualsWithDelta(279.611087805556, $cusps[9], $delta);
        self::assertEqualsWithDelta(308.040521666667, $cusps[10], $delta);
        self::assertEqualsWithDelta(338.849424194444, $cusps[11], $delta);
        self::assertEqualsWithDelta(11.373899611111, $cusps[12], $delta);
    }

    public function testHousesArmcSavardCuspsMatchSwissEphemeris(): void
    {
        $result = Houses::housesArmc(310.4570724, 59.0, 23.4376767, Houses::HSYS_SAVARD_A);
        $cusps = $result['cusps'];
        $delta = 2e-7;

        self::assertEqualsWithDelta(86.811765333333, $cusps[1], $delta);
        self::assertEqualsWithDelta(109.667209888889, $cusps[2], $delta);
        self::assertEqualsWithDelta(118.502562388889, $cusps[3], $delta);
        self::assertEqualsWithDelta(128.040521666667, $cusps[4], $delta);
        self::assertEqualsWithDelta(143.285320111111, $cusps[5], $delta);
        self::assertEqualsWithDelta(181.966316666667, $cusps[6], $delta);
        self::assertEqualsWithDelta(266.811765333333, $cusps[7], $delta);
        self::assertEqualsWithDelta(289.667209888889, $cusps[8], $delta);
        self::assertEqualsWithDelta(298.502562388889, $cusps[9], $delta);
        self::assertEqualsWithDelta(308.040521666667, $cusps[10], $delta);
        self::assertEqualsWithDelta(323.285320111111, $cusps[11], $delta);
        self::assertEqualsWithDelta(1.966316666667, $cusps[12], $delta);
    }

    public function testHousesArmcKrusinskiCuspsMatchSwissEphemeris(): void
    {
        $result = Houses::housesArmc(310.4570724, 59.0, 23.4376767, Houses::HSYS_KRUSINSKI);
        $cusps = $result['cusps'];
        $delta = 2e-7;

        self::assertEqualsWithDelta(86.811765333333, $cusps[1], $delta);
        self::assertEqualsWithDelta(96.294398888889, $cusps[2], $delta);
        self::assertEqualsWithDelta(106.752177083333, $cusps[3], $delta);
        self::assertEqualsWithDelta(128.040521666667, $cusps[4], $delta);
        self::assertEqualsWithDelta(202.178527666667, $cusps[5], $delta);
        self::assertEqualsWithDelta(251.705842111111, $cusps[6], $delta);
        self::assertEqualsWithDelta(266.811765333333, $cusps[7], $delta);
        self::assertEqualsWithDelta(276.294398888889, $cusps[8], $delta);
        self::assertEqualsWithDelta(286.752177083333, $cusps[9], $delta);
        self::assertEqualsWithDelta(308.040521666667, $cusps[10], $delta);
        self::assertEqualsWithDelta(22.178527666667, $cusps[11], $delta);
        self::assertEqualsWithDelta(71.705842111111, $cusps[12], $delta);
    }

    public function testHousesArmcPlacidusCuspsMatchSwissEphemeris(): void
    {
        $result = Houses::housesArmc(310.4570724, 59.0, 23.4376767, Houses::HSYS_PLACIDUS);
        $cusps = $result['cusps'];
        $delta = 2e-7;

        self::assertEqualsWithDelta(86.811765333333, $cusps[1], $delta);
        self::assertEqualsWithDelta(99.717949055556, $cusps[2], $delta);
        self::assertEqualsWithDelta(112.387588055556, $cusps[3], $delta);
        self::assertEqualsWithDelta(128.040521666667, $cusps[4], $delta);
        self::assertEqualsWithDelta(152.429186944444, $cusps[5], $delta);
        self::assertEqualsWithDelta(201.600786416667, $cusps[6], $delta);
        self::assertEqualsWithDelta(266.811765333333, $cusps[7], $delta);
        self::assertEqualsWithDelta(279.717949055556, $cusps[8], $delta);
        self::assertEqualsWithDelta(292.387588055556, $cusps[9], $delta);
        self::assertEqualsWithDelta(308.040521666667, $cusps[10], $delta);
        self::assertEqualsWithDelta(332.429186944444, $cusps[11], $delta);
        self::assertEqualsWithDelta(21.600786416667, $cusps[12], $delta);
    }

    public function testHouseArmcUnknownSystemDefaultsToPlacidus(): void
    {
        self::assertSame(
            Houses::housesArmc(310.4570724, 59.0, 23.4376767, Houses::HSYS_PLACIDUS)['cusps'],
            Houses::housesArmc(310.4570724, 59.0, 23.4376767, '?')['cusps'],
        );
    }

    public function testHousesArmcHorizonCuspsMatchSwissEphemeris(): void
    {
        $result = Houses::housesArmc(310.4570724, 59.0, 23.4376767, Houses::HSYS_HORIZON);
        $cusps = $result['cusps'];
        $delta = 2e-7;

        self::assertEqualsWithDelta(34.699781472222, $cusps[1], $delta);
        self::assertEqualsWithDelta(65.221259416667, $cusps[2], $delta);
        self::assertEqualsWithDelta(96.933535000000, $cusps[3], $delta);
        self::assertEqualsWithDelta(128.040521666667, $cusps[4], $delta);
        self::assertEqualsWithDelta(157.450271777778, $cusps[5], $delta);
        self::assertEqualsWithDelta(185.819281888889, $cusps[6], $delta);
        self::assertEqualsWithDelta(214.699781472222, $cusps[7], $delta);
        self::assertEqualsWithDelta(245.221259416667, $cusps[8], $delta);
        self::assertEqualsWithDelta(276.933535000000, $cusps[9], $delta);
        self::assertEqualsWithDelta(308.040521666667, $cusps[10], $delta);
        self::assertEqualsWithDelta(337.450271777778, $cusps[11], $delta);
        self::assertEqualsWithDelta(5.819281888889, $cusps[12], $delta);
    }

    public function testHousesArmcGauquelinSectorsMatchSwissEphemeris(): void
    {
        $result = Houses::housesArmc(310.4570724, 59.0, 23.4376767, Houses::HSYS_GAUQUELIN);
        $cusps = $result['cusps'];
        $delta = 2e-7;

        $expected = [
            1 => 86.811765333333,
            2 => 69.352994138889,
            3 => 46.188136527778,
            4 => 21.600786416667,
            5 => 0.831428027778,
            6 => 344.780363500000,
            7 => 332.429186944444,
            8 => 322.679612583333,
            9 => 314.730388777778,
            10 => 308.040521666667,
            11 => 302.244509666667,
            12 => 297.088288611111,
            13 => 292.387588055556,
            14 => 288.001204861111,
            15 => 283.812955500000,
            16 => 279.717949055556,
            17 => 275.609696611111,
            18 => 271.363967916667,
            19 => 266.811765333333,
            20 => 249.352994138889,
            21 => 226.188136527778,
            22 => 201.600786416667,
            23 => 180.831428027778,
            24 => 164.780363500000,
            25 => 152.429186944444,
            26 => 142.679612583333,
            27 => 134.730388777778,
            28 => 128.040521666667,
            29 => 122.244509666667,
            30 => 117.088288611111,
            31 => 112.387588055556,
            32 => 108.001204861111,
            33 => 103.812955500000,
            34 => 99.717949055556,
            35 => 95.609696611111,
            36 => 91.363967916667,
        ];

        self::assertCount(36, $cusps);

        foreach ($expected as $house => $longitude) {
            self::assertEqualsWithDelta($longitude, $cusps[$house], $delta, 'sector ' . $house);
        }
    }

    public function testHousesArmcApcCuspsMatchSwissEphemeris(): void
    {
        $result = Houses::housesArmc(310.4570724, 59.0, 23.4376767, Houses::HSYS_APC);
        $cusps = $result['cusps'];
        $delta = 2e-7;

        self::assertEqualsWithDelta(86.811765333333, $cusps[1], $delta);
        self::assertEqualsWithDelta(99.543621250000, $cusps[2], $delta);
        self::assertEqualsWithDelta(111.410390222222, $cusps[3], $delta);
        self::assertEqualsWithDelta(128.040521666667, $cusps[4], $delta);
        self::assertEqualsWithDelta(171.217975611111, $cusps[5], $delta);
        self::assertEqualsWithDelta(241.802536527778, $cusps[6], $delta);
        self::assertEqualsWithDelta(266.811765333333, $cusps[7], $delta);
        self::assertEqualsWithDelta(287.974410083333, $cusps[8], $delta);
        self::assertEqualsWithDelta(298.007140777778, $cusps[9], $delta);
        self::assertEqualsWithDelta(308.040521666667, $cusps[10], $delta);
        self::assertEqualsWithDelta(324.569172000000, $cusps[11], $delta);
        self::assertEqualsWithDelta(13.557176250000, $cusps[12], $delta);
    }

    public function testHousesArmcSunshineTreindlCuspsMatchSwissEphemeris(): void
    {
        $sunDeclination = -23.032430333333;
        $result = Houses::housesArmc(310.4570724, 59.0, 23.4376767, 'I', $sunDeclination);
        $cusps = $result['cusps'];
        $delta = 2e-7;

        self::assertEqualsWithDelta(86.811765333333, $cusps[1], $delta);
        self::assertEqualsWithDelta(107.964365722222, $cusps[2], $delta);
        self::assertEqualsWithDelta(118.007746527778, $cusps[3], $delta);
        self::assertEqualsWithDelta(128.040521666667, $cusps[4], $delta);
        self::assertEqualsWithDelta(144.567556777778, $cusps[5], $delta);
        self::assertEqualsWithDelta(193.627851416667, $cusps[6], $delta);
        self::assertEqualsWithDelta(266.811765333333, $cusps[7], $delta);
        self::assertEqualsWithDelta(279.744468222222, $cusps[8], $delta);
        self::assertEqualsWithDelta(291.628579444444, $cusps[9], $delta);
        self::assertEqualsWithDelta(308.040521666667, $cusps[10], $delta);
        self::assertEqualsWithDelta(349.983791527778, $cusps[11], $delta);
        self::assertEqualsWithDelta(61.054429694444, $cusps[12], $delta);
    }

    public function testHousesArmcSunshineMakranskyCuspsMatchSwissEphemeris(): void
    {
        $sunDeclination = -23.032430333333;
        $result = Houses::housesArmc(310.4570724, 59.0, 23.4376767, 'i', $sunDeclination);
        $cusps = $result['cusps'];
        $delta = 2e-7;

        self::assertEqualsWithDelta(86.811765333333, $cusps[1], $delta);
        self::assertEqualsWithDelta(40.000179611111, $cusps[2], $delta);
        self::assertEqualsWithDelta(118.007746527778, $cusps[3], $delta);
        self::assertEqualsWithDelta(128.040521666667, $cusps[4], $delta);
        self::assertEqualsWithDelta(144.567556777778, $cusps[5], $delta);
        self::assertEqualsWithDelta(236.475319416667, $cusps[6], $delta);
        self::assertEqualsWithDelta(266.811765333333, $cusps[7], $delta);
        self::assertEqualsWithDelta(279.744468222222, $cusps[8], $delta);
        self::assertEqualsWithDelta(291.628579444444, $cusps[9], $delta);
        self::assertEqualsWithDelta(308.040521666667, $cusps[10], $delta);
        self::assertEqualsWithDelta(349.983791527778, $cusps[11], $delta);
        self::assertEqualsWithDelta(61.054429694444, $cusps[12], $delta);
    }

    public function testSunshineRequiresSunDeclination(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Houses::housesArmc(310.4570724, 59.0, 23.4376767, 'I');
    }

    public function testHousePositionEqualMatchesSwissEphemeris(): void
    {
        $hpos = Houses::housePosition(310.4570724, 59.0, 23.4376767, Houses::HSYS_EQUAL, [280.3689187, 0.0]);

        self::assertEqualsWithDelta(7.451905122222223, $hpos, 1e-8);
    }

    public function testHousePositionEqualMcMatchesSwissEphemeris(): void
    {
        $hpos = Houses::housePosition(310.4570724, 59.0, 23.4376767, Houses::HSYS_EQUAL_MC, [280.3689187, 0.0]);

        self::assertEqualsWithDelta(9.077613243518519, $hpos, 1e-8);
    }

    public function testHousePositionVehlowMatchesSwissEphemeris(): void
    {
        $hpos = Houses::housePosition(310.4570724, 59.0, 23.4376767, 'V', [280.3689187, 0.0]);

        self::assertEqualsWithDelta(7.951905122222223, $hpos, 1e-8);
    }

    public function testHousePositionWholeSignMatchesSwissEphemeris(): void
    {
        $hpos = Houses::housePosition(310.4570724, 59.0, 23.4376767, Houses::HSYS_WHOLE_SIGN, [280.3689187, 0.0]);

        self::assertEqualsWithDelta(8.345630632407406, $hpos, 1e-8);
    }

    public function testHousePositionEqualAriesMatchesSwissEphemeris(): void
    {
        $hpos = Houses::housePosition(310.4570724, 59.0, 23.4376767, Houses::HSYS_EQUAL_ARIES, [280.3689187, 0.0]);

        self::assertEqualsWithDelta(10.345630623333, $hpos, 1e-8);
    }

    public function testHousePositionPorphyryMatchesSwissEphemeris(): void
    {
        $hpos = Houses::housePosition(310.4570724, 59.0, 23.4376767, Houses::HSYS_PORPHYRY, [280.3689187, 0.0]);

        self::assertEqualsWithDelta(7.986482848148148, $hpos, 1e-8);
    }

    public function testHousePositionSripatiMatchesSwissEphemeris(): void
    {
        $hpos = Houses::housePosition(310.4570724, 59.0, 23.4376767, Houses::HSYS_SRIPATI, [280.3689187, 0.0]);

        self::assertEqualsWithDelta(8.486482848148148, $hpos, 1e-8);
    }

    public function testHousePositionAlcabitiusMatchesSwissEphemeris(): void
    {
        $hpos = Houses::housePosition(
            310.4570724,
            59.0,
            23.4376767,
            Houses::HSYS_ALCABITIUS,
            [280.3689187, 0.8187 / 3600.0]
        );

        self::assertEqualsWithDelta(8.007434758333332, $hpos, 1e-8);
    }

    public function testHousePositionMeridianMatchesSwissEphemeris(): void
    {
        $hpos = Houses::housePosition(
            310.4570724,
            59.0,
            23.4376767,
            Houses::HSYS_MERIDIAN,
            [280.3689187, 0.8187 / 3600.0]
        );

        self::assertEqualsWithDelta(9.027377105555555, $hpos, 1e-8);
    }

    public function testHousePositionCarterMatchesSwissEphemeris(): void
    {
        $hpos = Houses::housePosition(
            310.4570724,
            59.0,
            23.4376767,
            Houses::HSYS_CARTER,
            [280.3689187, 0.8187 / 3600.0]
        );

        self::assertEqualsWithDelta(7.491755095370370, $hpos, 1e-8);
    }

    public function testHousePositionMorinusMatchesSwissEphemeris(): void
    {
        $hpos = Houses::housePosition(
            310.4570724,
            59.0,
            23.4376767,
            Houses::HSYS_MORINUS,
            [280.3689187, 0.8187 / 3600.0]
        );

        self::assertEqualsWithDelta(8.969090490740740, $hpos, 1e-8);
    }

    public function testHousePositionCampanusMatchesSwissEphemeris(): void
    {
        $hpos = Houses::housePosition(
            310.4570724,
            59.0,
            23.4376767,
            Houses::HSYS_CAMPANUS,
            [280.3689187, 0.8187 / 3600.0]
        );

        self::assertEqualsWithDelta(7.330663045370370, $hpos, 1e-8);
    }

    public function testHousePositionHorizonMatchesSwissEphemeris(): void
    {
        $hpos = Houses::housePosition(
            310.4570724,
            59.0,
            23.4376767,
            Houses::HSYS_HORIZON,
            [280.3689187, 0.8187 / 3600.0]
        );

        self::assertEqualsWithDelta(9.108419875000001, $hpos, 1e-8);
    }

    public function testHousePositionRegiomontanusMatchesSwissEphemeris(): void
    {
        $hpos = Houses::housePosition(
            310.4570724,
            59.0,
            23.4376767,
            Houses::HSYS_REGIOMONTANUS,
            [280.3689187, 0.8187 / 3600.0]
        );

        self::assertEqualsWithDelta(7.625178997222222, $hpos, 1e-8);
    }

    public function testHousePositionSavardMatchesSwissEphemeris(): void
    {
        $hpos = Houses::housePosition(
            310.4570724,
            59.0,
            23.4376767,
            Houses::HSYS_SAVARD_A,
            [280.3689187, 0.8187 / 3600.0]
        );

        self::assertEqualsWithDelta(7.429096466666667, $hpos, 1e-8);
    }

    public function testHousePositionKrusinskiMatchesSwissEphemeris(): void
    {
        $hpos = Houses::housePosition(
            310.4570724,
            59.0,
            23.4376767,
            Houses::HSYS_KRUSINSKI,
            [280.3689187, 0.8187 / 3600.0]
        );

        self::assertEqualsWithDelta(8.431504687037037, $hpos, 1e-8);
    }

    public function testHousePositionPlacidusMatchesSwissEphemeris(): void
    {
        $hpos = Houses::housePosition(
            310.4570724,
            59.0,
            23.4376767,
            Houses::HSYS_PLACIDUS,
            [280.3689187, 0.8187 / 3600.0]
        );

        self::assertEqualsWithDelta(8.053176402777778, $hpos, 1e-8);
    }

    public function testHousePositionGauquelinMatchesSwissEphemeris(): void
    {
        $hpos = Houses::housePosition(
            310.4570724,
            59.0,
            23.4376767,
            Houses::HSYS_GAUQUELIN,
            [280.3689187, 0.8187 / 3600.0]
        );

        self::assertEqualsWithDelta(15.840470791666666, $hpos, 2e-8);
    }

    public function testHousePositionKochMatchesSwissEphemeris(): void
    {
        $hpos = Houses::housePosition(
            310.4570724,
            59.0,
            23.4376767,
            Houses::HSYS_KOCH,
            [280.3689187, 0.8187 / 3600.0]
        );

        self::assertEqualsWithDelta(7.835135765740741, $hpos, 1e-8);
    }

    public function testHousePositionTopocentricMatchesSwissEphemeris(): void
    {
        $hpos = Houses::housePosition(
            310.4570724,
            59.0,
            23.4376767,
            Houses::HSYS_TOPOCENTRIC,
            [280.3689187, 0.8187 / 3600.0]
        );

        self::assertEqualsWithDelta(8.180084526851854, $hpos, 1e-8);
    }

    public function testHousePositionPullenSdMatchesSwissEphemeris(): void
    {
        $hpos = Houses::housePosition(
            310.4570724,
            59.0,
            23.4376767,
            Houses::HSYS_PULLEN_SD,
            [280.3689187, 0.8187 / 3600.0]
        );

        self::assertEqualsWithDelta(7.761330343518519, $hpos, 1e-8);
    }

    public function testHousePositionPullenSrMatchesSwissEphemeris(): void
    {
        $hpos = Houses::housePosition(
            310.4570724,
            59.0,
            23.4376767,
            Houses::HSYS_PULLEN_SR,
            [280.3689187, 0.8187 / 3600.0]
        );

        self::assertEqualsWithDelta(7.866429342592593, $hpos, 1e-8);
    }

    public function testHousePositionApcMatchesSwissEphemeris(): void
    {
        $hpos = Houses::housePosition(
            310.4570724,
            59.0,
            23.4376767,
            Houses::HSYS_APC,
            [280.3689187, 0.8187 / 3600.0]
        );

        self::assertEqualsWithDelta(7.481286534259259, $hpos, 1e-8);
    }

    public function testHousePositionSunshineMatchesSwissEphemeris(): void
    {
        $hpos = Houses::housePosition(
            310.4570724,
            59.0,
            23.4376767,
            'I',
            [280.3689187, 0.8187 / 3600.0],
            -23.032430333333
        );

        self::assertEqualsWithDelta(8.053176402777777, $hpos, 1e-8);
    }

    public function testHousePositionSunshineAlternativeMatchesSwissEphemeris(): void
    {
        $hpos = Houses::housePosition(
            310.4570724,
            59.0,
            23.4376767,
            'i',
            [280.3689187, 0.8187 / 3600.0],
            -23.032430333333
        );

        self::assertEqualsWithDelta(8.053176402777777, $hpos, 1e-8);
    }

    public function testHousePositionSunshineRequiresSunDeclination(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Houses::housePosition(
            310.4570724,
            59.0,
            23.4376767,
            'I',
            [280.3689187, 0.8187 / 3600.0]
        );
    }

    public function testSiderealHousesPlacidusMatchSwissEphemerisLahiriFixture(): void
    {
        $result = Houses::siderealHouses(
            2451545.0,
            59.0,
            30.0,
            Houses::HSYS_PLACIDUS,
            Catalog::SE_SIDM_LAHIRI
        );

        $cusps = $result['cusps'];
        $ascmc = $result['ascmc'];

        self::assertEqualsWithDelta(62.958542833333, $cusps[1], 5e-5);
        self::assertEqualsWithDelta(75.864726555556, $cusps[2], 5e-5);
        self::assertEqualsWithDelta(88.534365555556, $cusps[3], 5e-5);
        self::assertEqualsWithDelta(104.187299166667, $cusps[4], 5e-5);
        self::assertEqualsWithDelta(128.575964472222, $cusps[5], 5e-5);
        self::assertEqualsWithDelta(177.747563916667, $cusps[6], 5e-5);
        self::assertEqualsWithDelta(242.958542833333, $cusps[7], 5e-5);
        self::assertEqualsWithDelta(255.864726555556, $cusps[8], 5e-5);
        self::assertEqualsWithDelta(268.534365555556, $cusps[9], 5e-5);
        self::assertEqualsWithDelta(284.187299166667, $cusps[10], 5e-5);
        self::assertEqualsWithDelta(308.575964472222, $cusps[11], 5e-5);
        self::assertEqualsWithDelta(357.747563916667, $cusps[12], 5e-5);

        self::assertEqualsWithDelta(62.958542833333, $ascmc[0], 5e-5);
        self::assertEqualsWithDelta(284.187299166667, $ascmc[1], 5e-5);
        self::assertEqualsWithDelta(310.457072444444, $ascmc[2], 1e-4);
    }

    public function testApplyAyanamsaToHousesLeavesArmcTropical(): void
    {
        $houses = Houses::housesArmc(310.4570724, 59.0, 23.4376767, Houses::HSYS_EQUAL);
        $sidereal = Houses::applyAyanamsaToHouses($houses, 23.85322475, Houses::HSYS_EQUAL);

        self::assertEqualsWithDelta(62.958540497103, $sidereal['cusps'][1], 1e-12);
        self::assertEqualsWithDelta(310.4570724, $sidereal['ascmc'][2], 1e-12);
    }

    public function testSiderealWholeSignCuspsAreRoundedToSigns(): void
    {
        $houses = Houses::housesArmc(310.4570724, 59.0, 23.4376767, Houses::HSYS_EQUAL);
        $sidereal = Houses::applyAyanamsaToHouses($houses, 23.85322475, Houses::HSYS_WHOLE_SIGN);

        self::assertSame(60.0, $sidereal['cusps'][1]);
        self::assertSame(90.0, $sidereal['cusps'][2]);
    }

    public function testCustomSiderealHousesUseCustomAyanamsa(): void
    {
        $result = Houses::customSiderealHouses(
            2451545.0,
            59.0,
            30.0,
            Houses::HSYS_PLACIDUS,
            2451545.0,
            30.0,
            false
        );

        $tropical = Houses::houses(2451545.0, 59.0, 30.0, Houses::HSYS_PLACIDUS);

        $ayanamsa = Ayanamsa::customAyanamsaUt(2451545.0, 2451545.0, 30.0, false);

        self::assertEqualsWithDelta(
            fmod($tropical['cusps'][1] - $ayanamsa + 360.0, 360.0),
            $result['cusps'][1],
            1e-12
        );

        self::assertEqualsWithDelta($tropical['ascmc'][2], $result['ascmc'][2], 1e-12);
    }

    public function testCustomSiderealHousesArmcDelegatesToSiderealHousesArmc(): void
    {
        $custom = Houses::customSiderealHousesArmc(
            310.4570724,
            59.0,
            23.4376767,
            Houses::HSYS_EQUAL,
            30.0
        );

        $direct = Houses::siderealHousesArmc(
            310.4570724,
            59.0,
            23.4376767,
            Houses::HSYS_EQUAL,
            30.0
        );

        self::assertSame($direct, $custom);
    }

    public function testCustomSiderealWholeSignHousesAreRoundedToSigns(): void
    {
        $result = Houses::customSiderealHouses(
            2451545.0,
            59.0,
            30.0,
            Houses::HSYS_WHOLE_SIGN,
            2451545.0,
            30.0,
            false
        );

        self::assertSame(30.0, $result['cusps'][1]);
        self::assertSame(60.0, $result['cusps'][2]);
        self::assertSame(90.0, $result['cusps'][3]);
    }

    public function testRemoveAyanamsaFromHousesRestoresTropicalCusps(): void
    {
        $tropical = Houses::housesArmc(310.4570724, 59.0, 23.4376767, Houses::HSYS_PLACIDUS);
        $sidereal = Houses::applyAyanamsaToHouses($tropical, 23.85322475, Houses::HSYS_PLACIDUS);
        $restored = Houses::removeAyanamsaFromHouses($sidereal, 23.85322475);

        for ($i = 1; $i <= 12; $i++) {
            self::assertEqualsWithDelta($tropical['cusps'][$i], $restored['cusps'][$i], 1e-12);
        }

        for ($i = 0; $i <= 9; $i++) {
            self::assertEqualsWithDelta($tropical['ascmc'][$i], $restored['ascmc'][$i], 1e-12);
        }
    }

    public function testRemoveAyanamsaFromHousesLeavesArmcUnchanged(): void
    {
        $tropical = Houses::housesArmc(310.4570724, 59.0, 23.4376767, Houses::HSYS_EQUAL);
        $sidereal = Houses::applyAyanamsaToHouses($tropical, 23.85322475, Houses::HSYS_EQUAL);
        $restored = Houses::removeAyanamsaFromHouses($sidereal, 23.85322475);

        self::assertEqualsWithDelta(310.4570724, $restored['ascmc'][2], 1e-12);
    }

    public function testUserSiderealHousesUsesUserUtBit(): void
    {
        $sidMode = Catalog::SE_SIDM_USER + Catalog::SE_SIDBIT_USER_UT;

        $custom = Houses::customSiderealHouses(
            2341500.0,
            59.0,
            30.0,
            Houses::HSYS_PLACIDUS,
            2374717.0,
            30.0,
            false,
            true
        );

        $user = Houses::userSiderealHouses(
            2341500.0,
            59.0,
            30.0,
            Houses::HSYS_PLACIDUS,
            $sidMode,
            2374717.0,
            30.0,
            false
        );

        self::assertEqualsWithDelta($custom['cusps'][1], $user['cusps'][1], 1e-12);
        self::assertEqualsWithDelta($custom['ascmc'][0], $user['ascmc'][0], 1e-12);
        self::assertEqualsWithDelta($custom['ascmc'][2], $user['ascmc'][2], 1e-12);
    }

    public function testUserSiderealHousesWithoutUserUtBitTreatsReferenceEpochAsEt(): void
    {
        $custom = Houses::customSiderealHouses(
            2341500.0,
            59.0,
            30.0,
            Houses::HSYS_EQUAL,
            2374717.0,
            30.0,
            false,
            false
        );

        $user = Houses::userSiderealHouses(
            2341500.0,
            59.0,
            30.0,
            Houses::HSYS_EQUAL,
            Catalog::SE_SIDM_USER,
            2374717.0,
            30.0,
            false
        );

        self::assertSame($custom, $user);
    }

    public function testUserSiderealHousesArmcUsesUserAyanamsa(): void
    {
        $tjdUt = 2341500.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);
        $sidMode = Catalog::SE_SIDM_USER + Catalog::SE_SIDBIT_USER_UT;

        $custom = Houses::customSiderealHousesArmc(
            310.4570724,
            59.0,
            23.4376767,
            Houses::HSYS_EQUAL,
            Ayanamsa::customAyanamsa($tjdEt, 2374717.0, 30.0, false, true)
        );

        $user = Houses::userSiderealHousesArmc(
            $tjdEt,
            310.4570724,
            59.0,
            23.4376767,
            Houses::HSYS_EQUAL,
            $sidMode,
            2374717.0,
            30.0,
            false
        );

        self::assertSame($custom, $user);
    }

    public function testHousesResultWrapsArrayResult(): void
    {
        $array = Houses::houses(2451545.0, 59.0, 30.0, Houses::HSYS_PLACIDUS);
        $result = Houses::housesResult(2451545.0, 59.0, 30.0, Houses::HSYS_PLACIDUS);

        self::assertSame($array, $result->toArray());
        self::assertSame($array['cusps'][1], $result->cusp(1));
        self::assertSame($array['ascmc'][0], $result->ascendant());
    }

    public function testHousesArmcResultWrapsArrayResult(): void
    {
        $array = Houses::housesArmc(310.4570724, 59.0, 23.4376767, Houses::HSYS_EQUAL);
        $result = Houses::housesArmcResult(310.4570724, 59.0, 23.4376767, Houses::HSYS_EQUAL);

        self::assertSame($array, $result->toArray());
        self::assertSame($array['ascmc'][2], $result->armc());
    }

    public function testSiderealHousesResultWrapsArrayResult(): void
    {
        $array = Houses::siderealHouses(2451545.0, 59.0, 30.0, Houses::HSYS_PLACIDUS);
        $result = Houses::siderealHousesResult(2451545.0, 59.0, 30.0, Houses::HSYS_PLACIDUS);

        self::assertSame($array, $result->toArray());
    }

    public function testSiderealHousesArmcResultWrapsArrayResult(): void
    {
        $array = Houses::siderealHousesArmc(310.4570724, 59.0, 23.4376767, Houses::HSYS_EQUAL, 23.85322475);
        $result = Houses::siderealHousesArmcResult(310.4570724, 59.0, 23.4376767, Houses::HSYS_EQUAL, 23.85322475);

        self::assertSame($array, $result->toArray());
    }

    public function testCustomSiderealHousesResultWrapsArrayResult(): void
    {
        $array = Houses::customSiderealHouses(
            2451545.0,
            59.0,
            30.0,
            Houses::HSYS_PLACIDUS,
            2451545.0,
            30.0,
            false
        );

        $result = Houses::customSiderealHousesResult(
            2451545.0,
            59.0,
            30.0,
            Houses::HSYS_PLACIDUS,
            2451545.0,
            30.0,
            false
        );

        self::assertSame($array, $result->toArray());
    }

    public function testUserSiderealHousesResultWrapsArrayResult(): void
    {
        $sidMode = Catalog::SE_SIDM_USER + Catalog::SE_SIDBIT_USER_UT;

        $array = Houses::userSiderealHouses(
            2341500.0,
            59.0,
            30.0,
            Houses::HSYS_EQUAL,
            $sidMode,
            2374717.0,
            30.0,
            false
        );

        $result = Houses::userSiderealHousesResult(
            2341500.0,
            59.0,
            30.0,
            Houses::HSYS_EQUAL,
            $sidMode,
            2374717.0,
            30.0,
            false
        );

        self::assertSame($array, $result->toArray());
    }

    public function testCustomSiderealHousesArmcResultWrapsArrayResult(): void
    {
        $array = Houses::customSiderealHousesArmc(
            310.4570724,
            59.0,
            23.4376767,
            Houses::HSYS_EQUAL,
            30.0
        );

        $result = Houses::customSiderealHousesArmcResult(
            310.4570724,
            59.0,
            23.4376767,
            Houses::HSYS_EQUAL,
            30.0
        );

        self::assertSame($array, $result->toArray());
    }

    public function testUserSiderealHousesArmcResultWrapsArrayResult(): void
    {
        $tjdEt = 2341500.0 + DeltaT::deltatEx(2341500.0, -1);
        $sidMode = Catalog::SE_SIDM_USER + Catalog::SE_SIDBIT_USER_UT;

        $array = Houses::userSiderealHousesArmc(
            $tjdEt,
            310.4570724,
            59.0,
            23.4376767,
            Houses::HSYS_EQUAL,
            $sidMode,
            2374717.0,
            30.0,
            false
        );

        $result = Houses::userSiderealHousesArmcResult(
            $tjdEt,
            310.4570724,
            59.0,
            23.4376767,
            Houses::HSYS_EQUAL,
            $sidMode,
            2374717.0,
            30.0,
            false
        );

        self::assertSame($array, $result->toArray());
    }

    /**
     * @return iterable<string, array{string|int, string}>
     */
    public static function houseNameProvider(): iterable
    {
        yield 'A' => ['A', 'equal'];
        yield 'B' => ['B', 'Alcabitius'];
        yield 'C' => ['C', 'Campanus'];
        yield 'D' => ['D', 'equal (MC)'];
        yield 'E' => ['E', 'equal'];
        yield 'F' => ['F', 'Carter poli-equ.'];
        yield 'G' => ['G', 'Gauquelin sectors'];
        yield 'H' => ['H', 'horizon/azimut'];
        yield 'I' => ['I', 'Sunshine'];
        yield 'i' => ['i', 'Sunshine/alt.'];
        yield 'J' => ['J', 'Savard-A'];
        yield 'K' => ['K', 'Koch'];
        yield 'L' => ['L', 'Pullen SD'];
        yield 'M' => ['M', 'Morinus'];
        yield 'N' => ['N', 'equal/1=Aries'];
        yield 'O' => ['O', 'Porphyry'];
        yield 'Q' => ['Q', 'Pullen SR'];
        yield 'R' => ['R', 'Regiomontanus'];
        yield 'S' => ['S', 'Sripati'];
        yield 'T' => ['T', 'Polich/Page'];
        yield 'U' => ['U', 'Krusinski-Pisa-Goelzer'];
        yield 'V' => ['V', 'equal/Vehlow'];
        yield 'W' => ['W', 'equal/ whole sign'];
        yield 'X' => ['X', 'axial rotation system/Meridian houses'];
        yield 'Y' => ['Y', 'APC houses'];
        yield 'default Placidus' => ['P', 'Placidus'];
        yield 'lowercase normalizes' => ['k', 'Koch'];
        yield 'integer char code' => [ord('R'), 'Regiomontanus'];
    }
}