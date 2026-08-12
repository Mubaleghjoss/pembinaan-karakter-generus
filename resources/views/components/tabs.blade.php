@props([
    'tabs' => [],
    'defaultTab' => null,
    'persistKey' => null,
    'syncQuery' => false,
    'contentClass' => '',
])

@php
    // Handle both formats: ['id' => 'tab1', 'label' => 'Tab 1'] or ['tab1' => 'Tab 1']
    $normalizedTabs = [];
    foreach ($tabs as $key => $tab) {
        if (is_array($tab) && isset($tab['id'])) {
            $normalizedTabs[$tab['id']] = $tab;
        } elseif (is_array($tab)) {
            $normalizedTabs[$key] = $tab;
        } else {
            $normalizedTabs[$key] = ['label' => $tab];
        }
    }
    
    $firstTabId = count($normalizedTabs) > 0 ? array_key_first($normalizedTabs) : null;
    $defaultTab = $defaultTab ?? $firstTabId;
    $persistKeyJs = $persistKey ? "'{$persistKey}'" : 'null';
    $validTabIds = json_encode(array_keys($normalizedTabs));
@endphp

<div x-data="{
    activeTab: '{{ $defaultTab }}',
    persistKey: {{ $persistKeyJs }},
    validTabs: {{ $validTabIds }},
    syncQuery: {{ $syncQuery ? 'true' : 'false' }},
    
    init() {
        // Check URL hash first
        const hash = window.location.hash.substring(1);
        if (hash && this.validTabs.includes(hash)) {
            this.activeTab = hash;
        } 
        // Then check localStorage
        else if (this.persistKey) {
            const stored = localStorage.getItem('tab_' + this.persistKey);
            if (stored && this.validTabs.includes(stored)) {
                this.activeTab = stored;
            }
        }
        
        // Listen for hash changes
        window.addEventListener('hashchange', () => {
            const newHash = window.location.hash.substring(1);
            if (newHash && this.validTabs.includes(newHash)) {
                this.activeTab = newHash;
            }
        });
    },
    
    setActiveTab(tab) {
        this.activeTab = tab;
        if (this.syncQuery) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            url.hash = tab;
            window.history.replaceState({}, '', url);
        } else {
            window.location.hash = tab;
        }
        if (this.persistKey) {
            localStorage.setItem('tab_' + this.persistKey, tab);
        }
    }
}" class="w-full">
    {{-- Tab Navigation --}}
    <div class="mb-6 overflow-x-auto">
        <nav class="flex min-w-max gap-3 pb-1" aria-label="Tabs">
            @foreach($normalizedTabs as $id => $tab)
                @php
                    $label = $tab['label'] ?? $id;
                    $icon = $tab['icon'] ?? null;
                    $badge = $tab['badge'] ?? null;
                @endphp
                
                <button
                    type="button"
                    @click="setActiveTab('{{ $id }}')"
                    :class="activeTab === '{{ $id }}' 
                        ? 'pkg-tab-link pkg-tab-link-active' 
                        : 'pkg-tab-link'"
                    class="whitespace-nowrap text-sm font-medium flex items-center gap-2"
                    :aria-selected="activeTab === '{{ $id }}'"
                    id="tab-{{ $id }}"
                    aria-controls="panel-{{ $id }}"
                    role="tab"
                >
                    @if($icon)
                        {!! $icon !!}
                    @endif
                    <span>{{ $label }}</span>
                    @if($badge)
                        <span
                            :class="activeTab === '{{ $id }}'
                                ? 'bg-white/20 text-white'
                                : 'bg-blue-600 text-white'"
                            class="inline-flex items-center justify-center rounded-full px-2 py-0.5 text-xs font-bold leading-none"
                        >
                            {{ $badge }}
                        </span>
                    @endif
                </button>
            @endforeach
        </nav>
    </div>

    {{-- Tab Content --}}
    <div class="{{ $contentClass }}">
        {{ $slot }}
    </div>
</div>
