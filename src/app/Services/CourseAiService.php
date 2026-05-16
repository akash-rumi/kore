<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CourseAiService
{
    private ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.key');
    }

    public function generateTopics(string $title, string $description, string $category, string $level): array
    {
        if (!$this->apiKey) {
            return $this->fallbackTopics($category);
        }

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type'      => 'application/json',
            ])->timeout(15)->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-haiku-4-5',
                'max_tokens' => 400,
                'messages'   => [
                    [
                        'role'    => 'user',
                        'content' => "Generate exactly 6 short course topic titles for the following course.\n\nCourse Title: {$title}\nDescription: {$description}\nCategory: {$category}\nLevel: {$level}\n\nReturn ONLY a valid JSON array of exactly 6 short strings. No explanation, no markdown, no extra text. Example format:\n[\"Topic One\", \"Topic Two\", \"Topic Three\", \"Topic Four\", \"Topic Five\", \"Topic Six\"]",
                    ],
                ],
            ]);

            if ($response->successful()) {
                $text = $response->json('content.0.text', '');
                preg_match('/\[.*?\]/s', $text, $matches);

                if (!empty($matches[0])) {
                    $topics = json_decode($matches[0], true);
                    if (is_array($topics) && count($topics) >= 3) {
                        return array_slice($topics, 0, 6);
                    }
                }
            }

            Log::warning('CourseAiService: API response could not be parsed.', ['status' => $response->status()]);
        } catch (\Throwable $e) {
            Log::warning('CourseAiService: API call failed, using fallback.', ['error' => $e->getMessage()]);
        }

        return $this->fallbackTopics($category);
    }

    public function generateDescription(string $title, string $category, string $level): string
    {
        if (!$this->apiKey) {
            return '';
        }

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type'      => 'application/json',
            ])->timeout(15)->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-haiku-4-5',
                'max_tokens' => 200,
                'messages'   => [
                    [
                        'role'    => 'user',
                        'content' => "Write a 2-sentence course description for a {$level} level {$category} course titled \"{$title}\". Be concise and compelling. Return only the description text, no extra formatting.",
                    ],
                ],
            ]);

            if ($response->successful()) {
                return trim($response->json('content.0.text', ''));
            }
        } catch (\Throwable $e) {
            Log::warning('CourseAiService: description generation failed.', ['error' => $e->getMessage()]);
        }

        return '';
    }

    private function fallbackTopics(string $category): array
    {
        $defaults = [
            'Backend'      => ['Introduction & Environment Setup', 'Core Language Concepts', 'Working with Databases', 'Authentication & Security', 'Building REST APIs', 'Deployment & Best Practices'],
            'Frontend'     => ['HTML & CSS Fundamentals', 'JavaScript Essentials', 'Component Architecture', 'State Management', 'Routing & Navigation', 'Testing & Deployment'],
            'Database'     => ['Database Fundamentals', 'Schema Design', 'Querying & Filtering', 'Indexing & Performance', 'Transactions & Data Integrity', 'Backup & Recovery'],
            'Design'       => ['Design Principles & Theory', 'Color & Typography', 'Layout & Composition', 'UI Component Design', 'Prototyping & Wireframing', 'Handoff & Documentation'],
            'DevOps'       => ['Linux & Shell Fundamentals', 'Docker & Containers', 'CI/CD Pipelines', 'Cloud Services Overview', 'Monitoring & Logging', 'Security Hardening'],
            'Mobile'       => ['Mobile Development Basics', 'UI & Navigation', 'State & Data Management', 'Device APIs & Permissions', 'Testing on Devices', 'Publishing to Stores'],
            'Data Science' => ['Python for Data Science', 'Data Wrangling & Cleaning', 'Exploratory Data Analysis', 'Machine Learning Basics', 'Model Evaluation', 'Deployment & Reporting'],
        ];

        return $defaults[$category]
            ?? ['Introduction', 'Core Concepts', 'Practical Application', 'Advanced Topics', 'Best Practices', 'Final Project'];
    }
}
