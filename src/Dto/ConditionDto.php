<?php

namespace App\Dto;

use App\Entity\Condition;

class ConditionDto
{
    private int $id;
    private string $type;
    private CharacteristicDto $characteristic;

    public function __construct(Condition $condition)
    {
        $this->id = $condition->getId();
        $this->type = $condition->getType();
        $this->characteristic = new CharacteristicDto($condition->getCharacteristic());
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'characteristic' => $this->characteristic->toArray(),
        ];
    }
}
