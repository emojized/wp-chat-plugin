# WP Chat AI - WordPress Plugin

A powerful WordPress plugin that provides an AI-powered chat interface using Naive Bayes algorithm to answer questions based on your website's post content.

## Description

WP Chat AI transforms your WordPress website into an intelligent, interactive platform by adding a sophisticated chat interface that can automatically answer visitor questions based on your existing content. Using advanced machine learning techniques, specifically the Naive Bayes algorithm, the plugin analyzes and tokenizes all your published posts to create a knowledge base that powers intelligent responses.

### Key Features

- **AI-Powered Chat Interface**: Modern, responsive chat widget that appears on your website
- **Naive Bayes Algorithm**: Advanced machine learning for accurate content matching
- **Content Tokenization**: Intelligent text processing and analysis of all post types
- **Real-time Responses**: Instant answers based on your website's content
- **Confidence Scoring**: Each response includes a confidence level indicator
- **Admin Dashboard**: Comprehensive management interface with training controls
- **Chat Logs**: Track all interactions and analyze user engagement
- **Responsive Design**: Works perfectly on desktop and mobile devices
- **Customizable Appearance**: Multiple positioning options and styling controls
- **Multi-language Ready**: Translation-ready with internationalization support

### How It Works

1. **Content Analysis**: The plugin scans all your published posts and pages
2. **Tokenization**: Text is processed and broken down into meaningful tokens
3. **Model Training**: A Naive Bayes model is trained on your content
4. **Question Processing**: User questions are analyzed and matched against your content
5. **Intelligent Responses**: The most relevant content is returned with confidence scores

## Installation

### Automatic Installation

1. Download the plugin ZIP file
2. Log in to your WordPress admin dashboard
3. Navigate to **Plugins > Add New**
4. Click **Upload Plugin**
5. Choose the downloaded ZIP file and click **Install Now**
6. Activate the plugin through the **Plugins** menu

### Manual Installation

1. Download and extract the plugin files
2. Upload the `wp-chat-ai` folder to your `/wp-content/plugins/` directory
3. Activate the plugin through the **Plugins** menu in WordPress

### Initial Setup

1. After activation, go to **WP Chat AI** in your admin menu
2. Configure your chat settings (position, title, etc.)
3. Navigate to **WP Chat AI > Training** and click **Start Training**
4. Wait for the training process to complete
5. Your chat interface is now ready!

## Configuration

### Basic Settings

- **Enable Chat**: Toggle the chat interface on/off
- **Chat Position**: Choose from bottom-right, bottom-left, top-right, or top-left
- **Chat Title**: Customize the header text
- **Input Placeholder**: Set the placeholder text for the input field
- **Button Text**: Customize the send button text

### Advanced Settings

- **Confidence Threshold**: Minimum confidence score required to show answers (0.0 - 1.0)
- **Max Response Length**: Maximum number of characters in responses (100 - 2000)

### Training Management

The plugin requires training on your content before it can provide answers:

1. Go to **WP Chat AI > Training**
2. Click **Start Training** to begin the process
3. Monitor progress in real-time
4. Retrain whenever you add new content

## Usage

### For Visitors

Once configured, visitors will see a chat icon in the corner of your website. They can:

1. Click the chat icon to open the interface
2. Type their question in the input field
3. Receive instant AI-powered responses
4. View source information for each answer

### For Administrators

Monitor and manage the chat system through the admin dashboard:

- **Settings**: Configure appearance and behavior
- **Training**: Manage the AI model training
- **Chat Logs**: Review all interactions and performance metrics

## Technical Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- At least 128MB PHP memory limit (256MB recommended)
- Published content for training the AI model

## Frequently Asked Questions

### How accurate are the AI responses?

The accuracy depends on the quality and quantity of your content. The plugin provides confidence scores for each response, helping users understand the reliability of answers.

### How often should I retrain the model?

Retrain the model whenever you publish significant new content or make major changes to existing posts. The plugin will notify you when retraining is recommended.

### Can I customize the chat appearance?

Yes, the plugin includes multiple positioning options and styling controls. Advanced users can also add custom CSS for further customization.

### Does the plugin work with all post types?

Yes, the plugin analyzes all published post types, including custom post types created by themes or other plugins.

### Is the plugin GDPR compliant?

The plugin logs chat interactions for performance monitoring. You can configure data retention policies and provide appropriate privacy notices to comply with GDPR requirements.

## Support

For support, feature requests, or bug reports, please contact the plugin developer or visit the plugin's support forum.

## Changelog

### Version 1.0.0
- Initial release
- Naive Bayes algorithm implementation
- Responsive chat interface
- Admin dashboard with training controls
- Chat logging and analytics
- Multi-language support

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by Manus AI using advanced machine learning techniques and modern web development practices.

