@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-flex; align-items: center; gap: 10px; text-decoration: none;">
    <span style="width: 30px; height: 30px; background: #1a56db; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; vertical-align: middle;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
    </span>
    <span style="font-size: 15px; font-weight: 600; color: #111111; letter-spacing: -0.2px; vertical-align: middle;">{!! $slot !!}</span>
</a>
</td>
</tr>
