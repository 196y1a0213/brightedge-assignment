/**
 * Page Classifier - Interactive Search & Results Display
 * Brightedge Assignment
 */

/**
 * Handle form submission for URL classification
 */
function classifyUrl(event) {
    event.preventDefault();
    
    const urlInput = document.getElementById('urlInput');
    const searchButton = document.getElementById('searchButton');
    const resultsSection = document.getElementById('resultsSection');
    const resultsBox = document.getElementById('resultsBox');
    const url = urlInput.value.trim();
    
    if (!url) {
        alert('Please enter a URL');
        return false;
    }
    
    // Show loading state
    searchButton.disabled = true;
    searchButton.textContent = 'Classifying...';
    resultsSection.style.display = 'block';
    resultsBox.innerHTML = `
        <div class="loading">
            <div class="spinner"></div>
            <p>Analyzing page and extracting topics...</p>
            <small>This may take 1-3 seconds depending on page size</small>
        </div>
    `;
    
    // Scroll to results
    resultsSection.scrollIntoView({ behavior: 'smooth' });
    
    // Make API request
    fetch('/api/classify?url=' + encodeURIComponent(url) + '&limit=15')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayResults(data);
            } else {
                displayError(data.error || 'Classification failed');
            }
        })
        .catch(error => {
            displayError('Network error: ' + error.message);
        })
        .finally(() => {
            searchButton.disabled = false;
            searchButton.textContent = 'Classify URL';
        });
    
    return false;
}

/**
 * Display successful classification results
 */
function displayResults(data) {
    const resultsBox = document.getElementById('resultsBox');
    
    // Build topics HTML
    let topicsHtml = '';
    if (data.topics && data.topics.length > 0) {
        topicsHtml = data.topics.map((topic, index) => {
            const detailedTopic = data.topics_detailed[index];
            const isPhrase = detailedTopic && detailedTopic.type === 'phrase';
            const badgeClass = isPhrase ? 'topic-badge topic-phrase' : 'topic-badge';
            const typeLabel = isPhrase ? '📝' : '🔤';
            return `<span class="${badgeClass}" title="${detailedTopic ? 'Score: ' + detailedTopic.score.toFixed(2) + ', Frequency: ' + detailedTopic.frequency : ''}">${typeLabel} ${topic}</span>`;
        }).join('');
    } else {
        topicsHtml = '<p style="color: #6c757d;">No topics found.</p>';
    }
    
    // Build detailed scoring table HTML
    let detailedTopicsHtml = '';
    if (data.topics_detailed && data.topics_detailed.length > 0) {
        detailedTopicsHtml = '<h3 style="margin-top: 25px;">📈 Detailed Scoring</h3><table style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
        detailedTopicsHtml += '<tr style="background: #f8f9fa; font-weight: bold;"><th style="padding: 10px; text-align: left; border-bottom: 2px solid #dee2e6;">Rank</th><th style="padding: 10px; text-align: left; border-bottom: 2px solid #dee2e6;">Topic</th><th style="padding: 10px; text-align: left; border-bottom: 2px solid #dee2e6;">Type</th><th style="padding: 10px; text-align: right; border-bottom: 2px solid #dee2e6;">Score</th><th style="padding: 10px; text-align: right; border-bottom: 2px solid #dee2e6;">Frequency</th></tr>';
        
        data.topics_detailed.slice(0, 10).forEach((topic, index) => {
            const typeEmoji = topic.type === 'phrase' ? '📝' : '🔤';
            const typeName = topic.type === 'phrase' ? 'Phrase' : 'Keyword';
            detailedTopicsHtml += `<tr style="border-bottom: 1px solid #e9ecef;">
                <td style="padding: 10px;">#${index + 1}</td>
                <td style="padding: 10px; font-weight: 500;">${topic.topic}</td>
                <td style="padding: 10px;">${typeEmoji} ${typeName}</td>
                <td style="padding: 10px; text-align: right;">${topic.score.toFixed(2)}</td>
                <td style="padding: 10px; text-align: right;">${topic.frequency}</td>
            </tr>`;
        });
        detailedTopicsHtml += '</table>';
    }
    
    // Display complete results
    resultsBox.innerHTML = `
        <div class="success-message">
            ✅ Classification successful!
        </div>
        
        <div class="page-title">
            📄 ${data.page_title || 'Untitled Page'}
        </div>
        
        <h3>🏷️ Extracted Topics</h3>
        <div style="margin: 15px 0;">
            ${topicsHtml}
        </div>
        
        ${detailedTopicsHtml}
        
        <div class="metadata">
            <strong>⚡ Performance Metrics:</strong><br>
            • Processing Time: ${data.metadata.total_time}s<br>
            • Topics Found: ${data.metadata.topics_found}<br>
            • Topics Returned: ${data.metadata.topics_returned}<br>
            • URL: <a href="${data.url}" target="_blank" style="word-break: break-all;">${data.url}</a>
        </div>
        
        <div style="margin-top: 15px; padding: 10px; background: #e7f3ff; border-radius: 5px; font-size: 14px;">
            <strong>ℹ️ Legend:</strong> 
            📝 = Multi-word phrase (higher relevance) | 
            🔤 = Single keyword
        </div>
    `;
}

/**
 * Display error message
 */
function displayError(errorMessage) {
    const resultsBox = document.getElementById('resultsBox');
    resultsBox.innerHTML = `
        <div class="error-message">
            <strong>❌ Error:</strong><br>
            ${errorMessage}
        </div>
        <div style="margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
            <strong>Common Issues:</strong>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Make sure the URL is valid and includes http:// or https://</li>
                <li>Some websites block automated crawlers (403 Forbidden)</li>
                <li>The website may be temporarily unavailable</li>
                <li>Check your internet connection</li>
            </ul>
        </div>
    `;
}

/**
 * Initialize when DOM is ready
 */
document.addEventListener('DOMContentLoaded', function() {
    // Add keyboard shortcut (Ctrl+Enter or Cmd+Enter to submit)
    const urlInput = document.getElementById('urlInput');
    if (urlInput) {
        urlInput.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                document.getElementById('classifyForm').dispatchEvent(new Event('submit'));
            }
        });
    }
});

// Example URLs for reference (not currently used in UI)
const exampleUrls = [
    'http://www.amazon.com/Cuisinart-CPT-122-Compact-2-Slice-Toaster/dp/B009GQ034C/',
    'http://www.cnn.com/2013/06/10/politics/edward-snowden-profile/',
    'https://en.wikipedia.org/wiki/Machine_learning'
];


