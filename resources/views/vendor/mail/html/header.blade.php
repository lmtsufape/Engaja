<tr>
    <td class="header">
        @php($url = config('app.url'))
        <a href="{{ $url ?: '#' }}" target="_blank" rel="noopener" aria-label="{{ config('app.name', 'Engaja') }}">
            <img src="{{ asset('images/engaja-favicon.png') }}" class="logo" alt="{{ config('app.name', 'Engaja') }}">
            <span class="brand-text">{{ config('app.name', 'Engaja') }}</span>
        </a>
    </td>
</tr>
