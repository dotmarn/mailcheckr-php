<?php

namespace Dotmarn\MailCheckr;

use InvalidArgumentException;

final class VerificationResult
{
    /** Create a result from the API data, which must include an ID and state. */
    public function __construct(private readonly array $data)
    {
        if (! isset($data['id'], $data['state']) || ! is_string($data['id']) || ! is_string($data['state'])) {
            throw new InvalidArgumentException('A verification result requires an ID and state.');
        }
    }

    /** Return the verification ID used to retrieve an updated result. */
    public function id(): string
    {
        return $this->data['id'];
    }

    /** Return the verification lifecycle state, such as queued or completed. */
    public function state(): string
    {
        return $this->data['state'];
    }

    /** Return the delivery status when present, or null while it is unavailable. */
    public function status(): ?string
    {
        return isset($this->data['status']) && is_string($this->data['status'])
            ? $this->data['status']
            : null;
    }

    /** Determine whether verification has completed. */
    public function isCompleted(): bool
    {
        return $this->state() === 'completed';
    }

    /** Determine whether verification is queued, processing, or scheduled for retry. */
    public function isPending(): bool
    {
        return in_array($this->state(), ['queued', 'processing', 'retry_scheduled'], true);
    }

    /** Determine whether verification ended in the failed state. */
    public function isFailed(): bool
    {
        return $this->state() === 'failed';
    }

    /** Determine whether a completed verification marked the address deliverable. */
    public function isDeliverable(): bool
    {
        return $this->isCompleted() && $this->status() === 'deliverable';
    }

    /** Determine whether a completed verification marked the address undeliverable. */
    public function isUndeliverable(): bool
    {
        return $this->isCompleted() && $this->status() === 'undeliverable';
    }

    /** Determine whether a completed verification marked the address risky. */
    public function isRisky(): bool
    {
        return $this->isCompleted() && $this->status() === 'risky';
    }

    /** Determine whether a completed verification has an unknown delivery status. */
    public function isUnknown(): bool
    {
        return $this->isCompleted() && $this->status() === 'unknown';
    }

    /** Return the original API data as an array. */
    public function toArray(): array
    {
        return $this->data;
    }
}
