<?php

namespace App\Services;

use App\Utils\Logger;

/**
 * PageClassifier Service
 * 
 * Orchestrates page scraping and topic extraction to classify any given URL.
 * This is the main service that coordinates PageScraper and TopicExtractor.
 */
class PageClassifier
{
    protected $scraper;
    protected $extractor;

    public function __construct()
    {
        $this->scraper = new PageScraper();
        $this->extractor = new TopicExtractor();
    }

    /**
     * Classify a page by URL and return relevant topics
     * 
     * @param string $url The URL to classify
     * @param int $topicLimit Maximum number of topics to return
     * @return array Classification results with topics
     */
    public function classify($url, $topicLimit = 10)
    {
        try {
            // Validate URL
            if (!$this->isValidUrl($url)) {
                throw new \InvalidArgumentException('Invalid URL provided');
            }

            Logger::info('Classifying page', ['url' => $url]);

            // Step 1: Scrape the page
            $startTime = microtime(true);
            $pageData = $this->scraper->scrape($url);
            $scrapeTime = microtime(true) - $startTime;

            Logger::info('Page scraped successfully', [
                'url' => $url,
                'time' => round($scrapeTime, 2) . 's',
            ]);

            // Step 2: Extract topics using custom density analysis
            $startTime = microtime(true);
            $allTopics = $this->extractor->extractTopics($pageData);
            $extractionTime = microtime(true) - $startTime;

            Logger::info('Topics extracted successfully', [
                'url' => $url,
                'topics_found' => count($allTopics),
                'time' => round($extractionTime, 2) . 's',
            ]);

            // Step 3: Format and limit results
            $topics = array_slice($allTopics, 0, $topicLimit);
            $topicList = array_map(function($topic) {
                return $topic['topic'];
            }, $topics);

            // Build result
            $result = [
                'success' => true,
                'url' => $url,
                'page_title' => $pageData['title'] ?? '',
                'topics' => $topicList,
                'topics_detailed' => $topics, // Include scores and frequencies
                'metadata' => [
                    'scrape_time' => round($scrapeTime, 3),
                    'extraction_time' => round($extractionTime, 3),
                    'total_time' => round($scrapeTime + $extractionTime, 3),
                    'topics_found' => count($allTopics),
                    'topics_returned' => count($topicList),
                ],
            ];

            return $result;

        } catch (\Exception $e) {
            Logger::error('Page classification failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'url' => $url,
                'error' => $e->getMessage(),
                'topics' => [],
            ];
        }
    }

    /**
     * Batch classify multiple URLs
     * 
     * @param array $urls Array of URLs to classify
     * @param int $topicLimit Maximum number of topics per URL
     * @return array Results for all URLs
     */
    public function classifyBatch($urls, $topicLimit = 10)
    {
        $results = [];

        foreach ($urls as $url) {
            $results[] = $this->classify($url, $topicLimit);
            
            // Small delay to be respectful to servers
            usleep(500000); // 0.5 seconds
        }

        return [
            'success' => true,
            'total_urls' => count($urls),
            'results' => $results,
        ];
    }

    /**
     * Validate URL format
     */
    protected function isValidUrl($url)
    {
        // Basic URL validation
        if (empty($url)) {
            return false;
        }

        // Check if it's a valid URL format
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        // Check if it starts with http or https
        if (!preg_match('/^https?:\/\//i', $url)) {
            return false;
        }

        return true;
    }

    /**
     * Get summary statistics for a classification result
     */
    public function getSummary($classificationResult)
    {
        if (!$classificationResult['success']) {
            return [
                'status' => 'failed',
                'error' => $classificationResult['error'] ?? 'Unknown error',
            ];
        }

        $topics = $classificationResult['topics_detailed'] ?? [];
        
        $phraseCount = 0;
        $keywordCount = 0;
        $avgScore = 0;
        
        foreach ($topics as $topic) {
            if ($topic['type'] === 'phrase') {
                $phraseCount++;
            } else {
                $keywordCount++;
            }
            $avgScore += $topic['score'];
        }

        if (count($topics) > 0) {
            $avgScore = $avgScore / count($topics);
        }

        return [
            'status' => 'success',
            'page_title' => $classificationResult['page_title'],
            'total_topics' => count($topics),
            'phrase_count' => $phraseCount,
            'keyword_count' => $keywordCount,
            'average_score' => round($avgScore, 2),
            'processing_time' => $classificationResult['metadata']['total_time'] ?? 0,
        ];
    }
}

