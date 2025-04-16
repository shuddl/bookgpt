## How to Add the Book Chatbot Widget to Your WordPress Site

Follow these steps to embed the AI Book Recommender widget onto your website:

**Method 1: Using a Plugin (Recommended for ease and safety)**

1. **Install Plugin:**
    * Log in to your WordPress Admin Dashboard.
    * Navigate to `Plugins` > `Add New`.
    * Search for a plugin like "WPCode – Insert Headers and Footers + Custom Code Snippets" (by WPCode), "Insert Headers and Footers" (by WPBeginner), or a similar code management plugin.
    * Click "Install Now" and then "Activate" the plugin you choose.

2. **Add Script Snippet:**
    * Go to the settings page for the plugin you just installed (e.g., `Code Snippets` > `Header & Footer`, or `Settings` > `Insert Headers and Footers`).
    * Look for a section labeled "Footer", "Body", or "Scripts in Footer".

3. **Paste Snippet:**
    * Copy the following code snippet:

        ```html
        <!-- Good e-Reader Book Recommendation Chatbot Embed -->
        <script src="https://bookgptwp.vercel.app/script.js" defer></script>
        ```

    * Paste this snippet into the "Footer" or equivalent section. Adding it to the footer ensures it loads after your page content, which is generally preferred for performance.
    * *(**Important:** Ensure the URL `https://bookgptwp.vercel.app/script.js` is correct. If your deployment URL is different, update it here.)*

4. **Save Changes:**
    * Click the "Save Changes" or "Save Snippet" button within the plugin's settings.

5. **Verify:**
    * Clear any caching plugins you might be using on your WordPress site (e.g., WP Super Cache, W3 Total Cache) and also clear your browser cache.
    * Visit your website. The chatbot widget (which looks like a chat bubble) should now appear in the bottom-right corner.

**Method 2: Editing Theme Files (Advanced - Use a Child Theme!)**

*This method requires directly editing your theme's code. It's strongly recommended to use a **child theme** to avoid losing your changes when the parent theme updates. If you're unsure about this, please use Method 1.*

1. **Navigate to Theme Editor:**
    * In your WordPress Admin Dashboard, go to `Appearance` > `Theme File Editor`.
    * You might see a warning about editing theme files directly; proceed with caution.

2. **Select Child Theme & File:**
    * On the right side, ensure your **Child Theme** is selected (if you are using one).
    * Find and click on the `Theme Footer (footer.php)` file to open it for editing.

3. **Paste Snippet:**
    * Scroll to the bottom of the `footer.php` file.
    * Paste the following code snippet just **before** the closing `</body>` tag:

        ```html
        <!-- Good e-Reader Book Recommendation Chatbot Embed -->
        <script src="https://bookgptwp.vercel.app/script.js" defer></script>
        ```

    * *(**Important:** Double-check that the URL `https://bookgptwp.vercel.app/script.js` is correct.)*

4. **Update File:**
    * Click the "Update File" button.

5. **Verify:**
    * Clear all website and browser caches thoroughly.
    * Visit your site to confirm the chatbot widget is visible and working correctly.

---

If you encounter any problems or the widget doesn't appear, please double-check the script URL and ensure all caches have been cleared.
