<div class="p-4">
    <div class="flex flex-col items-center space-y-4">
        <div class="bg-white p-4 rounded-lg border">
            {!! $qrCodeSvg !!}
        </div>
        <div class="text-center">
            <p class="text-sm font-medium mb-2">{{ __('enrollments.public_link') }}</p>
            <div class="flex items-center space-x-2">
                <input type="text" 
                       value="{{ $publicUrl }}" 
                       readonly 
                       class="flex-1 px-3 py-2 border rounded-md text-sm"
                       id="public-url-{{ $reference }}">
                <button onclick="copyToClipboard('public-url-{{ $reference }}')" 
                        class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 text-sm">
                    {{ __('enrollments.copy') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(elementId) {
    const input = document.getElementById(elementId);
    input.select();
    input.setSelectionRange(0, 99999);
    document.execCommand('copy');
    alert('{{ __('enrollments.copied') }}');
}
</script>

