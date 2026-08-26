import $ from 'jquery'

/**
 * Live filtering for the list screens.
 *
 * The server still renders everything, so filters and pagination keep working
 * exactly as they do without JavaScript — this just fetches the same page in
 * the background and swaps the results in.
 *
 * The filter form itself is deliberately left alone: replacing it under the
 * cursor would drop the user's focus and caret mid-word. Everything around it
 * (tabs, table, pagination) is refreshed.
 *
 * Markup contract:
 *   <div data-live-root>
 *     <form data-live> ... </form>     kept as-is
 *     ...anything else...              replaced
 *   </div>
 */
const ROOT = '[data-live-root]'

export function liveSearch() {
    let inFlight = null
    let timer

    window.liveRefresh = (url = window.location.href) => swap(url)

    function swap(url) {
        const $root = $(ROOT).first()

        if (!$root.length) return

        // A second keystroke cancels the request already on its way.
        inFlight?.abort()

        const controller = new AbortController()
        inFlight = controller
        $root.addClass('is-loading')

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal })
            .then((response) => response.text())
            .then((html) => {
                const fresh = new DOMParser().parseFromString(html, 'text/html').querySelector(ROOT)

                if (!fresh) {
                    window.location = url          // not a page we can swap; just go there
                    return
                }

                const $new = $(fresh).children()

                $root.children().each(function (index) {
                    const $old = $(this)

                    // Leave the form the user is typing into exactly where it is.
                    if ($old.is('[data-live]')) return

                    const $replacement = $new.eq(index)

                    $replacement.length ? $old.replaceWith($replacement) : $old.remove()
                })

                $root.removeClass('is-loading')
                window.history.replaceState({}, '', url)
            })
            .catch((error) => {
                if (error.name === 'AbortError') return

                $root.removeClass('is-loading')
                window.location = url              // fall back to a normal page load
            })
    }

    function urlFor($form) {
        const params = new URLSearchParams(new FormData($form[0]))

        // Blank filters do not belong in the address bar.
        for (const [key, value] of [...params]) {
            if (value === '') params.delete(key)
        }

        const query = params.toString()

        return ($form.attr('action') || window.location.pathname) + (query ? `?${query}` : '')
    }

    // Typing filters as you go.
    $(document).on('input', '[data-live] input[type="search"], [data-live] input[type="text"]', function () {
        const $form = $(this).closest('form')
        clearTimeout(timer)
        timer = setTimeout(() => swap(urlFor($form)), 300)
    })

    // Dropdowns, dates and checkboxes apply immediately.
    $(document).on('change', '[data-live] select, [data-live] input[type="date"], [data-live] input[type="checkbox"]',
        function () {
            swap(urlFor($(this).closest('form')))
        })

    // Submitting by hand should not reload the page either.
    $(document).on('submit', '[data-live]', function (event) {
        event.preventDefault()
        swap(urlFor($(this)))
    })

    // Pagination, and filter links that opt in with data-live-link.
    $(document).on(
        'click',
        `${ROOT} .pagination a, ${ROOT} nav[role="navigation"] a, ${ROOT} [data-live-link]`,
        function (event) {
            event.preventDefault()
            swap(this.href)
        },
    )
}
