<?php

namespace App\Services;

/**
 * TopicExtractor Service
 * 
 * Implements custom density analysis and topic extraction algorithm.
 * This service analyzes text content to identify the most relevant topics/keywords.
 * 
 * NOTE: As per requirements, this does NOT use any 3rd party libraries for
 * density collection or analysis - all algorithms are custom-built.
 */
class TopicExtractor
{
    // Common English stop words to filter out
    protected $stopWords = [
        'a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 'from', 'has', 'he',
        'in', 'is', 'it', 'its', 'of', 'on', 'that', 'the', 'to', 'was', 'will',
        'with', 'the', 'this', 'but', 'they', 'have', 'had', 'what', 'when', 'where',
        'who', 'which', 'why', 'how', 'all', 'each', 'every', 'both', 'few', 'more',
        'most', 'other', 'some', 'such', 'no', 'nor', 'not', 'only', 'own', 'same',
        'so', 'than', 'too', 'very', 's', 't', 'can', 'will', 'just', 'don', 'should',
        'now', 'i', 'you', 'your', 'my', 'our', 'their', 'his', 'her', 'am', 'been',
        'being', 'having', 'does', 'did', 'doing', 'would', 'could', 'ought', 'im',
        'youre', 'hes', 'shes', 'its', 'were', 'theyre', 'ive', 'youve', 'weve',
        'theyve', 'id', 'youd', 'hed', 'shed', 'wed', 'theyd', 'ill', 'youll',
        'hell', 'shell', 'well', 'theyll', 'isnt', 'arent', 'wasnt', 'werent',
        'hasnt', 'havent', 'hadnt', 'doesnt', 'dont', 'didnt', 'wont', 'wouldnt',
        'shant', 'shouldnt', 'cant', 'cannot', 'couldnt', 'mustnt', 'lets', 'thats',
        'whos', 'whats', 'heres', 'theres', 'whens', 'wheres', 'whys', 'hows', 'get',
        'got', 'also', 'may', 'might', 'must', 'need', 'shall', 'go', 'going', 'gone',
        'make', 'made', 'making', 'see', 'seen', 'saw', 'one', 'two', 'three', 'four',
        'five', 'six', 'seven', 'eight', 'nine', 'ten', 'about', 'above', 'after',
        'again', 'against', 'among', 'any', 'because', 'before', 'below', 'between',
        'down', 'during', 'further', 'here', 'into', 'off', 'once', 'out', 'over',
        'then', 'there', 'these', 'those', 'through', 'under', 'until', 'up', 'upon',
        'while', 'within', 'without', 'yes', 'yet', 'new', 'amp', 'nbsp', 'if', 'or',
    ];

    protected $minWordLength = 2;
    protected $maxWordLength = 50;
    protected $minPhraseLength = 2;
    protected $maxPhraseLength = 5;

    /**
     * Extract topics from scraped page data
     * 
     * @param array $pageData Structured page data from PageScraper
     * @return array List of relevant topics with scores
     */
    public function extractTopics($pageData)
    {
        // Collect all text with different weights
        $weightedText = $this->collectWeightedText($pageData);
        
        // Extract n-grams (phrases) with scoring
        $phrases = $this->extractPhrases($weightedText);
        
        // Extract single keywords with scoring
        $keywords = $this->extractKeywords($weightedText);
        
        // Combine and rank topics
        $topics = $this->rankTopics($phrases, $keywords);
        
        return $topics;
    }

    /**
     * Collect text from different parts of the page with different weights
     * Higher weight = more important for topic extraction
     */
    protected function collectWeightedText($pageData)
    {
        $weightedText = [];

        // Title has highest weight
        if (!empty($pageData['title'])) {
            $weightedText[] = [
                'text' => $pageData['title'],
                'weight' => 10,
            ];
        }

        // Meta description
        if (!empty($pageData['meta_description'])) {
            $weightedText[] = [
                'text' => $pageData['meta_description'],
                'weight' => 8,
            ];
        }

        // Meta keywords
        if (!empty($pageData['meta_keywords'])) {
            $weightedText[] = [
                'text' => $pageData['meta_keywords'],
                'weight' => 7,
            ];
        }

        // H1 headings
        if (!empty($pageData['headings']['h1'])) {
            foreach ($pageData['headings']['h1'] as $h1) {
                $weightedText[] = [
                    'text' => $h1,
                    'weight' => 9,
                ];
            }
        }

        // H2 headings
        if (!empty($pageData['headings']['h2'])) {
            foreach ($pageData['headings']['h2'] as $h2) {
                $weightedText[] = [
                    'text' => $h2,
                    'weight' => 6,
                ];
            }
        }

        // H3 headings
        if (!empty($pageData['headings']['h3'])) {
            foreach ($pageData['headings']['h3'] as $h3) {
                $weightedText[] = [
                    'text' => $h3,
                    'weight' => 5,
                ];
            }
        }

        // Structured content (product titles, article titles, breadcrumbs)
        if (!empty($pageData['structured_content'])) {
            foreach ($pageData['structured_content'] as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $item) {
                        $weightedText[] = [
                            'text' => $item,
                            'weight' => 8,
                        ];
                    }
                } else {
                    $weightedText[] = [
                        'text' => $value,
                        'weight' => 8,
                    ];
                }
            }
        }

        // Link text (anchor text is often descriptive)
        if (!empty($pageData['links'])) {
            foreach (array_slice($pageData['links'], 0, 50) as $link) {
                if (!empty($link['text'])) {
                    $weightedText[] = [
                        'text' => $link['text'],
                        'weight' => 3,
                    ];
                }
            }
        }

        // Image alt text
        if (!empty($pageData['images'])) {
            foreach (array_slice($pageData['images'], 0, 20) as $image) {
                if (!empty($image['alt'])) {
                    $weightedText[] = [
                        'text' => $image['alt'],
                        'weight' => 4,
                    ];
                }
            }
        }

        // Body text (lower weight, but still important)
        if (!empty($pageData['body_text'])) {
            // Take first 5000 characters to avoid processing too much
            $bodyText = substr($pageData['body_text'], 0, 5000);
            $weightedText[] = [
                'text' => $bodyText,
                'weight' => 1,
            ];
        }

        return $weightedText;
    }

    /**
     * Extract and score phrases (n-grams) from weighted text
     * Custom density analysis implementation
     */
    protected function extractPhrases($weightedText)
    {
        $phrases = [];

        foreach ($weightedText as $item) {
            $text = $item['text'];
            $weight = $item['weight'];

            // Tokenize text
            $words = $this->tokenize($text);

            // Generate n-grams (2 to 5 words)
            for ($n = $this->minPhraseLength; $n <= $this->maxPhraseLength; $n++) {
                $nGrams = $this->generateNGrams($words, $n);

                foreach ($nGrams as $phrase) {
                    $phraseKey = strtolower($phrase);
                    
                    if (!isset($phrases[$phraseKey])) {
                        $phrases[$phraseKey] = [
                            'phrase' => $phrase,
                            'score' => 0,
                            'frequency' => 0,
                        ];
                    }

                    // Increase score based on weight
                    $phrases[$phraseKey]['score'] += $weight;
                    $phrases[$phraseKey]['frequency']++;
                }
            }
        }

        // Filter out low-quality phrases
        $phrases = array_filter($phrases, function($phraseData) {
            return $phraseData['frequency'] >= 1 && 
                   strlen($phraseData['phrase']) >= 5;
        });

        return $phrases;
    }

    /**
     * Extract and score single keywords from weighted text
     */
    protected function extractKeywords($weightedText)
    {
        $keywords = [];

        foreach ($weightedText as $item) {
            $text = $item['text'];
            $weight = $item['weight'];

            // Tokenize text
            $words = $this->tokenize($text);

            foreach ($words as $word) {
                $word = strtolower($word);

                // Skip stop words and short/long words
                if ($this->isStopWord($word) || 
                    strlen($word) < $this->minWordLength || 
                    strlen($word) > $this->maxWordLength) {
                    continue;
                }

                if (!isset($keywords[$word])) {
                    $keywords[$word] = [
                        'keyword' => $word,
                        'score' => 0,
                        'frequency' => 0,
                    ];
                }

                $keywords[$word]['score'] += $weight;
                $keywords[$word]['frequency']++;
            }
        }

        return $keywords;
    }

    /**
     * Rank and combine phrases and keywords to generate final topic list
     */
    protected function rankTopics($phrases, $keywords)
    {
        $topics = [];

        // Add phrases as topics (prefer phrases over single words)
        foreach ($phrases as $phraseData) {
            $topics[] = [
                'topic' => $this->cleanTopic($phraseData['phrase']),
                'score' => $phraseData['score'] * 1.5, // Boost phrase scores
                'frequency' => $phraseData['frequency'],
                'type' => 'phrase',
            ];
        }

        // Add keywords as topics
        foreach ($keywords as $keywordData) {
            $topics[] = [
                'topic' => $keywordData['keyword'],
                'score' => $keywordData['score'],
                'frequency' => $keywordData['frequency'],
                'type' => 'keyword',
            ];
        }

        // Sort by score (descending)
        usort($topics, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Remove duplicates and near-duplicates
        $topics = $this->removeDuplicates($topics);

        // Return top topics
        return array_slice($topics, 0, 20);
    }

    /**
     * Tokenize text into words
     */
    protected function tokenize($text)
    {
        // Remove special characters (keep letters, numbers, spaces, hyphens)
        $text = preg_replace('/[^\w\s\-]/u', ' ', $text);
        
        // Split by whitespace into words
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Filter out words that are too short
        return array_filter($words, function($word) {
            return strlen($word) >= $this->minWordLength;
        });
    }

    /**
     * Generate n-grams from word array
     */
    protected function generateNGrams($words, $n)
    {
        $nGrams = [];
        $count = count($words);

        for ($i = 0; $i <= $count - $n; $i++) {
            $gram = array_slice($words, $i, $n);
            
            // Skip if contains stop words in key positions
            if ($this->containsCriticalStopWords($gram)) {
                continue;
            }

            $nGrams[] = implode(' ', $gram);
        }

        return $nGrams;
    }

    /**
     * Check if phrase contains critical stop words that make it low quality
     */
    protected function containsCriticalStopWords($words)
    {
        $stopWordCount = 0;
        
        foreach ($words as $word) {
            if ($this->isStopWord(strtolower($word))) {
                $stopWordCount++;
            }
        }

        // If more than half are stop words, skip this phrase
        return $stopWordCount > (count($words) / 2);
    }

    /**
     * Check if a word is a stop word
     */
    protected function isStopWord($word)
    {
        return in_array(strtolower($word), $this->stopWords);
    }

    /**
     * Clean and format topic string
     */
    protected function cleanTopic($topic)
    {
        // Trim whitespace
        $topic = trim($topic);
        
        // Capitalize first letter of each word for phrases
        if (strpos($topic, ' ') !== false) {
            $topic = ucwords(strtolower($topic));
        }

        return $topic;
    }

    /**
     * Remove duplicate and similar topics
     */
    protected function removeDuplicates($topics)
    {
        $unique = [];
        $seen = [];

        foreach ($topics as $topic) {
            $normalized = strtolower($topic['topic']);
            
            // Skip exact duplicates
            if (in_array($normalized, $seen)) {
                continue;
            }

            // Skip if this is a single word contained in a higher-scored phrase
            if ($topic['type'] === 'keyword') {
                $skipThis = false;
                foreach ($unique as $existingTopic) {
                    if ($existingTopic['type'] === 'phrase' && 
                        $existingTopic['score'] > $topic['score'] &&
                        stripos($existingTopic['topic'], $topic['topic']) !== false) {
                        $skipThis = true;
                        break;
                    }
                }
                if ($skipThis) {
                    continue;
                }
            }

            $seen[] = $normalized;
            $unique[] = $topic;
        }

        return $unique;
    }
}

