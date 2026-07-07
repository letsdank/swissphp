<?php

declare(strict_types=1);

namespace SwissEph;

final readonly class NatalChartPoint
{
    private const SIGN_NAMES = [
        'Aries',
        'Taurus',
        'Gemini',
        'Cancer',
        'Leo',
        'Virgo',
        'Libra',
        'Scorpio',
        'Sagittarius',
        'Capricorn',
        'Aquarius',
        'Pisces',
    ];

    public function __construct(
        public int    $body,
        public string $name,
        public float  $longitude,
        public float  $latitude,
        public float  $distance,
        public float  $speedLongitude = 0.0,
        public ?int   $house = null,
    )
    {
    }

    public function normalizedLongitude(): float
    {
        return Angle::degnorm($this->longitude);
    }

    public function signIndex(): int
    {
        return intdiv((int)floor($this->normalizedLongitude()), 30);
    }

    public function signName(): string
    {
        return self::SIGN_NAMES[$this->signIndex()];
    }

    public function signDegree(): float
    {
        return $this->normalizedLongitude() - $this->signIndex() * 30.0;
    }

    public function isRetrograde(): bool
    {
        return $this->speedLongitude < 0.0;
    }

    /**
     * @return array<string, int|float|string|null|bool>
     */
    public function toArray(): array
    {
        return [
            'body' => $this->body,
            'name' => $this->name,
            'longitude' => $this->longitude,
            'latitude' => $this->latitude,
            'distance' => $this->distance,
            'speedLongitude' => $this->speedLongitude,
            'house' => $this->house,
            'signIndex' => $this->signIndex(),
            'signName' => $this->signName(),
            'signDegree' => $this->signDegree(),
            'retrograde' => $this->isRetrograde(),
        ];
    }

    /**
     * @param array{body:int, name:string, longitude:float, latitude:float, distance:float, speedLongitude?:float, house?:int|null} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['body'],
            $data['name'],
            $data['longitude'],
            $data['latitude'],
            $data['distance'],
            $data['speedLongitude'] ?? 0.0,
            $data['house'] ?? null,
        );
    }
}