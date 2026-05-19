<?php

declare(strict_types=1);

namespace SwissEph;

final readonly class NodesApsidesResult
{
    /**
     * @param array<int, float> $ascNode
     * @param array<int, float> $descNode
     * @param array<int, float> $perihelion
     * @param array<int, float> $aphelion
     */
    public function __construct(
        public int    $rc,
        public array  $ascNode,
        public array  $descNode,
        public array  $perihelion,
        public array  $aphelion,
        public string $error = '',
    )
    {
    }

    /**
     * @param array{
     *     rc:int,
     *     ascNode:array<int, float>,
     *     descNode:array<int, float>,
     *     perihelion:array<int, float>,
     *     aphelion:array<int, float>,
     *     error:string
     * } $result
     */
    public static function fromArray(array $result): self
    {
        return new self(
            $result['rc'],
            $result['ascNode'],
            $result['descNode'],
            $result['perihelion'],
            $result['aphelion'],
            $result['error'],
        );
    }

    /**
     * @return array{
     *     rc:int,
     *     ascNode:array<int, float>,
     *     descNode:array<int, float>,
     *     perihelion:array<int, float>,
     *     aphelion:array<int, float>,
     *     error:string
     *  }
     */
    public function toArray(): array
    {
        return [
            'rc' => $this->rc,
            'ascNode' => $this->ascNode,
            'descNode' => $this->descNode,
            'perihelion' => $this->perihelion,
            'aphelion' => $this->aphelion,
            'error' => $this->error,
        ];
    }

    public function isOk(): bool
    {
        return $this->rc !== SwissDate::ERR;
    }

    public function hasError(): bool
    {
        return !$this->isOk() || $this->error !== '';
    }

    public function ascendingNodeLongitude(): float
    {
        return $this->ascNode[0];
    }

    public function descendingNodeLongitude(): float
    {
        return $this->descNode[0];
    }

    public function perihelionLongitude(): float
    {
        return $this->perihelion[0];
    }

    public function aphelionLongitude(): float
    {
        return $this->aphelion[0];
    }

    public function ascendingNodeDms(): string
    {
        return Angle::formatDms($this->ascendingNodeLongitude());
    }

    public function descendingNodeDms(): string
    {
        return Angle::formatDms($this->descendingNodeLongitude());
    }

    public function perihelionDms(): string
    {
        return Angle::formatDms($this->perihelionLongitude());
    }

    public function aphelionDms(): string
    {
        return Angle::formatDms($this->aphelionLongitude());
    }
}