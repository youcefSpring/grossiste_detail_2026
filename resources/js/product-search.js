import $ from 'jquery'

/** Money on screen: parse what the user typed, print what the shop expects. */
export const money = {
    parse: (value) => parseFloat(String(value).replace(',', '.')) || 0,
    format: (value) =>
        value.toLocaleString('fr-DZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
}

/**
 * The scan-or-search box used by the till, the purchase form and the exchange screen.
 *
 * A barcode scanner types the whole code then presses Enter, so an exact single match
 * is added straight away and the dropdown never appears.
 */
export function productSearch({ input, results, url, onPick, meta, emptyText }) {
    const $input = $(input)
    const $results = $(results)
    let timer

    const hide = () => $results.addClass('hidden').empty()

    $input.on('input', function () {
        const term = $(this).val().trim()
        clearTimeout(timer)

        if (term.length < 2) return hide()

        timer = setTimeout(() => {
            $.getJSON(url, { q: term }, function (products) {
                const scanned =
                    products.length === 1 &&
                    (products[0].barcode === term || products[0].sku === term)

                if (scanned) {
                    $input.val('')
                    hide()
                    return onPick(products[0])
                }

                $results.empty().removeClass('hidden')

                if (!products.length) {
                    return $results.append($('<div class="p-4 text-sm text-slate-400">').text(emptyText))
                }

                products.forEach((product) => {
                    $('<button type="button">')
                        .addClass('w-full text-start px-4 py-3 hover:bg-slate-50 flex justify-between gap-3')
                        .append($('<span>').text(product.name))
                        .append($('<span class="text-xs text-slate-400 whitespace-nowrap">').text(meta(product)))
                        .on('click', function () {
                            $input.val('').trigger('focus')
                            hide()
                            onPick(product)
                        })
                        .appendTo($results)
                })
            })
        }, 180)
    })

    // A scanner's trailing Enter must not submit the form half-filled.
    $input.on('keydown', (event) => {
        if (event.key === 'Enter') event.preventDefault()
    })

    $(document).on('click', (event) => {
        if (!$(event.target).closest(`${input}, ${results}`).length) hide()
    })
}

/** Adding the same product twice bumps its quantity instead of stacking rows. */
export function bumpExisting($rows, productId) {
    const $row = $rows.filter(function () {
        return $(this).find('.f-product').val() === String(productId)
    })

    if (!$row.length) return false

    const $qty = $row.find('.qty')
    $qty.val(money.parse($qty.val()) + 1)
    $row.addClass('bg-emerald-50')
    setTimeout(() => $row.removeClass('bg-emerald-50'), 400)

    return true
}
