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
        ?string $action,
        string $expectedMethod,
        ?string $expectedActionMethod
    ): void {
        $data = ['id' => 'verification-1', 'state' => $state, 'status' => $status, 'action' => $action, 'checks' => []];
        $result = new VerificationResult($data);

        $this->assertSame('verification-1', $result->id());
        $this->assertSame($state, $result->state());
        $this->assertSame($status, $result->status());
        $this->assertSame($action, $result->action());
        $this->assertSame($data, $result->toArray());

        foreach (['isDeliverable', 'isUndeliverable', 'isRisky', 'isUnknown', 'isPending', 'isFailed'] as $method) {
            $this->assertSame($method === $expectedMethod, $result->{$method}(), $method);
        }

        foreach (['shouldSend', 'shouldReview', 'shouldReject'] as $method) {
            $this->assertSame($method === $expectedActionMethod, $result->{$method}(), $method);
        }

        $this->assertSame($state === 'completed', $result->isCompleted());
    }

    public function test_action_is_null_when_missing_or_not_a_string(): void
    {
        $this->assertNull((new VerificationResult(['id' => 'v', 'state' => 'completed']))->action());
        $this->assertNull((new VerificationResult(['id' => 'v', 'state' => 'completed', 'action' => 1]))->action());
    }

    public static function resultStates(): array
    {
        return [
            'deliverable' => ['completed', 'deliverable', 'send', 'isDeliverable', 'shouldSend'],
            'undeliverable' => ['completed', 'undeliverable', 'reject', 'isUndeliverable', 'shouldReject'],
            'risky' => ['completed', 'risky', 'review', 'isRisky', 'shouldReview'],
            'unknown' => ['completed', 'unknown', 'review', 'isUnknown', 'shouldReview'],
            'queued' => ['queued', null, null, 'isPending', null],
            'processing' => ['processing', null, null, 'isPending', null],
            'retry scheduled' => ['retry_scheduled', null, null, 'isPending', null],
            'failed' => ['failed', null, null, 'isFailed', null],
            'premature status' => ['queued', 'deliverable', 'send', 'isPending', null],
        ];
    }
}
