@php
    $locale = app()->getLocale();
    $direction = $locale === 'ar' ? 'rtl' : 'ltr';
@endphp
<script>
    document.documentElement.setAttribute('dir', '{{ $direction }}');
    document.documentElement.setAttribute('lang', '{{ $locale }}');
</script>

