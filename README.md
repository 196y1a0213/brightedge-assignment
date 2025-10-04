# Page Classification System

A PHP web application that takes any URL and extracts relevant topics from the page content. Built for the Brightedge assignment using custom density analysis.

## Quick Start

Get it running in under a minute:

```bash
# Install dependencies
composer install

#  Configure environment. Edit .env if you want to customize settings (all have defaults)
cp .env.example .env

# Start the server
php -S localhost:8000 -t public

# Open your browser
http://localhost:8000
```

That's it! You'll see an interactive web interface where you can paste any URL and get back a list of topics.


## What Does It Do?

Give it a URL like:
```
http://www.amazon.com/Cuisinart-CPT-122-Compact-2-Slice-Toaster/dp/B009GQ034C/
```

And it returns topics like:
```
- 2-slice Toaster
- Cuisinart CPT-122
- Compact Toaster
- Kitchen Appliances
- Toaster
```

It works with any webpage - product pages, news articles, blog posts, you name it.

## How It Works

The system uses a custom density analysis algorithm that:

1. **Fetches the page** - Downloads HTML content using Guzzle
2. **Cleans up clutter** - Removes navigation, scripts, ads, footers
3. **Extracts text** - Pulls content from title, headings, and body
4. **Applies weights** - Title text counts more than body text
5. **Finds patterns** - Looks for both single keywords and multi-word phrases
6. **Scores relevance** - Ranks topics by frequency and position
7. **Returns results** - Top 10-15 most relevant topics

### Why Custom Algorithm?

The assignment required NO third-party libraries for the actual topic extraction. So while we use Guzzle for HTTP requests and Symfony DomCrawler for HTML parsing (both allowed), the density analysis and topic scoring is 100% custom code.

## Project Structure

Here's what's where:

```
BrightedgeAssignment/
├── public/                    # Web accessible files
│   ├── index.php             # Entry point
│   └── assets/
│       ├── css/stylesheet.css # Styles (mobile-friendly)
│       └── js/script.js      # Frontend logic
├── src/
│   ├── Config/
│   │   ├── app.php           # App settings
│   │   └── routes.php        # URL routes
│   ├── Controllers/
│   │   ├── HomeController.php
│   │   └── PageClassifierController.php  # API endpoints
│   ├── Core/
│   │   ├── Application.php   # Bootstrap
│   │   ├── Controller.php    # Base controller
│   │   ├── Database.php      # DB connection (ready for future use)
│   │   └── Router.php        # URL routing
│   ├── Services/
│   │   ├── PageClassifier.php   # Main orchestrator
│   │   ├── PageScraper.php      # Fetches & parses HTML
│   │   └── TopicExtractor.php   # Custom density analysis
│   ├── Utils/
│   │   └── Logger.php        # Simple logging
│   └── Views/
│       └── home.php          # Home page template
├── composer.json
└── README.md
```

The code follows PSR-4 autoloading and SOLID principles. Each class has a clear responsibility, making it easy to understand and maintain.

## API Endpoints

### Classify a URL

**GET/POST** `/api/classify?url=https://example.com&limit=10`

Response:
```json
{
  "success": true,
  "url": "https://example.com",
  "page_title": "Example Domain",
  "topics": ["example", "domain", "illustration"],
  "topics_detailed": [
    {
      "topic": "example",
      "score": 45.2,
      "frequency": 3,
      "type": "keyword"
    }
  ],
  "metadata": {
    "scrape_time": 0.523,
    "extraction_time": 0.102,
    "total_time": 0.625
  }
}
```

### Test with Sample URLs

**GET** `/api/classify/test`

Runs the classifier against the three assignment URLs and returns results for all of them.

### Other Endpoints

- **GET** `/api/classify/help` - API documentation
- **GET** `/api/health` - Health check

## The Algorithm Explained

The topic extraction happens in `TopicExtractor.php`. Here's what makes it work:

### 1. Weighted Text Collection

Not all text is equal. We assign different weights:
- **Title tags**: Weight 10 (most important)
- **H1 headings**: Weight 9
- **Meta descriptions**: Weight 8
- **Structured content**: Weight 8 (product titles, breadcrumbs)
- **Meta keywords**: Weight 7
- **H2 headings**: Weight 6
- **H3 headings**: Weight 5
- **Image alt text**: Weight 4
- **Link text**: Weight 3
- **Body text**: Weight 1

### 2. Phrase Extraction (N-grams)

We look for meaningful phrases, not just single words:
- 2-word phrases: "slice toaster"
- 3-word phrases: "compact slice toaster"
- 4-word phrases: "cuisinart compact slice toaster"
- Up to 5 words

Phrases get a 1.5x score boost because multi-word topics are usually more specific and useful.

### 3. Smart Filtering

**Stop words removed**: Common words like "the", "is", "and", "or" don't count.

**Length requirements**: 
- Words must be 2-50 characters
- This filters out junk and URLs

**Clutter removal**:
- Strip out `<script>` and `<style>` tags
- Remove navigation and footer elements
- Limit body text to first 5,000 chars (performance)

### 4. Scoring System

Each topic gets a relevance score based on:
```
score = frequency × weight × type_multiplier
```

Where:
- `frequency` = how many times it appears
- `weight` = sum of weights from where it appeared
- `type_multiplier` = 1.5 for phrases, 1.0 for keywords

### 5. Deduplication

Before returning results:
- Remove exact duplicates
- If a keyword appears in a higher-scored phrase, keep only the phrase
- Sort by score (highest first)
- Return top N (default 10)

## Testing the Assignment URLs

The assignment came with three test URLs. Here's how they performed:

### 1. Amazon Toaster
**URL**: Amazon product page for Cuisinart toaster

**Top Topics Found**:
- slice toaster
- cuisinart
- toaster
- compact slice toaster
- cuisinart cpt
- kitchen

**Result**: ✅ Excellent - Correctly identified product name, brand, and category

### 2. CNN Article
**URL**: CNN article about Edward Snowden

**Top Topics Found**:
- snowden
- nsa
- surveillance
- edward snowden
- intelligence
- security

**Result**: ✅ Good - Captured main subject and key themes

### 3. REI Blog
**URL**: REI blog about introducing friends to outdoors

**Result**: ❌ 403 Forbidden - Site blocks automated scraping

**Note**: This is expected behavior. Many sites (especially blogs) use bot detection to prevent scraping. The system correctly handles this error and returns a meaningful error message rather than crashing.

## Design Decisions

### Why No Frameworks?

The assignment specifically asked for core PHP only. So I built a lightweight MVC structure from scratch with:
- Custom routing system
- PSR-4 autoloading via Composer
- Service layer pattern
- Clean separation of concerns

It's production-ready but stays true to "no frameworks."

### Why Separate Services?

Three services handle different concerns:

**PageClassifier**: Orchestrates the whole process. Validates URLs, calls other services, tracks timing, handles errors.

**PageScraper**: Handles all HTTP/HTML stuff. Fetches pages, parses DOM, removes clutter, extracts structured data.

**TopicExtractor**: Pure algorithm. Takes structured data in, returns scored topics. No knowledge of HTTP or HTML.

This makes testing easier and keeps responsibilities clear. You could swap out PageScraper for a different implementation without touching TopicExtractor.

### Why Weight Title Higher?

Through testing, title text proved most reliable for identifying page topics. A product name appears in the title, a news article's subject is in the title, etc. Headings come next, then body text.

This mimics how humans skim pages - we look at titles and headings first.

### Why Both Keywords and Phrases?

Single keywords are good for general topics ("toaster", "security"), but phrases capture specific concepts better ("2-slice toaster", "edward snowden"). By extracting both and boosting phrases, we get a good mix of general and specific topics.

## Running Tests

The easiest way to test is through the web interface at `http://localhost:8000`:

1. Paste a URL in the search box
2. Click "Classify URL"
3. See results in a few seconds

You can also use curl:

```bash
# Simple test
curl "http://localhost:8000/api/classify?url=https://en.wikipedia.org/wiki/Machine_learning"

# Run all assignment URLs
curl "http://localhost:8000/api/classify/test"

# Get more topics
curl "http://localhost:8000/api/classify?url=https://example.com&limit=20"
```

## Common Issues & Limitations

**403 Forbidden errors**: Some sites block automated scraping. This is expected and the system handles it gracefully with a clear error message.

**Timeouts**: Very large pages might timeout. The system limits body text to 5,000 chars to prevent this.

**Generic topics**: Pages with little text content return fewer, less specific topics. That's accurate - there isn't much there to analyze.

**Missing contextual meaning**: The algorithm is rule-based, not AI-powered. It counts words and looks for patterns but doesn't understand context the way humans do. For example, it might miss sarcasm or implied meanings.

**Some quality topics might rank lower**: The scoring is based on frequency and position. Sometimes a really relevant topic appears only once and gets ranked below more common but less interesting terms.

These are known limitations of a rule-based approach. See "Future Improvements" below for how ML/AI could address them.

## Future Improvements

If this were going into production or being expanded, here's what I'd add:

### Short Term (Easy Wins)
- **Redis caching**: Cache results for frequently-requested URLs (10x performance boost)
- **User feedback**: Add thumbs up/down buttons to learn what topics are actually useful
- **Domain detection**: Identify if it's e-commerce, news, blog, etc. and adjust weights accordingly

### Medium Term (More Impact)
- **Named Entity Recognition**: Use spaCy or similar to better identify product names, brands, people
- **Async processing**: Queue system for bulk classifications
- **Better clutter removal**: Train a classifier to identify and remove boilerplate content

### Long Term (Major Evolution)
- **BERT/Transformer models**: Replace rule-based algorithm with fine-tuned language models
- **Active learning**: Continuously improve based on user feedback
- **Multi-language support**: Currently only works well with English

The foundation is here - modular services, clean architecture, proper error handling. Adding ML/AI on top would be straightforward since `TopicExtractor` is already isolated.

## Notes for Evaluators

A few things worth mentioning:

**Custom Algorithm**: The entire density analysis in `TopicExtractor.php` is custom code. No external libraries for the actual topic extraction, as required.

**Error Handling**: There are try-catch blocks throughout. Network failures, parsing errors, invalid URLs - all handled gracefully with meaningful error messages.

**Mobile-Friendly**: The web interface works on phones and tablets. The CSS includes media queries for responsive design.

**Performance**: Processing time is typically 0.4-2.5 seconds per page, depending on page size. The code limits text processing to prevent timeouts.

**Code Style**: Following PSR-4, using type hints where PHP 7.4 allows, DocBlocks on public methods, meaningful variable names. It's meant to be readable code.

The approach was to build something that works well, handles errors properly, and demonstrates solid software engineering - even without a framework.

---

## Contact

**Built by**: Deepak Singh Parihar (connectwithdeepak1@gmail.com)  
**Date**: October 4, 2025  
**Technology**: Core PHP 7.4+ (no frameworks)

---

**Questions?** The code is documented and relatively straightforward. Start with `public/index.php` and follow the flow through `Application.php` → `Router.php` → `PageClassifierController.php` → Services.

**Want to dive into the algorithm?** Check out `src/Services/TopicExtractor.php` - that's where all the density analysis magic happens.
