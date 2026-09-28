<?php

declare(strict_types=1);

namespace LeviLabs\LaravelQicard\Data;

/**
 * Optional details about the payer, sent on payment creation. Tracks the
 * `CustomerInfo` schema exactly — most fields exist for Instant Payment /
 * regulatory (AML) use cases and are rarely needed for a plain card payment;
 * pass only what you have.
 */
final readonly class CustomerInfo
{
    public function __construct(
        public ?string $firstName = null,
        public ?string $middleName = null,
        public ?string $lastName = null,
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $accountId = null,
        public ?string $accountNumber = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $provinceCode = null,
        /** ISO 3166-1 alpha-2 or alpha-3 country code, e.g. "IQ". */
        public ?string $countryCode = null,
        public ?string $postalCode = null,
        /** Format `MMDDYYYY`. */
        public ?string $birthDate = null,
        /** "00" Passport, "01" National ID, "02" Driver's License, "03" Government Issued, "04" Other. */
        public ?string $identificationType = null,
        public ?string $identificationNumber = null,
        public ?string $identificationCountryCode = null,
        /** Format `MMDDYYYY`. */
        public ?string $identificationExpirationDate = null,
        public ?string $nationality = null,
        public ?string $countryOfBirth = null,
        /** See CustomerInfo::fundSource docblock in the package README for the full code table (differs by card scheme). */
        public ?string $fundSource = null,
        public ?string $participantId = null,
        public ?string $additionalMessage = null,
        /** "00" Family support … "09" Salary — full table in the README. */
        public ?string $transactionReason = null,
        public ?string $claimCode = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return array_filter([
            'firstName' => $this->firstName,
            'middleName' => $this->middleName,
            'lastName' => $this->lastName,
            'phone' => $this->phone,
            'email' => $this->email,
            'accountId' => $this->accountId,
            'accountNumber' => $this->accountNumber,
            'address' => $this->address,
            'city' => $this->city,
            'provinceCode' => $this->provinceCode,
            'countryCode' => $this->countryCode,
            'postalCode' => $this->postalCode,
            'birthDate' => $this->birthDate,
            'identificationType' => $this->identificationType,
            'identificationNumber' => $this->identificationNumber,
            'identificationCountryCode' => $this->identificationCountryCode,
            'identificationExpirationDate' => $this->identificationExpirationDate,
            'nationality' => $this->nationality,
            'countryOfBirth' => $this->countryOfBirth,
            'fundSource' => $this->fundSource,
            'participantId' => $this->participantId,
            'additionalMessage' => $this->additionalMessage,
            'transactionReason' => $this->transactionReason,
            'claimCode' => $this->claimCode,
        ], fn ($value) => $value !== null);
    }
}
