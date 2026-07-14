<?php

namespace App\Commerce\Fulfillment;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final readonly class ShippingAddress
{
    public function __construct(
        public string $fullName,
        public ?string $company,
        public string $addressLine1,
        public ?string $addressLine2,
        public string $city,
        public string $region,
        public string $postalCode,
        public string $countryCode,
        public ?string $phone,
        public ?string $deliveryInstructions,
    ) {}

    public static function fromInput(array $input): self
    {
        return new self(
            fullName: trim((string) Arr::get($input, 'full_name', '')),
            company: self::nullable(Arr::get($input, 'company')),
            addressLine1: trim((string) Arr::get($input, 'address_line_1', '')),
            addressLine2: self::nullable(Arr::get($input, 'address_line_2')),
            city: trim((string) Arr::get($input, 'city', '')),
            region: trim((string) Arr::get($input, 'region', '')),
            postalCode: trim((string) Arr::get($input, 'postal_code', '')),
            countryCode: strtoupper(trim((string) Arr::get($input, 'country_code', 'US'))),
            phone: self::nullable(Arr::get($input, 'phone')),
            deliveryInstructions: self::nullable(Arr::get($input, 'delivery_instructions')),
        );
    }

    public function isComplete(): bool
    {
        return $this->fullName !== ''
            && $this->addressLine1 !== ''
            && $this->city !== ''
            && $this->region !== ''
            && $this->postalCode !== ''
            && strlen($this->countryCode) === 2;
    }

    public function toArray(): array
    {
        return [
            'version' => 1,
            'full_name' => $this->fullName,
            'company' => $this->company,
            'address_line_1' => $this->addressLine1,
            'address_line_2' => $this->addressLine2,
            'city' => $this->city,
            'region' => $this->region,
            'postal_code' => $this->postalCode,
            'country_code' => $this->countryCode,
            'phone' => $this->phone,
            'delivery_instructions' => $this->deliveryInstructions,
        ];
    }

    public function hash(): string
    {
        return hash('sha256', json_encode($this->toArray(), JSON_THROW_ON_ERROR));
    }

    private static function nullable(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }
}
