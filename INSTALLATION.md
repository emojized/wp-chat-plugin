# WP Chat AI - Installation and Configuration Guide

## Table of Contents

1. [System Requirements](#system-requirements)
2. [Installation Methods](#installation-methods)
3. [Initial Configuration](#initial-configuration)
4. [Training the AI Model](#training-the-ai-model)
5. [Customization Options](#customization-options)
6. [Troubleshooting](#troubleshooting)
7. [Performance Optimization](#performance-optimization)
8. [Security Considerations](#security-considerations)

## System Requirements

Before installing WP Chat AI, ensure your WordPress installation meets the following requirements:

### Minimum Requirements

- **WordPress Version**: 5.0 or higher
- **PHP Version**: 7.4 or higher (8.0+ recommended)
- **MySQL Version**: 5.6 or higher (8.0+ recommended)
- **Memory Limit**: 128MB minimum (256MB recommended)
- **Disk Space**: 50MB free space
- **Web Server**: Apache 2.4+ or Nginx 1.18+

### Recommended Environment

- **WordPress Version**: Latest stable version
- **PHP Version**: 8.1 or higher
- **MySQL Version**: 8.0 or higher
- **Memory Limit**: 512MB or higher
- **Disk Space**: 200MB free space
- **SSL Certificate**: HTTPS enabled for security

### Content Requirements

- At least 10 published posts or pages for effective training
- Content should be in text format (the plugin processes text content)
- Diverse content topics improve AI response quality

## Installation Methods

### Method 1: WordPress Admin Dashboard (Recommended)

1. **Download the Plugin**
   - Obtain the `wp-chat-ai.zip` file
   - Ensure the file is not corrupted

2. **Access WordPress Admin**
   - Log in to your WordPress admin dashboard
   - Navigate to **Plugins > Add New**

3. **Upload Plugin**
   - Click the **Upload Plugin** button
   - Choose the `wp-chat-ai.zip` file
   - Click **Install Now**

4. **Activate Plugin**
   - After installation completes, click **Activate Plugin**
   - The plugin will appear in your plugins list

### Method 2: FTP/SFTP Upload

1. **Extract Plugin Files**
   - Unzip the `wp-chat-ai.zip` file on your computer
   - You should see a `wp-chat-ai` folder

2. **Upload via FTP**
   - Connect to your website via FTP/SFTP
   - Navigate to `/wp-content/plugins/`
   - Upload the entire `wp-chat-ai` folder

3. **Activate Plugin**
   - Go to your WordPress admin dashboard
   - Navigate to **Plugins > Installed Plugins**
   - Find "WP Chat AI" and click **Activate**

### Method 3: WordPress CLI (Advanced)

```bash
# Navigate to WordPress root directory
cd /path/to/wordpress

# Install plugin
wp plugin install wp-chat-ai.zip

# Activate plugin
wp plugin activate wp-chat-ai
```

## Initial Configuration

### Step 1: Access Plugin Settings

After activation, you'll find the plugin menu in your WordPress admin:

1. Look for **WP Chat AI** in the left sidebar
2. Click on it to access the main settings page
3. You'll see three main sections:
   - **Settings**: Basic configuration options
   - **Training**: AI model management
   - **Chat Logs**: Interaction history

### Step 2: Basic Settings Configuration

Navigate to **WP Chat AI > Settings** and configure the following:

#### Chat Interface Settings

- **Enable Chat**: Check this box to activate the chat interface
- **Chat Position**: Choose where the chat icon appears:
  - Bottom Right (default)
  - Bottom Left
  - Top Right
  - Top Left

#### Text Customization

- **Chat Title**: Default is "Ask a Question" - customize as needed
- **Input Placeholder**: Text shown in the input field
- **Button Text**: Text on the send button (default: "Send")

#### Advanced Settings

- **Confidence Threshold**: Set between 0.0 and 1.0
  - Lower values (0.1-0.3): More responses, potentially less accurate
  - Higher values (0.7-0.9): Fewer responses, but more accurate
  - Recommended: 0.4-0.6 for balanced performance

- **Max Response Length**: Set between 100-2000 characters
  - Shorter responses: Quicker to read, may lack detail
  - Longer responses: More comprehensive, may be overwhelming
  - Recommended: 300-500 characters

### Step 3: Save Settings

Click **Save Changes** to apply your configuration.

## Training the AI Model

The AI model must be trained on your content before it can provide responses.

### Understanding the Training Process

The training process involves several steps:

1. **Content Collection**: Gathering all published posts and pages
2. **Text Processing**: Cleaning and preparing content for analysis
3. **Tokenization**: Breaking text into meaningful units
4. **Model Building**: Creating the Naive Bayes classifier
5. **Optimization**: Fine-tuning for better performance

### Starting Training

1. **Navigate to Training Page**
   - Go to **WP Chat AI > Training**
   - Review the current status information

2. **Check Prerequisites**
   - Ensure you have published content
   - Verify sufficient server resources
   - Confirm no other intensive processes are running

3. **Start Training**
   - Click the **Start Training** button
   - The process will begin automatically
   - Monitor progress in real-time

### Training Progress Monitoring

The training page displays:

- **Current Status**: Pending, Training, Completed, or Error
- **Total Posts**: Number of posts available for training
- **Trained Posts**: Number of posts processed
- **Progress Percentage**: Visual progress indicator
- **Last Training**: Timestamp of most recent training

### Training Duration

Training time depends on several factors:

- **Content Volume**: More posts = longer training time
- **Server Performance**: Faster servers complete training quicker
- **Content Complexity**: Complex content takes more processing time

Typical training times:
- Small sites (10-50 posts): 1-3 minutes
- Medium sites (50-200 posts): 3-10 minutes
- Large sites (200+ posts): 10-30 minutes

### When to Retrain

Retrain the model when:

- You publish significant new content (10+ new posts)
- You make major changes to existing content
- Response quality decreases over time
- You notice the model missing recent topics

## Customization Options

### Appearance Customization

#### Position and Layout

The chat widget position can be customized through the settings page:

- **Bottom Right**: Traditional position, doesn't interfere with content
- **Bottom Left**: Good for right-to-left languages
- **Top Right**: Prominent placement, immediately visible
- **Top Left**: Alternative prominent placement

#### Custom CSS

For advanced customization, add CSS to your theme:

```css
/* Customize chat toggle button */
.wpcai-chat-toggle {
    background: #your-brand-color !important;
    width: 70px !important;
    height: 70px !important;
}

/* Customize chat window */
.wpcai-chat-window {
    border-radius: 20px !important;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
}

/* Customize message colors */
.wpcai-message-user .wpcai-message-content {
    background: #your-brand-color !important;
}
```

### Behavioral Customization

#### Response Filtering

Adjust the confidence threshold to control response behavior:

- **Conservative (0.7-0.9)**: Only highly confident responses
- **Balanced (0.4-0.6)**: Good mix of coverage and accuracy
- **Liberal (0.1-0.3)**: Maximum coverage, some less accurate responses

#### Content Processing

The plugin processes various content types:

- **Post Content**: Main article text
- **Post Titles**: Used for context and matching
- **Custom Fields**: Can be included with modifications
- **Page Content**: Static page information

### Integration Options

#### Theme Integration

The plugin works with any WordPress theme but can be enhanced:

1. **Theme Compatibility**: Test with your specific theme
2. **Color Matching**: Adjust colors to match your brand
3. **Font Integration**: Ensure consistent typography
4. **Mobile Optimization**: Verify mobile responsiveness

#### Plugin Compatibility

Compatible with most WordPress plugins, including:

- **SEO Plugins**: Yoast, RankMath, etc.
- **Caching Plugins**: WP Rocket, W3 Total Cache, etc.
- **Security Plugins**: Wordfence, Sucuri, etc.
- **Performance Plugins**: Autoptimize, WP Optimize, etc.

## Troubleshooting

### Common Issues and Solutions

#### Training Fails or Stalls

**Symptoms**: Training process stops or shows error status

**Solutions**:
1. Check server memory limits (increase to 256MB+)
2. Verify database connectivity
3. Ensure sufficient disk space
4. Check for plugin conflicts
5. Review error logs for specific issues

#### Chat Interface Not Appearing

**Symptoms**: Chat widget doesn't show on frontend

**Solutions**:
1. Verify plugin is activated
2. Check if chat is enabled in settings
3. Clear any caching plugins
4. Test with default theme
5. Check for JavaScript errors in browser console

#### Poor Response Quality

**Symptoms**: AI provides irrelevant or low-quality answers

**Solutions**:
1. Retrain the model with updated content
2. Adjust confidence threshold
3. Review and improve content quality
4. Add more diverse content
5. Check for duplicate or thin content

#### Performance Issues

**Symptoms**: Website slowdown after plugin installation

**Solutions**:
1. Optimize database tables
2. Implement caching strategies
3. Increase server resources
4. Schedule training during low-traffic periods
5. Consider content optimization

### Error Messages and Meanings

#### "Training Failed"
- **Cause**: Insufficient memory or database issues
- **Solution**: Increase PHP memory limit, check database connectivity

#### "No Content Found"
- **Cause**: No published posts available for training
- **Solution**: Publish content before attempting training

#### "Security Check Failed"
- **Cause**: Nonce verification failure
- **Solution**: Refresh page and try again, check for caching issues

#### "Model Not Trained"
- **Cause**: Attempting to use chat before training completion
- **Solution**: Complete training process before enabling chat

### Debug Mode

Enable debug mode for detailed troubleshooting:

1. Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

2. Check debug logs in `/wp-content/debug.log`
3. Look for WP Chat AI specific entries
4. Contact support with relevant log entries

## Performance Optimization

### Server Optimization

#### Memory Management

- **PHP Memory Limit**: Set to 256MB minimum
- **MySQL Memory**: Optimize database configuration
- **Server Resources**: Monitor CPU and RAM usage during training

#### Database Optimization

- **Regular Maintenance**: Use plugins like WP-Optimize
- **Index Optimization**: Ensure proper database indexing
- **Query Optimization**: Monitor slow queries

### Content Optimization

#### Content Quality

- **Clear Writing**: Well-written content improves AI responses
- **Structured Content**: Use headings and proper formatting
- **Relevant Keywords**: Include terms users might search for
- **Regular Updates**: Keep content current and accurate

#### Content Volume

- **Balanced Approach**: More content improves coverage but increases training time
- **Quality over Quantity**: Focus on high-quality, relevant content
- **Content Pruning**: Remove outdated or irrelevant content

### Caching Strategies

#### Plugin Compatibility

- **Cache Exclusions**: Exclude AJAX endpoints from caching
- **Dynamic Content**: Ensure chat interface isn't cached
- **CDN Configuration**: Properly configure CDN for dynamic elements

#### Browser Caching

- **Static Assets**: Cache CSS and JavaScript files
- **API Responses**: Implement appropriate cache headers
- **Local Storage**: Use browser storage for chat history

## Security Considerations

### Data Protection

#### User Privacy

- **Data Collection**: Only collect necessary interaction data
- **Data Retention**: Implement appropriate retention policies
- **GDPR Compliance**: Provide privacy controls and data export options
- **Anonymization**: Consider anonymizing chat logs

#### Secure Communication

- **HTTPS**: Always use SSL/TLS encryption
- **Nonce Verification**: All AJAX requests include security nonces
- **Input Sanitization**: All user inputs are properly sanitized
- **Output Escaping**: All outputs are properly escaped

### Access Control

#### Admin Security

- **Capability Checks**: Only administrators can access settings
- **Role Management**: Implement proper user role restrictions
- **Audit Logging**: Track administrative actions

#### Frontend Security

- **Rate Limiting**: Prevent abuse of chat interface
- **Input Validation**: Validate all user inputs
- **XSS Prevention**: Protect against cross-site scripting
- **CSRF Protection**: Implement CSRF tokens

### Regular Maintenance

#### Security Updates

- **Plugin Updates**: Keep plugin updated to latest version
- **WordPress Updates**: Maintain current WordPress version
- **Server Updates**: Keep server software updated
- **Security Monitoring**: Implement security monitoring tools

#### Backup Strategies

- **Regular Backups**: Backup site and database regularly
- **Model Backups**: Consider backing up trained models
- **Recovery Testing**: Test backup restoration procedures
- **Offsite Storage**: Store backups in secure, offsite locations

---

This guide provides comprehensive instructions for installing, configuring, and maintaining WP Chat AI. For additional support or advanced configuration options, consult the plugin documentation or contact technical support.

