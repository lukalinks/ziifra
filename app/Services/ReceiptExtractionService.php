<?php

namespace App\Services;

use App\Data\ExtractedReceiptData;
use App\Enums\ExpenseCategory;
use App\Exceptions\ReceiptExtractionException;
use App\Models\Organization;
use App\Support\ExpenseReceiptStorage;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReceiptExtractionService
{
    public function isAvailable(): bool
    {
        return filled(config('services.openai.key'));
    }

    public function extract(UploadedFile $file, Organization $organization): ExtractedReceiptData
    {
        if (! $this->isAvailable()) {
            throw new ReceiptExtractionException(__('expenses.scan_not_configured'));
        }

        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'pdf' || $file->getMimeType() === 'application/pdf') {
            throw new ReceiptExtractionException(__('expenses.scan_pdf_unsupported'));
        }

        if (! in_array($extension, ExpenseReceiptStorage::ALLOWED_MIMES, true) || $extension === 'pdf') {
            throw new ReceiptExtractionException(__('expenses.scan_unsupported_format'));
        }

        $response = Http::withToken((string) config('services.openai.key'))
            ->timeout(45)
            ->acceptJson()
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model', 'gpt-4o-mini'),
                'temperature' => 0.1,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You extract structured expense claim data from receipt images. Respond with JSON only.',
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $this->prompt($organization),
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => sprintf(
                                        'data:%s;base64,%s',
                                        $file->getMimeType() ?? 'image/jpeg',
                                        base64_encode($file->get()),
                                    ),
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            Log::warning('Receipt extraction API failed', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new ReceiptExtractionException(__('expenses.scan_failed'));
        }

        $content = data_get($response->json(), 'choices.0.message.content');

        if (! is_string($content) || $content === '') {
            throw new ReceiptExtractionException(__('expenses.scan_failed'));
        }

        /** @var array<string, mixed>|null $payload */
        $payload = json_decode($content, true);

        if (! is_array($payload)) {
            throw new ReceiptExtractionException(__('expenses.scan_failed'));
        }

        return $this->normalize($payload);
    }

    protected function prompt(Organization $organization): string
    {
        $categories = collect(ExpenseCategory::cases())
            ->map(fn (ExpenseCategory $category) => $category->value.': '.$category->label())
            ->implode(', ');

        $currency = $organization->currency ?? 'EUR';

        return <<<PROMPT
Read this receipt image and extract expense claim fields.

Return JSON with these keys only:
- title (string|null): merchant or short description, max 120 characters
- amount (number|null): total amount paid including tax
- expense_date (string|null): date in YYYY-MM-DD format
- category (string|null): one of {$categories}
- notes (string|null): receipt number, tax details, or other useful context

Rules:
- Use null when a field cannot be determined confidently.
- Prefer the final total amount, not subtotals.
- expense_date must not be in the future.
- If currency is visible and differs from {$currency}, mention it in notes.
- category must be one of the allowed values exactly.
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function normalize(array $payload): ExtractedReceiptData
    {
        $title = $this->stringValue($payload['title'] ?? null);
        $title = $title !== null ? mb_substr($title, 0, 255) : null;

        $amount = $this->amountValue($payload['amount'] ?? null);

        $expenseDate = $this->dateValue($payload['expense_date'] ?? null);

        $category = $this->categoryValue($payload['category'] ?? null);

        $notes = $this->stringValue($payload['notes'] ?? null);
        $notes = $notes !== null ? mb_substr($notes, 0, 2000) : null;

        $extracted = new ExtractedReceiptData(
            title: $title,
            amount: $amount,
            expenseDate: $expenseDate,
            category: $category,
            notes: $notes,
        );

        if (! $extracted->hasAnyField()) {
            throw new ReceiptExtractionException(__('expenses.scan_no_data'));
        }

        return $extracted;
    }

    protected function stringValue(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    protected function amountValue(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            $amount = round((float) $value, 2);

            return $amount > 0 ? $amount : null;
        }

        if (is_string($value)) {
            $normalized = preg_replace('/[^\d.,-]/', '', $value) ?? '';
            $normalized = str_replace(',', '.', $normalized);

            if (is_numeric($normalized)) {
                $amount = round((float) $normalized, 2);

                return $amount > 0 ? $amount : null;
            }
        }

        return null;
    }

    protected function dateValue(mixed $value): ?string
    {
        $string = $this->stringValue($value);

        if ($string === null) {
            return null;
        }

        try {
            $date = Carbon::parse($string)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        if ($date->isFuture()) {
            return null;
        }

        return $date->toDateString();
    }

    protected function categoryValue(mixed $value): ?ExpenseCategory
    {
        $string = $this->stringValue($value);

        if ($string === null) {
            return null;
        }

        return ExpenseCategory::tryFrom(strtolower($string));
    }
}
