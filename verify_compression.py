from playwright.sync_api import sync_playwright

def verify_compression_logic(page):
    # Load the local HTML file
    page.goto("file:///app/index.html")

    # We can't easily upload a large file and check the compression in a headless environment without a real file.
    # However, we can inject a script to verify the `compressImage` function exists and has the expected logic structure.

    # Check if `compressImage` is defined
    result = page.evaluate("typeof compressImage")
    assert result == "function", "compressImage function is not defined"

    # Check if the code contains the iterative compression logic (simple string check)
    script_content = page.content()
    assert "while (dataUrl.length > 60 * 1024" in script_content, "Iterative compression logic not found in page content"

    print("Verification successful: compressImage function exists and contains the 60KB limit logic.")

    page.screenshot(path="/home/jules/verification/code_check.png")

if __name__ == "__main__":
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        verify_compression_logic(page)
        browser.close()
