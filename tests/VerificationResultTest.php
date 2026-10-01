<?php

use Dotmarn\MailCheckr\VerificationResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VerificationResultTest extends TestCase
{
    #[DataProvider('resultStates')]
    public function test_status_helpers_are_true_only_for_completed_matching_results(
        string $state,
        ?string $status,
        string $expectedMethod
    ): void {
        $data = ['id' => 'verification-1', 'state' => $state, 'status' => $status, 'checks' => []];
        $result = new VerificationResult($data);

        $this->assertSame('verification-1', $result->id());
        $this->assertSame($state, $result->state());
        $this->assertSame($status, $result->status());
        $this->assertSame($data, $result->toArray());

        foreach (['isDeliverable', 'isUndeliverable', 'isRisky', 'isUnknown', 'isPending', 'isFailed'] as $method) {
            $this->assertSame($method === $expectedMethod, $result->{$method}(), $method);
        }

        $this->assertSame($state === 'completed', $result->isCompleted());
    }

    public static function resultStates(): array
    {
        return [
            'deliverable' => ['completed', 'deliverable', 'isDeliverable'],
            'undeliverable' => ['completed', 'undeliverable', 'isUndeliverable'],
            'risky' => ['completed', 'risky', 'isRisky'],
            'unknown' => ['completed', 'unknown', 'isUnknown'],
            'queued' => ['queued', null, 'isPending'],
            'processing' => ['processing', null, 'isPending'],
            'retry scheduled' => ['retry_scheduled', null, 'isPending'],
            'failed' => ['failed', null, 'isFailed'],
            'premature status' => ['queued', 'deliverable', 'isPending'],
        ];
    }
}
