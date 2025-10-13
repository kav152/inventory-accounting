<?php
require_once __DIR__ . '/../Entity/IProperty.php';

class TypesTMC implements IProperty
{
    public int $IDTypesTMC;
    public $NameTypesTMC;
    public $NameImage;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->IDTypesTMC = (int)$data['IDTypesTMC'];
            $this->NameTypesTMC = $data['NameTypesTMC'];
            $this->NameImage = $data['NameImage'];
        }
    }

    public function getName(): string { return $this->NameTypesTMC; }
    public function getId(): int { return $this->IDTypesTMC ?? 0; }
}