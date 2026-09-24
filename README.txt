=== ExamHub ===
Tags: education, exams, downloads, elementor
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage exam papers and answer sheets in WordPress, with taxonomy filters, download counters, and Elementor widgets.

== Description ==

ExamHub adds an exam management area to WordPress and Elementor widgets for displaying and filtering exam resources. The interface includes Persian labels and RTL styles.

Features:

* Organize exams by level, grade, field, subject, year, term, and exam type.
* Set an exam title, featured image, and featured flag.
* Select question and answer files from the Media Library or enter external resource URLs.
* Display tracked download links, direct viewing links, and download counters.
* Search and filter exams through AJAX without reloading the page.
* Configure card appearance and dark mode through Elementor controls.

Available Elementor widgets:

* Exam Showcase
* Category Showcase
* Download Library
* Featured Exams
* Search & Filter
* Exam Mega Library
* Exam Section
* Dark Mode Tokens

Exam management works without Elementor. The included page widgets require Elementor to be installed and active. Exams do not have a built-in public single page or archive; display them using the widgets on a page.

== Installation ==

1. Copy the complete examhub directory, including its subdirectories, into wp-content/plugins/.
2. Activate ExamHub from the WordPress Plugins screen.
3. Open the ExamHub management menu and create the categories needed for your exams. Configure parent relationships for dependent categories.
4. Add an exam, choose its categories, and provide question and answer resources. The current editor requires a file or URL for each resource section.
5. Publish the exam.
6. With Elementor active, edit a page and add widgets from the ExamHub category.
7. Configure the widget filters and display settings, then publish the page.

== Frequently Asked Questions ==

= Is Elementor required? =

Elementor is required for the included page widgets. Exam management and the download handler are available without it.

= Do visitors need an account to download files? =

No. Download links for published exams are available to logged-in and anonymous visitors.

= Can I use an external file URL? =

Yes. Each question or answer resource can reference a Media Library file or an external URL. External downloads are fetched through the WordPress server, with a redirect fallback when the fetch fails.

= What does the download count represent? =

Question and answer download requests have separate counters. Cards display their sum. These counters do not represent unique visitors or verified completed transfers; direct viewing links do not increment them.

= What happens when I deactivate or delete the plugin? =

Deactivation preserves the data. Deleting the plugin runs its uninstall routine to remove exam posts, ExamHub taxonomy terms, and migration settings. On multisite, that routine runs across the network's sites. Uploaded Media Library files are retained.

== Compatibility ==

The supported WordPress, PHP, and Elementor version matrix has not yet been verified. This README does not claim a tested WordPress version. Verify compatibility with the versions used on your site before release.

== Changelog ==

= 1.1.0 =

* Align the plugin header and translation template version with the existing 1.1.0 runtime version.
* Replace the template README with ExamHub features, setup instructions, data-removal behavior, and compatibility notes.

== Development ==

When changing the release version, update the Version header and EXAMHUB_VERSION constant in examhub.php, the Stable tag in README.txt, and Project-Id-Version in languages/examhub.pot together. Historical @since annotations and dated review documents describe earlier changes and are not current release metadata.
