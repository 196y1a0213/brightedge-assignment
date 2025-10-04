<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brightedge Assignment - Page Classifier</title>
    <link rel="stylesheet" href="/assets/css/stylesheet.css">
</head>
<body>
    <div class="container">
        <h1>🎯 BrightEdge Assignment - Page Classifier API</h1>
        
        <!-- Interactive Search Section -->
        <div class="search-section">
            <h2 style="margin-top: 0;">🔍 Try It Now!</h2>
            <p>Enter any URL to see what topics our algorithm extracts from that page.</p>
            <form class="search-form" id="classifyForm" onsubmit="return classifyUrl(event)">
                <input 
                    type="text" 
                    id="urlInput" 
                    class="search-input" 
                    placeholder="https://www.example.com/page"
                    required
                />
                <button type="submit" class="search-button" id="searchButton">
                    Classify URL
                </button>
            </form>
        </div>
        
        <!-- Results Section -->
        <div class="results-section" id="resultsSection">
            <h2>📊 Classification Results</h2>
            <div class="results-box" id="resultsBox"></div>
        </div>
        
        <div class="section">
            <h2>📋 Page Classification</h2>
            <p>This API classifies web pages and returns a list of relevant topics using custom density analysis.</p>
            <p><strong>Key Features:</strong></p>
            <ul>
                <li>✅ Crawls and parses any URL using Guzzle and Symfony DomCrawler</li>
                <li>✅ Custom-built density analysis (no 3rd party libraries for analysis)</li>
                <li>✅ Extracts topics from titles, headings, meta tags, and body content</li>
                <li>✅ Weighted scoring system for relevance ranking</li>
                <li>✅ Supports both single and batch URL processing</li>
            </ul>
        </div>

        <h2>🚀 API Endpoints</h2>
        
        <div class="endpoint">
            <span class="method get">GET</span> <span class="method post">POST</span>
            <strong>/api/classify</strong>
            <p>Classify a single URL and get relevant topics</p>
            <pre>GET  /api/classify?url=https://example.com&limit=10
POST /api/classify
     Body: {"url": "https://example.com", "limit": 10}</pre>
        </div>

        <div class="endpoint">
            <span class="method post">POST</span>
            <strong>/api/classify/batch</strong>
            <p>Classify multiple URLs in one request (max 10 URLs)</p>
            <pre>POST /api/classify/batch
     Body: {"urls": ["url1", "url2"], "limit": 10}</pre>
        </div>

        <div class="endpoint">
            <span class="method get">GET</span>
            <strong>/api/classify/test</strong>
            <p>Test with predefined assignment URLs</p>
            <pre>GET /api/classify/test           (runs all test URLs)
GET /api/classify/test?index=0   (runs specific test)</pre>
        </div>

        <div class="endpoint">
            <span class="method get">GET</span>
            <strong>/api/classify/help</strong>
            <p>Get API documentation</p>
        </div>

        <div class="test-urls">
            <h3>🧪 Test URLs from Assignment</h3>
            <ol>
                <li><a href="/api/classify/test?index=0" target="_blank">Amazon Toaster Product Page</a></li>
                <li><a href="/api/classify/test?index=1" target="_blank">REI Blog Post</a></li>
                <li><a href="/api/classify/test?index=2" target="_blank">CNN Article</a></li>
            </ol>
            <p><a href="/api/classify/test" target="_blank"><strong>Run All Tests →</strong></a></p>
        </div>

        <h2>💡 Quick Examples</h2>
        
        <div class="section">
            <h3>Example 1: Classify a URL</h3>
            <pre>curl "http://localhost:8000/api/classify?url=https://example.com"</pre>
        </div>

        <div class="section">
            <h3>Example 2: Test with Assignment URLs</h3>
            <pre>curl "http://localhost:8000/api/classify/test"</pre>
        </div>

        <div class="section">
            <h3>Example 3: Batch Classification</h3>
            <pre>curl -X POST http://localhost:8000/api/classify/batch \
  -H "Content-Type: application/json" \
  -d '{"urls": ["url1", "url2"], "limit": 10}'</pre>
        </div>

        <h2>📚 Additional Resources</h2>
        <ul>
            <li><a href="/api/classify/help">API Documentation</a></li>
            <li><a href="/api/health">Health Check</a></li>
        </ul>

        <p style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #dee2e6; color: #6c757d;">
            <small>Built by <a href="https://www.linkedin.com/in/deepak-singh-4592bb141/">Deepak Singh Parihar</a></small>
        </p>
    </div>
    
    <script src="/assets/js/script.js"></script>
</body>
</html>
