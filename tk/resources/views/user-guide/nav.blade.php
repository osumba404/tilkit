@foreach ($nav as $group)
    @php $openKey = 's'.$group['section']; @endphp
    <div class="tk-guide-nav-group" x-show="groupVisible(@js($group))">
        <button
            type="button"
            class="tk-guide-nav-group-btn w-full"
            @click="toggle(@js($openKey))"
            :aria-expanded="open[@js($openKey)]"
        >
            <span>Section {{ $group['section'] }} · {{ $group['title'] }}</span>
            <svg class="tk-guide-nav-chevron" :class="{ 'is-open': open[@js($openKey)] }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
            </svg>
        </button>
        <ul x-show="open[@js($openKey)]" class="tk-guide-nav-list">
            @foreach ($group['items'] as $item)
                <li x-show="itemVisible(@js($item['label'].' '.$item['num']))">
                    <a
                        href="#{{ $item['id'] }}"
                        class="tk-guide-nav-link"
                        :class="{ 'is-active': active === @js($item['id']) }"
                        @click="active = @js($item['id'])"
                    ><span class="text-tk-accent font-medium">{{ $item['num'] }}</span> {{ $item['label'] }}</a>
                </li>
            @endforeach
        </ul>
    </div>
@endforeach
