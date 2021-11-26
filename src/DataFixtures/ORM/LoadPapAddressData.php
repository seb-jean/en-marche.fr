<?php

namespace App\DataFixtures\ORM;

use App\Entity\Pap\Address;
use App\Entity\Pap\Voter;
use App\ValueObject\Genders;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;

class LoadPapAddressData extends Fixture
{
    private const ADDRESS_01_UUID = 'a0b9231b-9ff5-49b9-aa7a-1d28abbba32f';
    private const ADDRESS_02_UUID = 'ccfd846a-5439-42ad-85ce-286baf4e7269';
    private const ADDRESS_03_UUID = '702eda29-39c6-4b3d-b28f-3fd3806747b2';
    private const ADDRESS_04_UUID = '04e1d76f-c727-4612-afab-2dec2d71a480';

    private const VOTER_01_UUID = 'bdb9d49c-20f5-44c0-bc4a-d8b75f85ee95';
    private const VOTER_02_UUID = '0cf560f0-c5ec-43ef-9ea1-b6fd2a2dc339';
    private const VOTER_03_UUID = '75c6bacb-f278-4194-b1df-014de729aead';
    private const VOTER_04_UUID = '7d3c0207-f3aa-4804-b713-f01ab29052e6';
    private const VOTER_05_UUID = '348fd353-9819-4dfc-848d-211672ebb6b9';
    private const VOTER_06_UUID = '881cd07e-efce-4fda-890b-70ad277c8c32';
    private const VOTER_07_UUID = '536f8caa-a644-449e-8bdf-aca3475d9276';

    public function load(ObjectManager $manager)
    {
        $address = $this->createAddress(
            self::ADDRESS_01_UUID,
            '55',
            'Rue du Rocher',
            '75108',
            ['75008'],
            'Paris 8ème',
            66380,
            45080,
            48.878708,
            2.319111
        );
        $address->addVoter($this->createVoter(self::VOTER_01_UUID, 'John', 'Doe', Genders::MALE, '-30 years', '75108_0001'));
        $address->addVoter($this->createVoter(self::VOTER_02_UUID, 'Jane', 'Doe', Genders::FEMALE, '-29 years', '75108_0001'));
        $this->addReference('address-1', $address);
        $manager->persist($address);

        $address = $this->createAddress(
            self::ADDRESS_02_UUID,
            '65',
            'Rue du Rocher',
            '75108',
            ['75008'],
            'Paris 8ème',
            66380,
            45080,
            48.879078,
            2.318631
        );
        $address->addVoter($this->createVoter(self::VOTER_03_UUID, 'Jack', 'Doe', Genders::MALE, '-55 years', '75108_0001'));
        $this->addReference('address-2', $address);
        $manager->persist($address);

        $address = $this->createAddress(
            self::ADDRESS_03_UUID,
            '67',
            'Rue du Rocher',
            '75108',
            ['75008'],
            'Paris 8ème',
            66380,
            45079,
            48.879246,
            2.318427
        );
        $address->addVoter($this->createVoter(self::VOTER_04_UUID, 'Mickaël', 'Doe', Genders::MALE, '-44 years', '75108_0001'));
        $address->addVoter($this->createVoter(self::VOTER_05_UUID, 'Mickaëla', 'Doe', Genders::FEMALE, '-45 years', '75108_0001'));
        $address->addVoter($this->createVoter(self::VOTER_06_UUID, 'Mickaël Jr', 'Doe', Genders::MALE, '-22 years', '75108_0001'));
        $this->addReference('address-3', $address);
        $manager->persist($address);

        $address = $this->createAddress(
            self::ADDRESS_04_UUID,
            '70',
            'Rue du Rocher',
            '75108',
            ['75008'],
            'Paris 8ème',
            66380,
            45080,
            48.879166,
            2.318761
        );
        $address->addVoter($this->createVoter(self::VOTER_07_UUID, 'Patrick', 'Simpson Jones', Genders::MALE, '-70 years', '75108_0001'));
        $this->addReference('address-4', $address);
        $manager->persist($address);

        $manager->flush();
    }

    private function createAddress(
        string $uuid,
        string $number,
        string $street,
        string $inseeCode,
        ?array $postalCodes,
        string $cityName,
        int $offsetX,
        int $offsetY,
        float $latitude,
        float $longitude
    ): Address {
        return new Address(
            Uuid::fromString($uuid),
            $number,
            $street,
            $inseeCode,
            $postalCodes,
            $cityName,
            $offsetX,
            $offsetY,
            $latitude,
            $longitude
        );
    }

    private function createVoter(
        string $uuid,
        string $firstName,
        string $lastName,
        string $gender,
        string $birthdate,
        string $votePlace = null
    ): Voter {
        return new Voter(
            Uuid::fromString($uuid),
            $firstName,
            $lastName,
            $gender,
            new \DateTime($birthdate),
            $votePlace
        );
    }
}
