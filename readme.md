# JSON Post Importer

[![GitHub release](https://img.shields.io/github/release/drhdev/json-post-importer.svg)](https://github.com/drhdev/json-post-importer/releases) [![License: GPL v2](https://img.shields.io/badge/License-GPL_v2-blue.svg)](LICENSE)

**Repository:** [https://github.com/drhdev/json-post-importer](https://github.com/drhdev/json-post-importer)

## Description

JSON Post Importer is a lightweight WordPress plugin that enables you to quickly create blog posts by simply pasting a JSON string into a responsive form on your website. Designed with busy content creators in mind, this plugin is ideal for converting conversations with ChatGPT or any other generative AI assistant (for example, on iOS devices) into fully formatted WordPress posts with minimal effort.

The plugin validates the JSON input (which must include at least the "title" and "content" fields) and supports HTML formatting within the content. This allows you to include links, images, and other HTML elements directly in your post. Posts are automatically assigned to the currently logged-in user who submits the form, ensuring proper attribution. Robust security measures—including nonce verification, capability checks, and data sanitization—ensure that submissions are protected against unauthorized access and malicious code.

## Features

- **Quick Post Creation:** Paste your JSON data to instantly create a new WordPress post.
- **Comprehensive JSON Validation:** Ensures the JSON is properly formatted and contains required fields ("title" and "content").
- **HTML Content Support:** Accepts HTML in the content field so you can include links, images, and other formatting.
- **Customizable Defaults:** Configure default post status, categories, tags, post type, and more via the settings page.
- **Custom Redirect URL:** Optionally set a URL to redirect to after successful submission.
- **File Storage Option:** Optionally save the submitted JSON as a file in a secure directory.
- **Responsive Input Form:** The JSON input textarea is fully responsive (100% width) with a configurable number of rows.
- **Role-Based Access Control:** Only logged-in users with allowed roles (default is "administrator") can access the form.
- **Security First:** Utilizes WordPress nonces, sanitization, and capability checks to protect against CSRF, malware, and unauthorized use.
- **Ideal Use Case:** Perfect for turning AI-generated conversations into blog posts quickly and efficiently.

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- Compatible with both Apache and Nginx servers
- For Debug Mode: Ensure `WP_DEBUG` and `WP_DEBUG_LOG` are enabled (logs are written to `wp-content/debug.log`)

## Installation

1. **Clone or Download the Repository:**
   ```bash
   git clone https://github.com/drhdev/json-post-importer.git
   ```
2.	**Upload the Plugin:**
Place the json-post-importer folder in your /wp-content/plugins/ directory.

3. **Activate the Plugin:**
Log in to your WordPress admin dashboard and go to **Plugins**. Find **JSON Post Importer** and click **Activate**.

## Configuration

After activation, a new top-level menu item **JSON Post Importer** will appear in the WordPress admin sidebar. On the settings page, you can configure:

- **Default Post Status:** Choose whether new posts are created as Draft or Published.
- **Default Categories/Tags:** Set default category IDs and tags (comma separated) if they are not provided in the JSON.
- **Default Post Type:** Select the post type to create (e.g. Post, Page, or any public custom post type).
- **Redirect URL:** Optionally set a custom URL to redirect users after a successful submission (leave blank to reload the same page).
- **Debug Mode:** Enable debug mode to log extra error details to `wp-content/debug.log` (requires `WP_DEBUG` and `WP_DEBUG_LOG` to be enabled).
- **Allowed Roles:** Specify (comma separated) which user roles are allowed to access the form (default is "administrator").
- **Confirmation Text:** Customize the message displayed after a post is created. Use `{view_link}` and `{edit_link}` as placeholders.
- **Store JSON as File:** Enable this option to save the submitted JSON as a file in a secure directory.
- **JSON Textarea Rows:** Set the number of rows for the JSON input textarea. The width is always 100% for responsiveness, and there is no character limit (accepting even very large JSON inputs).

> **Note:** Posts are always assigned to the currently logged-in user who submits the form. The default author option has been removed.

## Usage

1. **Create a New Page:**
   - Insert the shortcode `[json_post_importer_form]` into the page content.
2. **Access the Form:**
   - Only logged-in users with allowed roles (by default, administrators) can access the form.
3. **Submit JSON:**
   - Copy your JSON (for example, from a conversation with ChatGPT or from the provided `demo.json` file) and paste it into the form.
4. **Post Creation:**
   - Upon submission, the plugin validates and sanitizes the input, creates a post, and displays a confirmation message with links to view or edit the post.

## Demo JSON Example

A sample `demo.json` file is provided created with an appropriate prompt (`demo_prompt_en.txt` or `demo_prompt_ger.txt` or any of your own modifications thereof which was entered at the end of a conversation with ChatGPT.

## JSON Options

When submitting JSON via the form, you can include the following keys:

- **title** (required): The post title.
- **content** (required): The post content (HTML is allowed, so include links, images, etc.).
- **status** (optional): Either `"draft"` or `"publish"`. If omitted, the default from the settings is used.
- **date** (optional): A valid date string (e.g., `"2025-02-26T10:00:00"`). If omitted, the current date/time is used.
- **categories** (optional): An array of category IDs.
- **tags** (optional): An array of tags.
- **Any additional key** will be stored as post meta (provided its value is not an array).

## Security

- **Role-Based Access:** Only logged-in users with allowed roles (default is "administrator") can access the form.
- **Data Validation:** The plugin checks for valid JSON syntax and ensures that required fields are present.
- **Sanitization:** All submitted data is sanitized using WordPress functions such as `sanitize_text_field` and `wp_kses_post`.
- **Nonce Protection:** Nonces are used to prevent CSRF attacks.
- **Malware Prevention:** Thorough input validation and sanitization protect against malicious code and malware.

## Primary Use Case

JSON Post Importer is designed to help bloggers and content creators quickly convert AI-generated content—such as summaries or full conversations with ChatGPT—into fully formatted WordPress posts. Simply copy the generated JSON file, paste it into the form provided by the plugin, and have your post created in seconds. This tool is perfect for streamlining your content creation process and publishing posts with minimal effort.

## Contributing

Contributions, bug reports, and feature requests are welcome! Please fork the repository and submit a pull request. You can also open issues on [GitHub](https://github.com/drhdev/json-post-importer).

## Changelog

### 1.3.1
- Removed the default author option; posts are now assigned to the currently logged-in user.
- Updated the backend documentation for Debug Mode.
- Enhanced inline descriptions on the settings page.

### 1.3
- Added options for custom redirect URL, default post type, debug mode, allowed roles, and JSON textarea rows.
- Improved security, error handling, and input validation.
- Enhanced documentation and inline help.

## License

This project is licensed under the GPL2. See the [LICENSE](LICENSE) file for details.
   
