<div class="mb-3">
    <label for="prizeLabel{{ $suffix }}" class="form-label fw-semibold">Label</label>
    <input type="text" name="label" id="prizeLabel{{ $suffix }}" class="form-control yg-input"
           value="{{ $prize->label ?? '' }}" placeholder="e.g. Rice 5kg, Power Bank, ₱500 Cash" maxlength="50" required>
</div>

<div class="mb-3">
    <label for="prizeType{{ $suffix }}" class="form-label fw-semibold">Type</label>
    <select name="prize_type" id="prizeType{{ $suffix }}" class="form-select yg-input" required>
        @foreach (\App\Models\Prize::TYPES as $value => $type)
            <option value="{{ $value }}" {{ ($prize->prize_type ?? 'foods') === $value ? 'selected' : '' }}>{{ $type['label'] }}</option>
        @endforeach
    </select>
</div>

<div class="mb-3" id="prizeAmountWrap{{ $suffix }}">
    <label for="prizeAmount{{ $suffix }}" class="form-label fw-semibold">Point amount</label>
    <input type="number" name="amount" id="prizeAmount{{ $suffix }}" class="form-control yg-input"
           value="{{ $prize->amount ?? '' }}" min="1" max="1000000" placeholder="e.g. 100">
    <div class="form-text">Only used when the prize type is Points.</div>
</div>

<div>
    <label for="prizeColor{{ $suffix }}" class="form-label fw-semibold">Segment Color</label>
    <input type="color" name="color" id="prizeColor{{ $suffix }}"
           class="form-control form-control-color yg-input w-100" style="height: 46px;"
           value="{{ $prize->color ?? '#9ACD32' }}" required>
    <input type="text" id="prizeColorHex{{ $suffix }}" class="form-control yg-input mt-2"
           value="{{ $prize->color ?? '#9ACD32' }}" pattern="^#[0-9A-Fa-f]{6}$"
           maxlength="7" spellcheck="false" aria-label="Segment color hex value" required>
    <div class="form-text">Choose a color or enter its six-digit hex value.</div>
</div>

<script>
(function () {
    const type = document.getElementById('prizeType{{ $suffix }}');
    const wrap = document.getElementById('prizeAmountWrap{{ $suffix }}');
    const amount = document.getElementById('prizeAmount{{ $suffix }}');
    const color = document.getElementById('prizeColor{{ $suffix }}');
    const colorHex = document.getElementById('prizeColorHex{{ $suffix }}');
    function toggleAmount() {
        const isPoints = type.value === 'points';
        wrap.hidden = !isPoints;
        amount.required = isPoints;
    }
    color.addEventListener('input', () => { colorHex.value = color.value; });
    colorHex.addEventListener('input', () => {
        if (/^#[0-9A-Fa-f]{6}$/.test(colorHex.value)) color.value = colorHex.value;
    });
    colorHex.form.addEventListener('submit', () => { color.value = colorHex.value; });
    type.addEventListener('change', toggleAmount);
    toggleAmount();
})();
</script>
