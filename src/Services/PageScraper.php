<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * PageScraper Service
 * 
 * Handles fetching and parsing HTML content from URLs.
 * Uses Guzzle for HTTP requests and Symfony DomCrawler for HTML parsing.
 */
class PageScraper
{
    protected $client;
    protected $timeout;
    protected $userAgent;

    public function __construct()
    {
        $this->timeout = 30;
        $this->userAgent = 'Mozilla/5.0 (compatible; BrightedgeBot/1.0)';
        
        $this->client = new Client([
            'timeout' => $this->timeout,
            'verify' => false, // Disable SSL verification for testing
            'headers' => [
                'User-Agent' => $this->userAgent,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
            ],
        ]);
    }

    /**
     * Fetch HTML content from a URL
     * 
     * @param string $url The URL to fetch
     * @return string The HTML content
     * @throws \Exception If the request fails
     */
    public function fetchHtml($url)
    {
        try {
            $response = $this->client->get($url);
            return (string) $response->getBody();
        } catch (GuzzleException $e) {
            throw new \Exception('Failed to fetch URL: ' . $e->getMessage());
        }
    }

    /**
     * Parse HTML content and extract structured data
     * 
     * @param string $html The HTML content to parse
     * @return array Structured data extracted from the HTML
     */
    public function parseHtml($html)
    {
        $crawler = new Crawler($html);
        
        $data = [
            'title' => $this->extractTitle($crawler),
            'meta_description' => $this->extractMetaDescription($crawler),
            'meta_keywords' => $this->extractMetaKeywords($crawler),
            'headings' => $this->extractHeadings($crawler),
            'body_text' => $this->extractBodyText($crawler),
            'links' => $this->extractLinks($crawler),
            'images' => $this->extractImages($crawler),
            'structured_content' => $this->extractStructuredContent($crawler),
        ];

        return $data;
    }

    /**
     * Fetch and parse a URL in one step
     * 
     * @param string $url The URL to fetch and parse
     * @return array Structured data from the page
     */
    public function scrape($url)
    {
        $html = $this->fetchHtml($url);
        return $this->parseHtml($html);
    }

    /**
     * Extract page title
     */
    protected function extractTitle(Crawler $crawler)
    {
        try {
            return $crawler->filter('title')->text();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Extract meta description
     */
    protected function extractMetaDescription(Crawler $crawler)
    {
        try {
            return $crawler->filter('meta[name="description"]')->attr('content');
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Extract meta keywords
     */
    protected function extractMetaKeywords(Crawler $crawler)
    {
        try {
            return $crawler->filter('meta[name="keywords"]')->attr('content');
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Extract all headings (h1-h6)
     */
    protected function extractHeadings(Crawler $crawler)
    {
        $headings = [];
        
        for ($i = 1; $i <= 6; $i++) {
            try {
                $crawler->filter("h{$i}")->each(function (Crawler $node) use (&$headings, $i) {
                    $text = trim($node->text());
                    if (!empty($text)) {
                        $headings["h{$i}"][] = $text;
                    }
                });
            } catch (\Exception $e) {
                // Skip if no headings found
            }
        }

        return $headings;
    }

    /**
     * Extract body text (excluding scripts and styles)
     */
    protected function extractBodyText(Crawler $crawler)
    {
        try {
            // Remove script and style tags
            $crawler->filter('script, style, nav, footer, header')->each(function (Crawler $node) {
                foreach ($node as $child) {
                    $child->parentNode->removeChild($child);
                }
            });

            // Get text from body
            $text = $crawler->filter('body')->text();
            
            // Clean up whitespace
            $text = preg_replace('/\s+/', ' ', $text);
            return trim($text);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Extract links from the page
     */
    protected function extractLinks(Crawler $crawler)
    {
        $links = [];
        
        try {
            $crawler->filter('a[href]')->each(function (Crawler $node) use (&$links) {
                $href = $node->attr('href');
                $text = trim($node->text());
                
                if (!empty($href) && !empty($text)) {
                    $links[] = [
                        'href' => $href,
                        'text' => $text,
                    ];
                }
            });
        } catch (\Exception $e) {
            // Skip if no links found
        }

        return $links;
    }

    /**
     * Extract images from the page
     */
    protected function extractImages(Crawler $crawler)
    {
        $images = [];
        
        try {
            $crawler->filter('img')->each(function (Crawler $node) use (&$images) {
                $src = $node->attr('src');
                $alt = $node->attr('alt');
                
                if (!empty($src)) {
                    $images[] = [
                        'src' => $src,
                        'alt' => $alt ?? '',
                    ];
                }
            });
        } catch (\Exception $e) {
            // Skip if no images found
        }

        return $images;
    }

    /**
     * Extract structured content (product info, article data, etc.)
     */
    protected function extractStructuredContent(Crawler $crawler)
    {
        $structured = [];

        // Extract product information (e.g., Amazon)
        try {
            $productTitle = $crawler->filter('#productTitle, .product-title, [data-testid="product-title"]')->text();
            if (!empty($productTitle)) {
                $structured['product_title'] = trim($productTitle);
            }
        } catch (\Exception $e) {}

        // Extract article title
        try {
            $articleTitle = $crawler->filter('article h1, .article-title, [itemprop="headline"]')->text();
            if (!empty($articleTitle)) {
                $structured['article_title'] = trim($articleTitle);
            }
        } catch (\Exception $e) {}

        // Extract breadcrumbs
        try {
            $breadcrumbs = [];
            $crawler->filter('.breadcrumb a, [aria-label="breadcrumb"] a, #breadcrumbs a')->each(function (Crawler $node) use (&$breadcrumbs) {
                $text = trim($node->text());
                if (!empty($text)) {
                    $breadcrumbs[] = $text;
                }
            });
            if (!empty($breadcrumbs)) {
                $structured['breadcrumbs'] = $breadcrumbs;
            }
        } catch (\Exception $e) {}

        return $structured;
    }
}

