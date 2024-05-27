<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as SymfonySerializer;

trait EntityPersonNameTrait
{
    /**
     * @ORM\Column(length=50)
     *
     * @SymfonySerializer\Groups({
     *     "user_profile",
     *     "export",
     *     "cause_read",
     *     "profile_read",
     *     "event_read",
     *     "event_list_read",
     *     "adherent_autocomplete",
     *     "phoning_campaign_history_read_list",
     *     "phoning_campaign_replies_list",
     *     "pap_campaign_replies_list",
     *     "pap_address_list",
     *     "pap_address_read",
     *     "message_read_list",
     *     "message_read",
     *     "pap_campaign_history_read_list",
     *     "pap_building_statistics_read",
     *     "survey_list_dc",
     *     "survey_read_dc",
     *     "survey_replies_list",
     *     "my_team_member_read",
     *     "my_team_read_list",
     *     "scope",
     *     "elected_representative_read",
     *     "committee_candidacy:read",
     *     "committee_election:read",
     *     "committee:read",
     *     "national_event_inscription:webhook",
     *     "procuration_request_list",
     *     "procuration_request_read",
     *     "procuration_matched_proxy",
     *     "procuration_proxy_list_request",
     *     "action_read",
     *     "action_read_list",
     * })
     */
    private $firstName = '';

    /**
     * @ORM\Column(length=50)
     *
     * @SymfonySerializer\Groups({
     *     "user_profile",
     *     "profile_read",
     *     "cause_read",
     *     "event_read",
     *     "event_list_read",
     *     "adherent_autocomplete",
     *     "phoning_campaign_history_read_list",
     *     "phoning_campaign_replies_list",
     *     "pap_campaign_replies_list",
     *     "pap_address_list",
     *     "pap_address_read",
     *     "message_read_list",
     *     "message_read",
     *     "pap_campaign_history_read_list",
     *     "pap_building_statistics_read",
     *     "survey_list_dc",
     *     "survey_read_dc",
     *     "survey_replies_list",
     *     "my_team_member_read",
     *     "my_team_read_list",
     *     "scope",
     *     "elected_representative_read",
     *     "committee_candidacy:read",
     *     "committee_election:read",
     *     "committee:read",
     *     "national_event_inscription:webhook",
     *     "procuration_request_list",
     *     "procuration_request_read",
     *     "procuration_matched_proxy",
     *     "procuration_proxy_list_request",
     *     "action_read",
     *     "action_read_list",
     * })
     */
    private $lastName = '';

    public function __toString(): string
    {
        return trim($this->getFullName());
    }

    /**
     * @SymfonySerializer\Groups({"api_candidacy_read"})
     */
    public function getFullName(): string
    {
        return $this->firstName.' '.$this->lastName;
    }

    /**
     * @SymfonySerializer\Groups({"pap_building_history"})
     */
    public function getPartialName(): string
    {
        return $this->firstName.' '.$this->getLastNameInitial();
    }

    public function getFirstName(): string
    {
        return (string) $this->firstName;
    }

    public function getLastName(): string
    {
        return (string) $this->lastName;
    }

    /**
     * @SymfonySerializer\Groups({"export", "cause_read"})
     */
    public function getLastNameInitial(bool $padWithDot = true): string
    {
        $normalized = self::normalize($this->lastName);

        $initial = strtoupper($normalized[0]);

        if ($padWithDot) {
            $initial .= '.';
        }

        return $initial;
    }

    public function getFirstNameInitial(): string
    {
        $normalized = self::normalize($this->firstName);

        return mb_strtoupper(mb_substr($normalized, 0, 1));
    }

    /**
     * @SymfonySerializer\Groups({"api_candidacy_read"})
     */
    public function getInitials(): string
    {
        return $this->getFirstNameInitial().$this->getLastNameInitial(false);
    }

    private static function normalize(string $name): string
    {
        return preg_replace('/[^a-z]+/', '', strtolower($name));
    }
}
