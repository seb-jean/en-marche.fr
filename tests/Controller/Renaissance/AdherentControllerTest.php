<?php

namespace Tests\App\Controller\Renaissance;

use App\Adherent\Command\RemoveAdherentAndRelatedDataCommand;
use App\Adherent\Handler\RemoveAdherentAndRelatedDataCommandHandler;
use App\DataFixtures\ORM\LoadAdherentData;
use App\Entity\Adherent;
use App\Entity\Reporting\EmailSubscriptionHistory;
use App\Entity\SubscriptionType;
use App\Entity\Unregistration;
use App\Mailer\Message\AdherentTerminateMembershipMessage;
use App\Repository\EmailRepository;
use App\Repository\UnregistrationRepository;
use App\SendInBlue\Client;
use App\Subscription\SubscriptionTypeEnum;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\App\AbstractWebCaseTest as WebTestCase;
use Tests\App\Controller\ControllerTestTrait;
use Tests\App\Test\SendInBlue\DummyClient;

/**
 * @group functional
 * @group adherent
 */
class AdherentControllerTest extends WebTestCase
{
    use ControllerTestTrait;

    /* @var EmailRepository */
    private $emailRepository;

    /**
     * @dataProvider provideProfilePage
     */
    public function testProfileActionIsSecured(string $profilePage): void
    {
        $this->client->request(Request::METHOD_GET, $profilePage);

        $this->assertResponseStatusCode(Response::HTTP_FOUND, $this->client->getResponse());
        $this->assertClientIsRedirectedTo('/connexion', $this->client);
    }

    public function provideProfilePage(): \Generator
    {
        yield 'Mes informations personnelles' => ['/parametres/mon-compte/modifier'];
        yield 'Mot de passe' => ['/parametres/mon-compte/changer-mot-de-passe'];
        yield 'Certification' => ['/parametres/mon-compte/certification'];
    }

    public function testProfileActionIsAccessibleForAdherent(): void
    {
        $this->authenticateAsAdherent($this->client, 'carl999@example.fr');

        $crawler = $this->client->request(Request::METHOD_GET, '/parametres/mon-compte/modifier');

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());
        $this->assertSame('Carl Mirabeau', trim($crawler->filter('h6')->text()));
        $this->assertStringContainsString('Adhérent depuis le 16 novembre 2016', $crawler->filter('#adherent-since')->text());
    }

    public function testProfileActionIsAccessibleForInactiveAdherent(): void
    {
        $this->authenticateAsAdherent($this->client, 'thomas.leclerc@example.ch');

        $crawler = $this->client->request(Request::METHOD_GET, '/parametres/mon-compte/modifier');

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());
        $this->assertSame('Thomas Leclerc', trim($crawler->filter('h6')->text()));
        $this->assertStringContainsString('Non adhérent.', $crawler->filter('#adherent-since')->text());
    }

    public function testProfileActionIsNotAccessibleForDisabledAdherent(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/connexion');

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $this->client->submit($crawler->selectButton('Connexion')->form([
            '_login_email' => 'michelle.dufour@example.ch',
            '_login_password' => LoadAdherentData::DEFAULT_PASSWORD,
        ]));

        $this->assertClientIsRedirectedTo('/connexion', $this->client);

        $crawler = $this->client->followRedirect();

        $this->assertStringContainsString('Pour vous connecter vous devez confirmer votre adhésion. Si vous n\'avez pas reçu le mail de validation, vous pouvez cliquer ici pour le recevoir à nouveau.', $crawler->filter('.text-red-400')->text());
    }

    public function testEditAdherentProfile(): void
    {
        $this->authenticateAsAdherent($this->client, 'carl999@example.fr');

        $adherent = $this->getAdherentRepository()->findOneByEmail('carl999@example.fr');
        $oldLatitude = $adherent->getLatitude();
        $oldLongitude = $adherent->getLongitude();
        $histories06Subscriptions = $this->findEmailSubscriptionHistoryByAdherent($adherent, 'subscribe', '06');
        $histories06Unsubscriptions = $this->findEmailSubscriptionHistoryByAdherent($adherent, 'unsubscribe', '06');
        $histories77Subscriptions = $this->findEmailSubscriptionHistoryByAdherent($adherent, 'subscribe', '77');
        $histories77Unsubscriptions = $this->findEmailSubscriptionHistoryByAdherent($adherent, 'unsubscribe', '77');

        $this->assertCount(6, $histories77Subscriptions);
        $this->assertCount(0, $histories77Unsubscriptions);
        $this->assertCount(0, $histories06Subscriptions);
        $this->assertCount(0, $histories06Unsubscriptions);

        $crawler = $this->client->request(Request::METHOD_GET, '/parametres/mon-compte/modifier');

        $inputPattern = 'input[name="adherent_profile[%s]"]';
        $optionPattern = 'select[name="adherent_profile[%s]"] option[selected="selected"]';

        self::assertSame('male', $crawler->filter(sprintf($optionPattern, 'gender'))->attr('value'));
        self::assertSame('Carl', $crawler->filter(sprintf($inputPattern, 'firstName'))->attr('value'));
        self::assertSame('Mirabeau', $crawler->filter(sprintf($inputPattern, 'lastName'))->attr('value'));
        self::assertSame('826 avenue du lys', $crawler->filter(sprintf($inputPattern, 'address][address'))->attr('value'));
        self::assertSame('77190', $crawler->filter(sprintf($inputPattern, 'address][postalCode'))->attr('value'));
        self::assertSame('77190-77152', $crawler->filter(sprintf($inputPattern, 'address][city'))->attr('value'));
        self::assertSame('France', $crawler->filter(sprintf($optionPattern, 'address][country'))->text());
        self::assertSame('01 11 22 33 44', $crawler->filter(sprintf($inputPattern, 'phone][number'))->attr('value'));
        self::assertSame('Retraité', $crawler->filter(sprintf($optionPattern, 'position'))->text());
        self::assertSame('1950-07-08', $crawler->filter(sprintf($inputPattern, 'birthdate'))->attr('value'));
        self::assertCount(2, $adherent->getReferentTags());
        self::assertAdherentHasReferentTag($adherent, '77');

        // Submit the profile form with invalid data
        $crawler = $this->client->submit($crawler->selectButton('Enregistrer')->form([
            'adherent_profile' => [
                'emailAddress' => '',
                'gender' => 'male',
                'firstName' => '',
                'lastName' => '',
                'nationality' => '',
                'address' => [
                    'address' => '',
                    'country' => 'FR',
                    'postalCode' => '',
                    'city' => '10102-45029',
                    'cityName' => '',
                ],
                'phone' => [
                    'country' => 'FR',
                    'number' => '',
                ],
                'position' => 'student',
            ],
        ]));

        $this->assertStatusCode(Response::HTTP_OK, $this->client);

        self::assertEmpty($this->getSendInBlueClient()->getUpdateSchedule());

        $errors = $crawler->filter('.re-form-error');
        self::assertSame(7, $errors->count());
        self::assertSame('Cette valeur ne doit pas être vide.', $errors->eq(0)->text());
        self::assertSame('Cette valeur ne doit pas être vide.', $errors->eq(1)->text());
        self::assertSame('La nationalité est requise.', $errors->eq(2)->text());
        self::assertSame('L\'adresse email est requise.', $errors->eq(3)->text());
        self::assertSame('L\'adresse est obligatoire.', $errors->eq(4)->text());
        self::assertSame('Veuillez renseigner un code postal.', $errors->eq(5)->text());
        self::assertSame('Votre adresse n\'est pas reconnue. Vérifiez qu\'elle soit correcte.', $errors->eq(6)->text());

        // Submit the profile form with too long input
        $crawler = $this->client->submit($crawler->selectButton('Enregistrer')->form([
            'adherent_profile' => [
                'emailAddress' => 'carl999@example.fr',
                'gender' => 'female',
                'firstName' => 'Jean',
                'lastName' => 'Dupont',
                'nationality' => 'FR',
                'address' => [
                    'address' => 'Une adresse de 150 caractères, ça peut arriver.Une adresse de 150 caractères, ça peut arriver.Une adresse de 150 caractères, ça peut arriver.Oui oui oui.',
                    'country' => 'FR',
                    'postalCode' => '0600000000000000',
                    'city' => '06000-6088',
                    'cityName' => 'Nice, France',
                ],
                'phone' => [
                    'country' => 'FR',
                    'number' => '04 01 02 03 04',
                ],
                'position' => 'student',
                'birthdate' => '1985-10-27',
            ],
        ]));

        $this->assertStatusCode(Response::HTTP_OK, $this->client);

        self::assertEmpty($this->getSendInBlueClient()->getUpdateSchedule());

        $errors = $crawler->filter('.re-form-error');
        self::assertSame(5, $errors->count());
        self::assertSame('L\'adresse ne peut pas dépasser 150 caractères.', $errors->eq(0)->text());
        self::assertSame('Le code postal doit contenir moins de 15 caractères.', $errors->eq(1)->text());
        self::assertSame('Cette valeur n\'est pas un code postal français valide.', $errors->eq(2)->text());
        self::assertSame('Cette valeur n\'est pas un code postal français valide.', $errors->eq(3)->text());
        self::assertSame('Votre adresse n\'est pas reconnue. Vérifiez qu\'elle soit correcte.', $errors->eq(4)->text());

        // Submit the profile form with valid data
        $this->client->submit($crawler->selectButton('Enregistrer')->form([
            'adherent_profile' => [
                'gender' => 'female',
                'firstName' => 'Jean',
                'lastName' => 'Dupont',
                'address' => [
                    'address' => '9 rue du Lycée',
                    'country' => 'FR',
                    'postalCode' => '06000',
                    'city' => '06000-6088',
                    'cityName' => 'Nice',
                ],
                'phone' => [
                    'country' => 'FR',
                    'number' => '04 01 02 03 04',
                ],
                'position' => 'student',
                'birthdate' => '1985-10-27',
            ],
        ]));

        $this->assertClientIsRedirectedTo('/parametres/mon-compte/modifier', $this->client);

        $sendInBlueUpdates = $this->getSendInBlueClient()->getUpdateSchedule();
        self::assertCount(1, $sendInBlueUpdates);
        self::assertSame('carl999@example.fr', $sendInBlueUpdates[0]['email']);

        $crawler = $this->client->followRedirect();

        $this->seeFlashMessage($crawler, 'Vos informations ont été mises à jour avec succès.');

        // We need to reload the manager reference to get the updated data
        /** @var Adherent $adherent */
        $adherent = $this->client->getContainer()->get('doctrine')->getManager()->getRepository(Adherent::class)->findOneByEmail('carl999@example.fr');

        self::assertSame('female', $adherent->getGender());
        self::assertSame('Jean Dupont', $adherent->getFullName());
        self::assertSame('9 rue du Lycée', $adherent->getAddress());
        self::assertSame('06000', $adherent->getPostalCode());
        self::assertSame('Nice', $adherent->getCityName());
        self::assertSame('401020304', $adherent->getPhone()->getNationalNumber());
        self::assertSame('student', $adherent->getPosition());
        $this->assertNotNull($newLatitude = $adherent->getLatitude());
        $this->assertNotNull($newLongitude = $adherent->getLongitude());
        $this->assertNotSame($oldLatitude, $newLatitude);
        $this->assertNotSame($oldLongitude, $newLongitude);
        self::assertCount(2, $adherent->getReferentTags());
        self::assertAdherentHasReferentTag($adherent, '06');
        self::assertAdherentHasReferentTag($adherent, 'CIRCO_06001');

        $histories06Subscriptions = $this->findEmailSubscriptionHistoryByAdherent($adherent, 'subscribe', '06');
        $histories06Unsubscriptions = $this->findEmailSubscriptionHistoryByAdherent($adherent, 'unsubscribe', '06');
        $histories77Subscriptions = $this->findEmailSubscriptionHistoryByAdherent($adherent, 'subscribe', '77');
        $histories77Unsubscriptions = $this->findEmailSubscriptionHistoryByAdherent($adherent, 'unsubscribe', '77');

        $this->assertCount(6, $histories77Subscriptions);
        $this->assertCount(6, $histories77Unsubscriptions);
        $this->assertCount(6, $histories06Subscriptions);
        $this->assertCount(0, $histories06Unsubscriptions);
    }

    public function testCertifiedAdherentCanNotEditFields(): void
    {
        $this->authenticateAsAdherent($this->client, 'jacques.picard@en-marche.fr');
        $crawler = $this->client->request(Request::METHOD_GET, '/parametres/mon-compte/modifier');

        $disabledFields = $crawler->filter('form[name="adherent_profile"] input[disabled="disabled"], form[name="adherent_profile"] select[disabled="disabled"]');
        self::assertCount(4, $disabledFields);
        self::assertEquals('adherent_profile[firstName]', $disabledFields->eq(0)->attr('name'));
        self::assertEquals('adherent_profile[lastName]', $disabledFields->eq(1)->attr('name'));
        self::assertEquals('adherent_profile[birthdate]', $disabledFields->eq(2)->attr('name'));
        self::assertEquals('adherent_profile[gender]', $disabledFields->eq(3)->attr('name'));
    }

    public function testAdherentChangePassword(): void
    {
        $this->authenticateAsAdherent($this->client, 'carl999@example.fr');

        $crawler = $this->client->request(Request::METHOD_GET, '/parametres/mon-compte/changer-mot-de-passe');

        $this->assertCount(1, $crawler->filter('input[name="adherent_change_password[old_password]"]'));
        $this->assertCount(1, $crawler->filter('input[name="adherent_change_password[password][first]"]'));
        $this->assertCount(1, $crawler->filter('input[name="adherent_change_password[password][second]"]'));

        // Submit the profile form with invalid data
        $crawler = $this->client->submit($crawler->selectButton('adherent_change_password[submit]')->form(), [
            'adherent_change_password' => [
                'old_password' => '',
                'password' => [
                    'first' => '',
                    'second' => '',
                ],
            ],
        ]);

        $errors = $crawler->filter('.re-form-error');

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());
        self::assertSame(2, $errors->count());
        self::assertSame('Le mot de passe est invalide.', $errors->eq(0)->text());
        self::assertSame('Cette valeur ne doit pas être vide.', $errors->eq(1)->text());

        // Submit the profile form with valid data
        $this->client->submit($crawler->selectButton('adherent_change_password[submit]')->form(), [
            'adherent_change_password' => [
                'old_password' => 'secret!12345',
                'password' => [
                    'first' => 'heaneaheah',
                    'second' => 'heaneaheah',
                ],
            ],
        ]);

        $this->assertClientIsRedirectedTo('/parametres/mon-compte/changer-mot-de-passe', $this->client);
    }

    /**
     * @return EmailSubscriptionHistory[]
     */
    public function findEmailSubscriptionHistoryByAdherent(
        Adherent $adherent,
        string $action = null,
        string $referentTagCode = null
    ): array {
        $qb = $this
            ->getEmailSubscriptionHistoryRepository()
            ->createQueryBuilder('history')
            ->where('history.adherentUuid = :adherentUuid')
            ->setParameter('adherentUuid', $adherent->getUuid())
            ->orderBy('history.date', 'DESC')
        ;

        if ($action) {
            $qb
                ->andWhere('history.action = :action')
                ->setParameter('action', $action)
            ;
        }

        if ($referentTagCode) {
            $qb
                ->leftJoin('history.referentTags', 'tag')
                ->andWhere('tag.code = :code')
                ->setParameter('code', $referentTagCode)
            ;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return EmailSubscriptionHistory[]
     */
    public function findAllEmailSubscriptionHistoryByAdherentAndType(
        Adherent $adherent,
        string $subscriptionType
    ): array {
        return $this
            ->getEmailSubscriptionHistoryRepository()
            ->createQueryBuilder('history')
            ->join('history.subscriptionType', 'subscriptionType')
            ->where('history.adherentUuid = :adherentUuid')
            ->andWhere('subscriptionType.code = :type')
            ->orderBy('history.date ', 'DESC')
            ->setParameter('adherentUuid', $adherent->getUuid())
            ->setParameter('type', $subscriptionType)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @dataProvider dataProviderCannotTerminateMembership
     */
    public function testCannotTerminateMembership(string $email): void
    {
        $this->authenticateAsAdherent($this->client, $email);

        $crawler = $this->client->request(Request::METHOD_GET, '/parametres/mon-compte/modifier');

        $this->assertStatusCode(Response::HTTP_OK, $this->client);
        $this->assertStringNotContainsString(
            'Si vous souhaitez désadhérer et supprimer votre compte En Marche, cliquez-ici.',
            $crawler->text()
        );

        $this->client->request(Request::METHOD_GET, '/parametres/mon-compte/desadherer');

        $this->assertStatusCode(Response::HTTP_FORBIDDEN, $this->client);
    }

    public function dataProviderCannotTerminateMembership(): \Generator
    {
        yield 'Host' => ['gisele-berthoux@caramail.com'];
        yield 'Referent' => ['referent@en-marche-dev.fr'];
        yield 'BoardMember' => ['carl999@example.fr'];
        yield 'CommitteeCandidate' => ['adherent-female-a@en-marche-dev.fr'];
        yield 'TerritorialCouncilCandidate' => ['senatorial-candidate@en-marche-dev.fr'];
    }

    /**
     * @dataProvider provideAdherentCredentials
     */
    public function testAdherentTerminatesMembership(string $userEmail, string $uuid): void
    {
        /** @var Adherent $adherent */
        $adherentBeforeUnregistration = $this->getAdherentRepository()->findOneByEmail($userEmail);
        $referentTagsBeforeUnregistration = $adherentBeforeUnregistration->getReferentTags()->toArray(); // It triggers the real SQL query instead of lazy-load

        $this->authenticateAsAdherent($this->client, $userEmail);

        $crawler = $this->client->request(Request::METHOD_GET, '/parametres/mon-compte/modifier');

        $this->assertStatusCode(Response::HTTP_OK, $this->client);
        $this->assertStringContainsString(
            'Cliquez ci-dessous si vous souhaitez supprimer votre compte.',
            $crawler->text()
        );

        $crawler = $this->client->click($crawler->selectLink('Supprimer mon compte')->link());
        $this->assertEquals('http://'.$this->getParameter('renaissance_host').'/parametres/mon-compte/desadherer', $this->client->getRequest()->getUri());
        $this->assertStatusCode(Response::HTTP_OK, $this->client);

        $crawler = $this->client->submit($crawler->selectButton('Je confirme la suppression de mon adhésion')->form());

        $this->assertEquals('http://'.$this->getParameter('renaissance_host').'/parametres/mon-compte/desadherer', $this->client->getRequest()->getUri());

        $errors = $crawler->filter('.form__errors > li');

        $this->assertStatusCode(Response::HTTP_OK, $this->client);
        $this->assertSame(0, $errors->count());
        $this->assertStringContainsString('Votre adhésion et votre compte Renaissance ont bien été supprimés, vos données personnelles ont été effacées de notre base.', $this->client->getResponse()->getContent());

        $this->assertCount(1, $this->getEmailRepository()->findRecipientMessages(AdherentTerminateMembershipMessage::class, $userEmail));

        $this->client->getContainer()->get('test.'.RemoveAdherentAndRelatedDataCommandHandler::class)(
            new RemoveAdherentAndRelatedDataCommand(Uuid::fromString($uuid))
        );

        /** @var Adherent $adherent */
        $adherent = $this->getAdherentRepository()->findOneByEmail($userEmail);

        $this->assertNull($adherent);

        /** @var Unregistration $unregistration */
        $unregistration = $this->get(UnregistrationRepository::class)->findOneByUuid($uuid);
        $mailHistorySubscriptions = $this->findEmailSubscriptionHistoryByAdherent($adherentBeforeUnregistration, 'subscribe');
        $mailHistoryUnsubscriptions = $this->findEmailSubscriptionHistoryByAdherent($adherentBeforeUnregistration, 'unsubscribe');

        $this->assertSame(\count($mailHistorySubscriptions), \count($mailHistoryUnsubscriptions));
        $this->assertEmpty($unregistration->getReasons());
        $this->assertNull($unregistration->getComment());
        $this->assertSame($adherentBeforeUnregistration->getRegisteredAt()->format('Y-m-d H:i:s'), $unregistration->getRegisteredAt()->format('Y-m-d H:i:s'));
        $this->assertSame((new \DateTime())->format('Y-m-d'), $unregistration->getUnregisteredAt()->format('Y-m-d'));
        $this->assertSame($adherentBeforeUnregistration->getUuid()->toString(), $unregistration->getUuid()->toString());
        $this->assertSame($adherentBeforeUnregistration->getPostalCode(), $unregistration->getPostalCode());
        $this->assertEquals($referentTagsBeforeUnregistration, $unregistration->getReferentTags()->toArray());
    }

    public function provideAdherentCredentials(): array
    {
        return [
            'adherent 1' => ['renaissance-user-1@en-marche-dev.fr', LoadAdherentData::RENAISSANCE_USER_1_UUID],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->client->setServerParameter('HTTP_HOST', self::$container->getParameter('renaissance_host'));

        $this->emailRepository = $this->getEmailRepository();
    }

    protected function tearDown(): void
    {
        $this->emailRepository = null;

        parent::tearDown();
    }

    private function getSubscriptionTypesFormValues(array $codes): array
    {
        return array_map(static function (SubscriptionType $type) use ($codes) {
            return \in_array($type->getCode(), $codes, true) ? $type->getId() : false;
        }, $this->getSubscriptionTypeRepository()->findByCodes(SubscriptionTypeEnum::ADHERENT_TYPES));
    }

    private function getSendInBlueClient(): DummyClient
    {
        return $this->client->getContainer()->get(Client::class);
    }
}