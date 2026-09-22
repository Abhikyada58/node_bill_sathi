/**
 * Global Translation Script via Google Translate API
 * Wraps the Google Translate element and provides a clean API for our custom dropdowns.
 */

document.addEventListener('DOMContentLoaded', () => {
    // Inject a hidden div for Google Translate to mount to
    const googleTranslateDiv = document.createElement('div');
    googleTranslateDiv.id = 'google_translate_element';
    googleTranslateDiv.style.display = 'none'; // Hide the default clunky widget
    document.body.appendChild(googleTranslateDiv);

    // Inject Google Translate script dynamically
    const googleTranslateScript = document.createElement('script');
    googleTranslateScript.type = 'text/javascript';
    googleTranslateScript.src = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
    document.head.appendChild(googleTranslateScript);
});

// Initialization callback function required by Google API
window.googleTranslateElementInit = function() {
    new google.translate.TranslateElement({
        pageLanguage: 'en',
        includedLanguages: 'en,gu,es',
        autoDisplay: false
    }, 'google_translate_element');
    
    // Restore saved language on load
    const savedLang = localStorage.getItem('finsnce_lang') || 'en';
    
    // Slight delay to allow Google's iframe to fully load before triggering the translation
    setTimeout(() => {
        if(savedLang !== 'en'){
           triggerTranslation(savedLang);
        }
        
        // Sync our custom dropdowns (if present on the page)
        const langSwitchers = document.querySelectorAll('.languageSwitcher, #languageSwitcher');
        langSwitchers.forEach(select => {
            select.value = savedLang;
            select.addEventListener('change', function(e) {
                const newLang = e.target.value;
                localStorage.setItem('finsnce_lang', newLang);
                triggerTranslation(newLang);
                
                // Sync other dropdowns if multiple exist
                langSwitchers.forEach(s => {
                    if (s !== e.target) s.value = newLang;
                });
            });
        });
    }, 1000);
};

/**
 * Programmatically triggers the Google Translate widget
 * @param {string} langCode - 'en', 'gu', 'es'
 */
function triggerTranslation(langCode) {
    const translateElement = document.querySelector('.goog-te-combo');
    if (translateElement) {
        translateElement.value = langCode;
        translateElement.dispatchEvent(new Event('change'));
    } else {
        // Fallback if the DOM structure changes slightly
        const select = document.getElementById('google_translate_element').querySelector('select');
        if (select) {
            select.value = langCode;
            select.dispatchEvent(new Event('change'));
        }
    }
}

// Global styles to hide the Google Translate top bar and tooltips
const style = document.createElement('style');
style.innerHTML = `
    .goog-te-banner-frame.skiptranslate { display: none !important; }
    body { top: 0px !important; }
    #goog-gt-tt { display: none !important; }
    .goog-text-highlight { background-color: transparent !important; box-shadow: none !important; }
`;
document.head.appendChild(style);
