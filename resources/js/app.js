import 'preline'

const LEGACY_HEAD_STYLESHEETS = ['assets/css/style.css']

// `wire:navigate` only ever adds or merges <head> assets — it never removes
// ones the destination page doesn't use. Legacy pages load
// assets/css/style.css, which then lingers in <head> and overrides the
// dashboard theme once you navigate back. Strip it whenever the current page
// uses the dashboard shell.
function removeStaleLegacyStylesheets() {
    if (!document.querySelector('.np-shell')) {
        return
    }

    document.head.querySelectorAll('link[rel="stylesheet"]').forEach((link) => {
        const href = link.getAttribute('href') || ''

        if (LEGACY_HEAD_STYLESHEETS.some((path) => href.includes(path))) {
            link.remove()
        }
    })
}

document.addEventListener('livewire:navigated', () => {
    removeStaleLegacyStylesheets()

    // Re-initialize all Preline UI components
    if (window.HSStaticMethods) {
        window.HSStaticMethods.autoInit()
    }
})

document.addEventListener('DOMContentLoaded', removeStaleLegacyStylesheets)
window.addEventListener('pageshow', removeStaleLegacyStylesheets)

removeStaleLegacyStylesheets()
