import $ from 'jquery'
import axios from 'axios'

/**
 * Modal CRUD.
 *
 * A link marked data-modal has its target fetched with an X-Modal header, so
 * the server renders the same form without the app chrome (see modal_layout()).
 * The form is then submitted over AJAX: the controller answers with JSON when
 * the header is present, and the list behind the modal refreshes in place.
 *
 * Everything still works with JavaScript off — the links are real links and
 * the forms are real forms; this only intercepts them.
 *
 * Markup contract:
 *   <a href="/customers/create" data-modal>            open in a modal
 *   <button data-modal-delete data-url="..." data-message="...">
 *   <a data-modal-close>                               cancel inside a modal
 */

const SKELETON =
    '<div class="space-y-3 animate-pulse">' +
    '<div class="h-4 w-1/3 rounded bg-slate-200"></div>' +
    '<div class="h-11 rounded-lg bg-slate-200"></div>' +
    '<div class="h-11 rounded-lg bg-slate-200"></div>' +
    '<div class="h-11 w-2/3 rounded-lg bg-slate-200"></div>' +
    '</div>'

let lastFocused = null
let inFlight = null

function show() {
    lastFocused = document.activeElement
    $('#modal').removeClass('hidden')
    $('body').addClass('overflow-hidden')
    // Next frame, so the transition has a starting point to animate from.
    requestAnimationFrame(() => $('#modal-panel').removeClass('opacity-0 translate-y-2'))
}

export function closeModal() {
    inFlight?.abort()
    inFlight = null

    $('#modal-panel').addClass('opacity-0 translate-y-2')
    $('#modal').addClass('hidden')
    $('#modal-body').html(SKELETON)
    $('#modal-title').text('')
    $('body').removeClass('overflow-hidden')

    lastFocused?.focus?.()
    lastFocused = null
}

/** Fetch a create/edit form and put it in the dialog. */
export function openModal(url, title = '') {
    $('#modal-title').text(title)
    $('#modal-body').html(SKELETON)
    show()

    inFlight?.abort()
    const controller = new AbortController()
    inFlight = controller

    fetch(url, {
        headers: { 'X-Modal': '1', 'X-Requested-With': 'XMLHttpRequest' },
        signal: controller.signal,
    })
        .then((response) => {
            if (!response.ok) throw new Error(response.status)

            return response.text()
        })
        .then((html) => {
            const parsed = new DOMParser().parseFromString(html, 'text/html')
            const content = parsed.querySelector('[data-modal-content]')

            if (!content) {
                // Not a modal-aware screen — a redirect to the login page, say.
                window.location = url
                return
            }

            $('#modal-body').html(content.innerHTML)
            $('#modal-title').text(content.dataset.title || title)

            // Run any inline <script> the form shipped with (jQuery strips them).
            parsed.querySelectorAll('[data-modal-content] script').forEach((old) => {
                const script = document.createElement('script')
                script.textContent = old.textContent
                $('#modal-body')[0].appendChild(script)
            })

            $('#modal-body').find('input, select, textarea').filter(':visible').first().trigger('focus')
        })
        .catch((error) => {
            if (error.name === 'AbortError') return

            closeModal()
            window.location = url                     // fall back to the full page
        })
}

/** Refresh the list behind the modal without a page reload. */
function refreshList() {
    window.liveRefresh ? window.liveRefresh() : window.location.reload()
}

function fieldErrors($form, errors) {
    $form.find('[data-field-error]').remove()
    $form.find('.border-red-400').removeClass('border-red-400')

    Object.entries(errors).forEach(([field, messages]) => {
        // name="payment_methods.0" is reported for name="payment_methods[]".
        const selector = `[name="${field}"], [name="${field.split('.')[0]}[]"]`
        const $input = $form.find(selector).first()

        if (!$input.length) return

        $input.addClass('border-red-400')
        $input.after(`<span data-field-error class="block text-xs text-red-600 mt-1">${messages[0]}</span>`)
    })
}

function summary($form, errors) {
    const items = Object.values(errors)
        .map((messages) => `<div>${messages[0]}</div>`)
        .join('')

    $form.prepend(
        `<div data-field-error class="rounded-xl bg-red-50 text-red-700 text-sm p-4 space-y-1">${items}</div>`,
    )
}

export function modals() {
    $(document).on('click', 'a[data-modal]', function (event) {
        event.preventDefault()
        openModal(this.href, $(this).data('modal-title') || $(this).attr('title') || '')
    })

    // Cancel buttons inside a form that is being shown in a modal.
    $(document).on('click', '#modal-body [data-modal-close]', function (event) {
        event.preventDefault()
        closeModal()
    })

    $(document).on('click', '[data-modal-dismiss]', closeModal)

    $(document).on('keydown', function (event) {
        if (event.key !== 'Escape') return

        if (!$('#confirm-modal').hasClass('hidden')) return closeConfirm()
        if (!$('#modal').hasClass('hidden')) closeModal()
    })

    // Keep tabbing inside the dialog while it is open.
    $(document).on('keydown', '#modal', function (event) {
        if (event.key !== 'Tab') return

        const $focusable = $(this)
            .find('a[href], button, input, select, textarea, [tabindex]:not([tabindex="-1"])')
            .filter(':visible:not(:disabled)')

        if (!$focusable.length) return

        const first = $focusable[0]
        const last = $focusable[$focusable.length - 1]

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault()
            last.focus()
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault()
            first.focus()
        }
    })

    /* ---- Submitting ---- */

    $(document).on('submit', '#modal-body form', function (event) {
        event.preventDefault()

        const form = this
        const $form = $(form)

        if ($form.data('busy')) return

        const $button = $form.find('button[type=submit], button:not([type])').first()
        const original = $button.html()

        $form.data('busy', true)
        $form.find('[data-field-error]').remove()
        $button.prop('disabled', true).html('<span class="spinner"></span>')

        axios
            .post($form.attr('action'), new FormData(form), {
                headers: { 'X-Modal': '1', Accept: 'application/json' },
            })
            .then((response) => {
                closeModal()
                refreshList()

                if (response.data?.message) window.toast(response.data.message)
                if (response.data?.redirect) window.location = response.data.redirect
            })
            .catch((error) => {
                const status = error.response?.status

                if (status === 422) {
                    const errors = error.response.data.errors || {}
                    summary($form, errors)
                    fieldErrors($form, errors)
                    $('#modal-panel')[0].scrollIntoView({ block: 'start', behavior: 'smooth' })
                } else if (status === 419) {
                    window.location.reload()          // the session expired
                } else {
                    window.toast(error.response?.data?.message || window.__messages.error, 'error')
                }
            })
            .finally(() => {
                $form.data('busy', false)
                $button.prop('disabled', false).html(original)
            })
    })

    /* ---- Deleting ---- */

    $(document).on('click', '[data-modal-delete]', function (event) {
        event.preventDefault()

        const url = $(this).data('url')

        askConfirm($(this).data('message'), () => {
            axios
                .post(url, { _method: 'DELETE' }, { headers: { 'X-Modal': '1', Accept: 'application/json' } })
                .then((response) => {
                    closeModal()
                    refreshList()
                    if (response.data?.message) window.toast(response.data.message)
                })
                .catch((error) => {
                    window.toast(error.response?.data?.message || window.__messages.error, 'error')
                })
        })
    })
}

/* ---- Confirmation dialog ---- */

let onAccept = null

function closeConfirm() {
    $('#confirm-modal').addClass('hidden')
    onAccept = null
}

export function askConfirm(message, callback) {
    $('#confirm-message').text(message)
    $('#confirm-modal').removeClass('hidden')
    onAccept = callback
    $('#confirm-accept').trigger('focus')
}

$(function () {
    $(document).on('click', '[data-confirm-dismiss]', closeConfirm)

    $(document).on('click', '#confirm-accept', function () {
        const callback = onAccept
        closeConfirm()
        callback?.()
    })
})

window.openModal = openModal
window.closeModal = closeModal
window.askConfirm = askConfirm
