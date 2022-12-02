<?php

namespace App\DataFixtures\ORM;

use App\Entity\VotingPlatform\Designation\Designation;
use App\VotingPlatform\Designation\DesignationGlobalZoneEnum;
use App\VotingPlatform\Designation\DesignationTypeEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LoadDesignationData extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        // Committee designation with started CANDIDATURE period in France
        $designation = new Designation('Désignation avec les candidatures ouvertes');
        $designation->setGlobalZones([DesignationGlobalZoneEnum::FRANCE]);
        $designation->setType(DesignationTypeEnum::COMMITTEE_ADHERENT);
        $designation->setCandidacyStartDate(new \DateTime('-1 month'));
        $designation->setCandidacyEndDate(new \DateTime('+1 week'));
        $designation->setVoteStartDate(new \DateTime('+1 week'));
        $designation->setVoteEndDate(new \DateTime('+4 week'));

        $this->setReference('designation-1', $designation);
        $manager->persist($designation);

        // Committee designation with started VOTE period
        $designation = new Designation('Désignation avec les votes ouverts');
        $designation->setGlobalZones([DesignationGlobalZoneEnum::FRANCE]);
        $designation->setType(DesignationTypeEnum::COMMITTEE_ADHERENT);
        $designation->setCandidacyStartDate(new \DateTime('-1 month'));
        $designation->setCandidacyEndDate(new \DateTime('-2 hours'));
        $designation->setVoteStartDate(new \DateTime('-1 hour'));
        $designation->setVoteEndDate(new \DateTime('+4 week'));

        $this->setReference('designation-2', $designation);
        $manager->persist($designation);

        // Committee designation with started RESULT period
        $designation = new Designation('Désignation avec les résultats disponibles');
        $designation->setGlobalZones([DesignationGlobalZoneEnum::FRANCE]);
        $designation->setType(DesignationTypeEnum::COMMITTEE_ADHERENT);
        $designation->setCandidacyStartDate(new \DateTime('-1 month'));
        $designation->setCandidacyEndDate(new \DateTime('-1 week'));
        $designation->setVoteStartDate(new \DateTime('-6 days'));
        $designation->setVoteEndDate(new \DateTime('-1 hour'));

        $this->setReference('designation-3', $designation);
        $manager->persist($designation);

        // Archived Committee designation
        $designation = new Designation('Désignation archivée');
        $designation->setGlobalZones([DesignationGlobalZoneEnum::FRANCE]);
        $designation->setType(DesignationTypeEnum::COMMITTEE_ADHERENT);
        $designation->setCandidacyStartDate(new \DateTime('-6 months'));
        $designation->setCandidacyEndDate(new \DateTime('-5 months'));
        $designation->setVoteStartDate(new \DateTime('-5 months'));
        $designation->setVoteEndDate(new \DateTime('-4 months'));

        $this->setReference('designation-4', $designation);
        $manager->persist($designation);

        // Committee designation with started CANDIDATURE period in FDE
        $designation = new Designation('Désignation "Comités-Animateurs" ouverte');
        $designation->setGlobalZones([DesignationGlobalZoneEnum::FDE]);
        $designation->setType(DesignationTypeEnum::COMMITTEE_SUPERVISOR);
        $designation->setCandidacyStartDate(new \DateTime('-1 day'));
        $designation->setCandidacyEndDate(new \DateTime('+5 days'));
        $designation->setVoteStartDate(new \DateTime('+7 days'));
        $designation->setVoteEndDate(new \DateTime('+2 weeks'));

        $this->setReference('designation-5', $designation);
        $manager->persist($designation);

        // COPOL designation with started CANDIDATURE period
        $designation = new Designation('Désignation COPOL avec les candidatures ouvertes');
        $designation->setType(DesignationTypeEnum::COPOL);
        $designation->addReferentTag($this->getReference('referent_tag_75'));
        $designation->setCandidacyStartDate(new \DateTime('-1 month'));

        $this->setReference('designation-6', $designation);
        $manager->persist($designation);

        // COPOL designation with started VOTE period
        $designation = new Designation('Désignation COPOL les votes');
        $designation->setType(DesignationTypeEnum::COPOL);
        $designation->addReferentTag($this->getReference('referent_tag_92'));
        $designation->setCandidacyStartDate(new \DateTime('-1 month'));
        $designation->setCandidacyEndDate(new \DateTime('-2 hours'));
        $designation->setVoteStartDate(new \DateTime('-1 hour'));
        $designation->setVoteEndDate(new \DateTime('+4 week'));
        $designation->markAsLimited();

        $this->setReference('designation-7', $designation);
        $manager->persist($designation);

        // SUPERVISOR designation with started VOTE period
        $designation = new Designation('Désignation "Comités-Animateurs" vote ouvert');
        $designation->setGlobalZones([DesignationGlobalZoneEnum::FDE]);
        $designation->setType(DesignationTypeEnum::COMMITTEE_SUPERVISOR);
        $designation->setCandidacyStartDate(new \DateTime('-10 days'));
        $designation->setCandidacyEndDate(new \DateTime('-2 days'));
        $designation->setVoteStartDate(new \DateTime('-1 day'));
        $designation->setVoteEndDate(new \DateTime('+2 weeks'));
        $designation->setDenomination(Designation::DENOMINATION_ELECTION);

        $this->setReference('designation-8', $designation);
        $manager->persist($designation);

        // SUPERVISOR designation with result period
        $designation = new Designation('Désignation "Comités-Animateurs" resultats affichés');
        $designation->setGlobalZones([DesignationGlobalZoneEnum::FDE]);
        $designation->setType(DesignationTypeEnum::COMMITTEE_SUPERVISOR);
        $designation->setCandidacyStartDate(new \DateTime('-15 days'));
        $designation->setCandidacyEndDate(new \DateTime('-10 days'));
        $designation->setVoteStartDate(new \DateTime('-8 days'));
        $designation->setVoteEndDate(new \DateTime('-1 day'));
        $designation->setDenomination(Designation::DENOMINATION_ELECTION);

        $this->setReference('designation-9', $designation);
        $manager->persist($designation);

        // NATIONAL_COUNCIL designation with started candidature period
        $designation = new Designation('Désignation Conseil national avec les candidatures');
        $designation->setType(DesignationTypeEnum::NATIONAL_COUNCIL);
        $designation->addReferentTag($this->getReference('referent_tag_59'));
        $designation->setCandidacyStartDate(new \DateTime('-1 month'));

        $this->setReference('designation-10', $designation);
        $manager->persist($designation);

        // EXECUTIVE_OFFICE election
        $designation = new Designation('Élection Bureau Exécutif');
        $designation->setType(DesignationTypeEnum::EXECUTIVE_OFFICE);
        $designation->setDenomination(Designation::DENOMINATION_ELECTION);
        $designation->setCandidacyStartDate(new \DateTime('-1 month'));
        $designation->setCandidacyEndDate(new \DateTime('-10 minutes'));
        $designation->setVoteStartDate(new \DateTime('+1 day'));
        $designation->setVoteEndDate(new \DateTime('+10 days'));
        $designation->setNotifications(0);
        $designation->setResultScheduleDelay(2.5);

        $this->setReference('designation-11', $designation);
        $manager->persist($designation);

        // POLL election
        $designation = new Designation('Vote des statuts');
        $designation->setType(DesignationTypeEnum::POLL);
        $designation->setDenomination(Designation::DENOMINATION_ELECTION);
        $designation->setCandidacyStartDate(new \DateTime('-1 month'));
        $designation->setCandidacyEndDate(new \DateTime('-10 minutes'));
        $designation->setVoteStartDate(new \DateTime('-5 minutes'));
        $designation->setVoteEndDate(new \DateTime('+10 days'));
        $designation->setResultScheduleDelay(2.5);

        $this->setReference('designation-12', $designation);
        $manager->persist($designation);

        // Local election in dpt 92
        $designation = new Designation('Élection départementale dans le département 92');
        $designation->setType(DesignationTypeEnum::LOCAL_ELECTION);
        $designation->setVoteStartDate(new \DateTime('-5 minutes'));
        $designation->setVoteEndDate(new \DateTime('+10 days'));
        $designation->addZone(LoadGeoZoneData::getZoneReference($manager, 'zone_department_92'));

        $this->setReference('designation-13', $designation);
        $manager->persist($designation);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            LoadReferentTagData::class,
        ];
    }
}
