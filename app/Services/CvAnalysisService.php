<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class CvAnalysisService
{
    public function analyze(Customer $customer): array
    {
        $apiKey = config('services.openai.key');

        if (blank($apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        if (blank($customer->cv_path)) {
            throw new RuntimeException('No CV file is attached to this customer.');
        }

        if (! Storage::disk('local')->exists($customer->cv_path)) {
            throw new RuntimeException('CV file was not found in storage.');
        }

        $customer->update([
            'cv_analysis_status' => 'processing',
        ]);

        try {
            $file = Storage::disk('local')->get($customer->cv_path);
            $filename = $customer->cv_original_name ?: basename($customer->cv_path);

            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout(180)
                ->post('https://api.openai.com/v1/responses', [
                    'model' => config('services.openai.model', 'gpt-5.6-sol'),
                    'store' => false,
                    'input' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'input_file',
                                    'filename' => $filename,
                                    'file_data' => base64_encode($file),
                                ],
                                [
                                    'type' => 'input_text',
                                    'text' => <<<'PROMPT'
Analyze this CV carefully.

Rules:
- Extract only information actually present in the CV.
- Do not invent, assume, or guess missing information.
- Preserve Arabic text in Arabic and English text in English.
- Return null for unavailable scalar values and [] for unavailable lists.
- Extract all available education, work experience, skills, languages, certifications, and courses.
- Do not add ATS scores, recommendations, or commentary.
- years_of_experience must be a number only when it can reasonably be determined; otherwise null.

Return only data matching the required JSON schema.
PROMPT,
                                ],
                            ],
                        ],
                    ],
                    'text' => [
                        'format' => [
                            'type' => 'json_schema',
                            'name' => 'cv_analysis',
                            'strict' => true,
                            'schema' => $this->schema(),
                        ],
                    ],
                ]);

            if ($response->failed()) {
                $message = data_get($response->json(), 'error.message', $response->body());

                throw new RuntimeException(
                    'OpenAI request failed (' . $response->status() . '): ' . $message
                );
            }

            $text = $this->extractOutputText($response->json());

            if (blank($text)) {
                throw new RuntimeException('OpenAI returned an empty analysis.');
            }

            $data = json_decode($text, true, 512, JSON_THROW_ON_ERROR);

            $updates = [
                'cv_ai_data' => $data,
                'cv_analysis_status' => 'completed',
                'cv_analyzed_at' => now(),
            ];

            foreach (['full_name', 'phone', 'email', 'city', 'country'] as $field) {
                if (blank($customer->{$field}) && filled($data[$field] ?? null)) {
                    $updates[$field] = $data[$field];
                }
            }

            $customer->update($updates);

            return $data;
        } catch (Throwable $e) {
            $customer->update([
                'cv_analysis_status' => 'failed',
            ]);

            throw $e;
        }
    }

    private function extractOutputText(array $response): ?string
    {
        foreach ($response['output'] ?? [] as $output) {
            if (($output['type'] ?? null) !== 'message') {
                continue;
            }

            foreach ($output['content'] ?? [] as $content) {
                if (
                    ($content['type'] ?? null) === 'output_text'
                    && filled($content['text'] ?? null)
                ) {
                    return $content['text'];
                }
            }
        }

        return null;
    }

    private function schema(): array
    {
        $nullableString = ['type' => ['string', 'null']];
        $nullableNumber = ['type' => ['number', 'null']];

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => [
                'full_name',
                'phone',
                'email',
                'city',
                'country',
                'job_title',
                'professional_summary',
                'years_of_experience',
                'education',
                'work_experience',
                'skills',
                'languages',
                'certifications',
                'courses',
                'linkedin',
            ],
            'properties' => [
                'full_name' => $nullableString,
                'phone' => $nullableString,
                'email' => $nullableString,
                'city' => $nullableString,
                'country' => $nullableString,
                'job_title' => $nullableString,
                'professional_summary' => $nullableString,
                'years_of_experience' => $nullableNumber,
                'linkedin' => $nullableString,
                'education' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => [
                            'degree',
                            'field_of_study',
                            'institution',
                            'location',
                            'start_date',
                            'end_date',
                            'description',
                        ],
                        'properties' => [
                            'degree' => $nullableString,
                            'field_of_study' => $nullableString,
                            'institution' => $nullableString,
                            'location' => $nullableString,
                            'start_date' => $nullableString,
                            'end_date' => $nullableString,
                            'description' => $nullableString,
                        ],
                    ],
                ],
                'work_experience' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => [
                            'job_title',
                            'company',
                            'location',
                            'start_date',
                            'end_date',
                            'description',
                            'achievements',
                        ],
                        'properties' => [
                            'job_title' => $nullableString,
                            'company' => $nullableString,
                            'location' => $nullableString,
                            'start_date' => $nullableString,
                            'end_date' => $nullableString,
                            'description' => $nullableString,
                            'achievements' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
                'skills' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'languages' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['language', 'level'],
                        'properties' => [
                            'language' => ['type' => 'string'],
                            'level' => $nullableString,
                        ],
                    ],
                ],
                'certifications' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['name', 'issuer', 'date'],
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'issuer' => $nullableString,
                            'date' => $nullableString,
                        ],
                    ],
                ],
                'courses' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['name', 'provider', 'date'],
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'provider' => $nullableString,
                            'date' => $nullableString,
                        ],
                    ],
                ],
            ],
        ];
    }
}
