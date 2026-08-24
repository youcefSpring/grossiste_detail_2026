{{-- Sold lines with what is still returnable --}}
<div class="table-card table-scroll">
    <table class="table table-edit min-w-[620px]">
        <thead>
            <tr>
                <th>{{ __('product.fields.name') }}</th>
                <th class="num">{{ __('return.sold') }}</th>
                <th class="num">{{ __('return.returnable') }}</th>
                <th class="num w-32">{{ __('return.returning') }}</th>
                <th class="mid w-56">{{ __('return.condition') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $i => $item)
                @php($left = (float) $item->quantity - (float) $item->returned_quantity)
                <tr class="return-line {{ $left <= 0 ? 'opacity-40' : '' }}" data-price="{{ $item->unit_price / 100 }}">
                    <td>
                        <div class="font-medium">{{ $item->product_name }}</div>
                        <div class="text-xs text-slate-400 tabular-nums">{{ money($item->unit_price) }}</div>
                        <input type="hidden" name="items[{{ $i }}][sale_item_id]" value="{{ $item->id }}">
                    </td>
                    <td class="num text-slate-500"><bdi>{{ (float) $item->quantity }}</bdi></td>
                    <td class="num font-medium">{{ $left }}</td>
                    <td>
                        <input type="number" name="items[{{ $i }}][quantity]" class="qty w-full rounded-lg border-slate-300 px-2 py-2 text-end tabular-nums"
                               step="0.001" min="0" max="{{ $left }}" value="0" {{ $left <= 0 ? 'disabled' : '' }}>
                    </td>
                    <td>
                        <select name="items[{{ $i }}][condition]" class="w-full rounded-lg border-slate-300 py-2 ps-2 pe-8 text-sm">
                            @foreach (\App\Models\SaleReturn::CONDITIONS as $condition)
                                <option value="{{ $condition }}">{{ __('return.conditions.'.$condition) }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
