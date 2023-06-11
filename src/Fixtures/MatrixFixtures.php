<?php

namespace App\Fixtures;

use App\Entity\Matrix;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MatrixFixtures extends Fixture
{
    public const REF_MATRIX_BPLA = 'ref_matrix_bpla';

    public function load(ObjectManager $manager)
    {
        foreach ($this->getData() as $data) {
            $matrix = new Matrix();

            $matrix->setTitle($data['title']);

            $this->addReference($data['reference'], $matrix);

            $manager->persist($matrix);
        }

        $manager->flush();
    }

    private function getData(): array
    {
        return [
            [
                'reference' => self::REF_MATRIX_BPLA,
                'title' => 'БПЛА',
            ],
        ];
    }
}
