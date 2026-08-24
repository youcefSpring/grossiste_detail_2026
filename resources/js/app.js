import $ from 'jquery'
import axios from 'axios'
import { bumpExisting, money, productSearch } from './product-search'

window.$ = window.jQuery = $
window.axios = axios

// Shared by the till, the purchase form and the exchange screen.
window.productSearch = productSearch
window.bumpExisting = bumpExisting
window.money = money

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.headers.common['X-CSRF-TOKEN'] =
    document.querySelector('meta[name="csrf-token"]')?.content

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
    },
})

/* ---- Shared UI helpers (kept tiny on purpose) ---- */

// Toast
window.toast = function (message, type = 'success') {
    const colors = { success: 'bg-emerald-600', error: 'bg-red-600', info: 'bg-slate-800' }
    const $t = $('<div>')
        .addClass(`${colors[type] || colors.info} text-white px-4 py-3 rounded-xl shadow-lg mb-2 text-sm`)
        .text(message)
    $('#toasts').append($t)
    setTimeout(() => $t.fadeOut(200, () => $t.remove()), 3500)
}

// Top progress bar, shown whenever the browser is fetching the next page.
window.pageLoading = function (on) {
    $('#page-loader').css({ opacity: on ? 1 : 0, width: on ? '80%' : '0%' })
}

// Tell inline page scripts that jQuery and the shared helpers are available.
window.__appReady = true
document.dispatchEvent(new Event('app:ready'))

$(function () {
    const isDesktop = () => window.matchMedia('(min-width: 1024px)').matches

    // One button: drawer on mobile, collapsible rail on desktop.
    $('#menu-toggle').on('click', function () {
        if (isDesktop()) {
            const collapsed = $('html').toggleClass('sidebar-collapsed').hasClass('sidebar-collapsed')
            localStorage.setItem('sidebar', collapsed ? 'collapsed' : 'open')
            return
        }

        $('#sidebar').removeClass('hidden').addClass('flex')
        $('#overlay').removeClass('hidden')
    })

    const closeDrawer = () => {
        $('#sidebar').addClass('hidden').removeClass('flex')
        $('#overlay').addClass('hidden')
    }

    $('#overlay, #menu-close').on('click', closeDrawer)

    // Leaving the drawer open while resizing up to desktop would strand it.
    $(window).on('resize', function () {
        if (isDesktop()) closeDrawer()
    })

    // Language switcher
    $('#locale-switch').on('change', function () {
        $('#locale-form input[name=locale]').val($(this).val())
        window.pageLoading(true)
        $('#locale-form').trigger('submit')
    })

    // Flash messages from the server
    $('[data-flash]').each(function () {
        window.toast($(this).data('message'), $(this).data('flash'))
    })

    // Confirm before destructive actions
    $(document).on('click', '[data-confirm]', function (e) {
        if (!window.confirm($(this).data('confirm'))) e.preventDefault()
    })

    /* ---- Loading feedback ---- */

    // Any submitted form: disable the button, show a spinner, run the progress bar.
    $(document).on('submit', 'form', function () {
        const $button = $(this).find('button[type=submit], button:not([type])').first()

        if ($button.length && !$button.data('no-spinner')) {
            $button.prop('disabled', true).prepend('<span class="spinner me-2"></span>')
        }

        window.pageLoading(true)
    })

    // Navigating away from the page.
    $(document).on('click', 'a[href]:not([target]):not([download])', function () {
        const href = $(this).attr('href')

        if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
            window.pageLoading(true)
        }
    })

    // Coming back via the back button must not leave the bar stuck on.
    $(window).on('pageshow', () => window.pageLoading(false))
})
