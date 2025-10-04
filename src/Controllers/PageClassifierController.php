<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\PageClassifier;

/**
 * PageClassifierController
 * 
 * Handles HTTP requests for page classification.
 * Provides API endpoints to classify URLs and extract relevant topics.
 */
class PageClassifierController extends Controller
{
    protected $classifier;

    public function __construct()
    {
        $this->classifier = new PageClassifier();
    }

    /**
     * Classify a single URL
     * 
     * GET /api/classify?url=https://example.com
     * POST /api/classify with JSON body: {"url": "https://example.com"}
     */
    public function classify()
    {
        // Get URL from query string or POST data
        $url = $_GET['url'] ?? null;
        
        if (!$url && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $url = $input['url'] ?? null;
        }

        // Validate input
        if (empty($url)) {
            $this->json([
                'success' => false,
                'error' => 'URL parameter is required',
                'usage' => [
                    'GET' => '/api/classify?url=https://example.com',
                    'POST' => '/api/classify with JSON body: {"url": "https://example.com"}',
                ],
            ], 400);
        }

        // Get topic limit (optional)
        $topicLimit = (int) ($_GET['limit'] ?? $input['limit'] ?? 10);
        $topicLimit = max(1, min($topicLimit, 50)); // Clamp between 1 and 50

        // Classify the page
        $result = $this->classifier->classify($url, $topicLimit);

        // Return appropriate status code
        $statusCode = $result['success'] ? 200 : 500;
        $this->json($result, $statusCode);
    }

    /**
     * Classify multiple URLs in batch
     * 
     * POST /api/classify/batch with JSON body:
     * {
     *   "urls": ["https://example1.com", "https://example2.com"],
     *   "limit": 10
     * }
     */
    public function classifyBatch()
    {
        // Only allow POST for batch requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json([
                'success' => false,
                'error' => 'This endpoint only accepts POST requests',
            ], 405);
        }

        // Get input data
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['urls']) || !is_array($input['urls'])) {
            $this->json([
                'success' => false,
                'error' => 'URLs array is required',
                'usage' => 'POST /api/classify/batch with JSON body: {"urls": ["url1", "url2"], "limit": 10}',
            ], 400);
        }

        $urls = $input['urls'];
        $topicLimit = (int) ($input['limit'] ?? 10);
        $topicLimit = max(1, min($topicLimit, 50));

        // Limit number of URLs to prevent abuse
        if (count($urls) > 10) {
            $this->json([
                'success' => false,
                'error' => 'Maximum 10 URLs allowed per batch request',
            ], 400);
        }

        // Classify all URLs
        $result = $this->classifier->classifyBatch($urls, $topicLimit);
        $this->json($result);
    }

    /**
     * Test endpoint with predefined URLs from the assignment
     * 
     * GET /api/classify/test
     */
    public function test()
    {
        // Predefined URLs from the assignment
        $testUrls = [
            'http://www.amazon.com/Cuisinart-CPT-122-Compact-2-Slice-Toaster/dp/B009GQ034C/ref=sr_1_1?s=kitchen&ie=UTF8&qid=1431620315&sr=1-1&keywords=toaster',
            'http://blog.rei.com/camp/how-to-introduce-your-indoorsy-friend-to-the-outdoors/',
            'http://www.cnn.com/2013/06/10/politics/edward-snowden-profile/',
        ];

        // Get which test to run (or all)
        $testIndex = $_GET['index'] ?? 'all';

        if ($testIndex === 'all') {
            // Run all tests
            $results = [];
            foreach ($testUrls as $index => $url) {
                $results[$index] = $this->classifier->classify($url, 10);
            }

            $this->json([
                'success' => true,
                'test' => 'all',
                'results' => $results,
            ]);
        } else {
            // Run specific test
            $index = (int) $testIndex;
            
            if (!isset($testUrls[$index])) {
                $this->json([
                    'success' => false,
                    'error' => 'Invalid test index',
                    'available_indexes' => [0, 1, 2],
                ], 400);
            }

            $result = $this->classifier->classify($testUrls[$index], 10);
            $this->json([
                'success' => true,
                'test_index' => $index,
                'result' => $result,
            ]);
        }
    }

    /**
     * API documentation/help endpoint
     * 
     * GET /api/classify/help
     */
    public function help()
    {
        $this->json([
            'api_name' => 'Page Classification API',
            'version' => '1.0',
            'description' => 'Classifies web pages and returns relevant topics using custom density analysis',
            'endpoints' => [
                [
                    'method' => 'GET/POST',
                    'path' => '/api/classify',
                    'description' => 'Classify a single URL',
                    'parameters' => [
                        'url' => 'Required. The URL to classify',
                        'limit' => 'Optional. Number of topics to return (default: 10, max: 50)',
                    ],
                    'examples' => [
                        'GET /api/classify?url=https://example.com&limit=10',
                        'POST /api/classify with body: {"url": "https://example.com", "limit": 10}',
                    ],
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/classify/batch',
                    'description' => 'Classify multiple URLs in batch',
                    'parameters' => [
                        'urls' => 'Required. Array of URLs to classify',
                        'limit' => 'Optional. Number of topics per URL (default: 10)',
                    ],
                    'example' => 'POST /api/classify/batch with body: {"urls": ["url1", "url2"], "limit": 10}',
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/classify/test',
                    'description' => 'Test with predefined assignment URLs',
                    'parameters' => [
                        'index' => 'Optional. Test index (0-2) or "all" (default: all)',
                    ],
                    'examples' => [
                        'GET /api/classify/test (runs all tests)',
                        'GET /api/classify/test?index=0 (runs specific test)',
                    ],
                ],
            ],
            'response_format' => [
                'success' => 'Boolean indicating if classification succeeded',
                'url' => 'The URL that was classified',
                'page_title' => 'The page title',
                'topics' => 'Array of topic strings',
                'topics_detailed' => 'Array of topics with scores and frequencies',
                'metadata' => 'Processing time and statistics',
            ],
        ]);
    }
}

