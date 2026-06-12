<?php

namespace Database\Factories;

use App\Enums\ScanStatus;
use App\Enums\Verdict;
use App\Models\ScanHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScanHistory>
 */
class ScanHistoryFactory extends Factory
{
    protected $model = ScanHistory::class;

    public function definition(): array
    {
        $malicious = $this->faker->numberBetween(0, 5);
        $harmless = $this->faker->numberBetween(60, 70);
        $suspicious = $this->faker->numberBetween(0, 2);
        $undetected = $this->faker->numberBetween(0, 5);
        $timeout = $this->faker->numberBetween(0, 1);
        $total = $malicious + $harmless + $suspicious + $undetected + $timeout;

        return [
            'type' => 'url',
            'input_value' => $this->faker->url(),
            'file_hash' => null,
            'malicious_count' => $malicious,
            'total_engines' => $total,
            'ai_explanation' => '[🟢 AMAN] Link terlihat aman berdasarkan '.$total.' mesin pemindai.',
            'status' => ScanStatus::Completed,
            'verdict' => Verdict::fromStats($malicious, $total),
            'result_json' => [
                'stats' => [
                    'malicious' => $malicious,
                    'suspicious' => $suspicious,
                    'harmless' => $harmless,
                    'undetected' => $undetected,
                    'timeout' => $timeout,
                ],
            ],
            'expires_at' => now()->addDay(),
        ];
    }

    public function url(?string $url = null): static
    {
        return $this->state(fn () => [
            'type' => 'url',
            'input_value' => $url ?? $this->faker->url(),
            'file_hash' => null,
        ]);
    }

    public function file(?string $name = null, ?string $hash = null): static
    {
        return $this->state(fn () => [
            'type' => 'file',
            'input_value' => $name ?? $this->faker->fileName(),
            'file_hash' => $hash ?? hash('sha256', $this->faker->uuid()),
        ]);
    }

    public function completed(int $malicious = 0, int $total = 70): static
    {
        return $this->state(fn () => [
            'status' => ScanStatus::Completed,
            'malicious_count' => $malicious,
            'total_engines' => $total,
            'verdict' => Verdict::fromStats($malicious, $total),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => ScanStatus::Pending,
            'malicious_count' => 0,
            'total_engines' => 0,
            'verdict' => null,
        ]);
    }

    public function failed(string $message = 'Unknown error'): static
    {
        return $this->state(fn () => [
            'status' => ScanStatus::Failed,
            'malicious_count' => 0,
            'total_engines' => 0,
            'verdict' => null,
            'result_json' => ['error' => $message],
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => ScanStatus::Completed,
            'expires_at' => now()->subDay(),
        ]);
    }
}
