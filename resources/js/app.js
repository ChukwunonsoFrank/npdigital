const LEGACY_HEAD_STYLESHEETS = ['assets/css/style.css']

const PRELINE_SELECTOR = [
    '[data-hs-overlay]',
    '[data-hs-tab]',
    '[data-hs-select]',
    '[data-hs-dropdown]',
    '[data-hs-collapse]',
    '[data-hs-tooltip]',
    '[data-hs-carousel]',
    '[data-hs-stepper]',
    '[data-hs-datepicker]',
    '[data-hs-file-upload]',
    '[data-hs-combo-box]',
    '[data-hs-accordion]',
    '[data-hs-pin-input]',
    '[data-hs-copy-markup]',
    '[data-hs-remove-element]',
    '[data-hs-scrollspy]',
    '[data-hs-theme-switch]',
    '[class*="hs-"]',
].join(', ')

let prelinePromise

function pageUsesPreline() {
    return document.querySelector(PRELINE_SELECTOR) !== null
}

function initPreline() {
    if (!pageUsesPreline()) {
        return
    }

    prelinePromise ??= import('preline')

    prelinePromise
        .then(() => {
            window.HSStaticMethods?.autoInit()
        })
        .catch((error) => {
            prelinePromise = undefined
            console.error('Unable to load Preline UI', error)
        })
}

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
    initPreline()
})

document.addEventListener('DOMContentLoaded', () => {
    removeStaleLegacyStylesheets()
    initPreline()
})
window.addEventListener('pageshow', removeStaleLegacyStylesheets)

removeStaleLegacyStylesheets()
