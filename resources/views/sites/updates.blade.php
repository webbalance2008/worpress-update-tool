<x-app-layout>
    {{-- Site header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-sans text-xl font-medium text-white">{{ $site->name }}</h1>
            <p class="font-mono text-xs text-white/80 mt-0.5">Available Updates</p>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="border-b border-white/[0.08] mb-6">
        <nav class="flex gap-0">
            <a href="{{ route('sites.show', $site) }}" class="wb-nav-link">Overview</a>
            <a href="{{ route('sites.updates', $site) }}" class="wb-nav-link active">Updates</a>
            <a href="{{ route('sites.history', $site) }}" class="wb-nav-link">History</a>
            <a href="{{ route('sites.health', $site) }}" class="wb-nav-link">Health</a>
            <a href="{{ route('sites.errors', $site) }}" class="wb-nav-link">Errors</a>
        </nav>
    </div>

    @if($pendingUpdates->isEmpty())
        <div class="wb-card p-12 text-center">
            <p class="text-white/80 text-sm">Everything is up to date.</p>
        </div>
    @else
        <div x-data="{
            showModal: false,
            submitting: false,
            jobId: null,
            progressUrl: null,
            detailsUrl: null,
            status: 'pending',
            totalItems: 0,
            completedItems: 0,
            items: [],
            pollInterval: null,
            errorMessage: null,

            async submitUpdates() {
                const checked = [...document.querySelectorAll('input[name=&quot;installed_item_ids[]&quot;]:checked')];
                if (checked.length === 0) { alert('Select at least one item.'); return; }

                this.submitting = true;
                this.showModal = true;
                this.status = 'pending';
                this.completedItems = 0;
                this.items = [];
                this.errorMessage = null;
                this.totalItems = checked.length;

                try {
                    const formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');
                    checked.forEach(cb => formData.append('installed_item_ids[]', cb.value));

                    const response = await axios.post('{{ route('updates.trigger', $site) }}', formData, {
                        headers: { 'Accept': 'application/json' }
                    });

                    this.jobId = response.data.job_id;
                    this.progressUrl = response.data.progress_url;
                    this.detailsUrl = response.data.details_url;
                    this.startPolling();
                } catch (e) {
                    this.errorMessage = e.response?.data?.message || 'Failed to start update.';
                    this.submitting = false;
                }
            },

            startPolling() {
                this.pollInterval = setInterval(() => this.fetchProgress(), 2500);
            },

            async fetchProgress() {
                try {
                    const res = await axios.get(this.progressUrl);
                    this.status = res.data.status;
                    this.totalItems = res.data.total_items;
                    this.completedItems = res.data.completed_items;
                    this.items = res.data.items;

                    if (['completed', 'failed', 'partially_failed'].includes(this.status)) {
                        clearInterval(this.pollInterval);
                        this.submitting = false;
                    }
                } catch (e) {
                    // Silently retry on next poll
                }
            },

            get isFinished() {
                return ['completed', 'failed', 'partially_failed'].includes(this.status);
            },

            get progressPercent() {
                return this.totalItems > 0 ? Math.round((this.completedItems / this.totalItems) * 100) : 0;
            },

            get statusLabel() {
                const labels = {
                    pending: 'Queued...',
                    in_progress: 'Updating...',
                    completed: 'All Updates Complete',
                    failed: 'Updates Failed',
                    partially_failed: 'Some Updates Failed'
                };
                return labels[this.status] || this.status;
            },

            closeModal() {
                if (this.isFinished) {
                    this.showModal = false;
                    if (this.status === 'completed') {
                        window.location.reload();
                    }
                }
            }
        }">
            <form @submit.prevent="submitUpdates()">
                <div class="wb-card">
                    <div class="px-6 py-4 border-b border-white/[0.04] flex justify-between items-center">
                        <span class="wb-label">{{ $pendingUpdates->count() }} Available Updates</span>
                        <button type="submit" class="wb-btn-primary" :disabled="submitting">
                            <span x-show="!submitting">Update Selected</span>
                            <span x-show="submitting" x-cloak>Updating...</span>
                        </button>
                    </div>
                    @foreach($pendingUpdates->groupBy(fn($item) => $item->type->label()) as $typeLabel => $items)
                        <div class="px-6 py-2.5 bg-white/[0.02] border-b border-white/[0.04]">
                            <h3 class="wb-label text-wb-teal">{{ $typeLabel }}</h3>
                        </div>
                        @foreach($items as $item)
                            <label class="flex items-center px-6 py-3 border-b border-white/[0.04] hover:bg-white/[0.02] cursor-pointer transition-colors">
                                <input type="checkbox" name="installed_item_ids[]" value="{{ $item->id }}" class="rounded bg-wb-card border-white/20 text-wb-teal focus:ring-wb-teal/30 mr-4">
                                <div class="flex-1">
                                    <div class="text-sm text-white">{{ $item->name }}</div>
                                    <div class="text-xs text-white/20 font-mono">{{ $item->slug }}</div>
                                </div>
                                <div class="text-sm font-mono text-white/80">
                                    {{ $item->current_version }} &rarr; <span class="text-white">{{ $item->available_version }}</span>
                                </div>
                            </label>
                        @endforeach
                    @endforeach
                </div>
            </form>

            {{-- Progress Modal --}}
            <div x-show="showModal" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100">
                <div class="wb-card p-8 max-w-lg w-full mx-4 shadow-2xl" @click.outside="closeModal()">
                    {{-- Header --}}
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-sans font-semibold text-white" x-text="statusLabel"></h2>
                        <span class="font-mono text-sm text-white/60" x-text="completedItems + ' / ' + totalItems + ' items'"></span>
                    </div>

                    {{-- Progress bar --}}
                    <div class="w-full bg-white/[0.06] rounded-full h-2 mb-6 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500 ease-out"
                             :class="isFinished && status !== 'completed' ? 'bg-red-500' : 'bg-wb-teal'"
                             :style="'width: ' + progressPercent + '%'">
                        </div>
                    </div>

                    {{-- Queued state --}}
                    <div x-show="status === 'pending'" class="flex items-center gap-3 py-4 text-white/60">
                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span class="text-sm font-mono">Waiting for queue worker to pick up job...</span>
                    </div>

                    {{-- Item list --}}
                    <div x-show="items.length > 0" class="space-y-0 max-h-64 overflow-y-auto">
                        <template x-for="item in items" :key="item.id">
                            <div class="flex items-center justify-between py-3 border-b border-white/[0.04] last:border-0">
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    {{-- Status icon --}}
                                    <template x-if="item.status === 'completed'">
                                        <span class="text-emerald-400 flex-shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        </span>
                                    </template>
                                    <template x-if="item.status === 'failed'">
                                        <span class="text-red-400 flex-shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </span>
                                    </template>
                                    <template x-if="item.status !== 'completed' && item.status !== 'failed'">
                                        <span class="text-white/30 flex-shrink-0">
                                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                            </svg>
                                        </span>
                                    </template>
                                    <div class="min-w-0">
                                        <div class="text-sm text-white truncate" x-text="item.name"></div>
                                        <div class="text-xs font-mono text-white/40" x-text="item.type"></div>
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0 ml-4">
                                    <div class="text-xs font-mono text-white/60">
                                        <span x-text="item.old_version"></span>
                                        <span class="text-white/30">&rarr;</span>
                                        <span x-text="item.resulting_version || item.requested_version"
                                              :class="item.status === 'completed' ? 'text-emerald-400' : (item.status === 'failed' ? 'text-red-400' : 'text-white/60')"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Error messages for failed items --}}
                    <template x-for="item in items.filter(i => i.error_message)" :key="'err-' + item.id">
                        <div class="mt-3 text-xs text-red-400 bg-red-500/10 border border-red-500/20 p-3 rounded-md font-mono">
                            <span class="text-red-300" x-text="item.name + ': '"></span>
                            <span x-text="item.error_message"></span>
                        </div>
                    </template>

                    {{-- Error message for submission failure --}}
                    <div x-show="errorMessage" class="mt-4 text-sm text-red-400 bg-red-500/10 border border-red-500/20 p-3 rounded-md">
                        <span x-text="errorMessage"></span>
                    </div>

                    {{-- Footer --}}
                    <div class="mt-6 flex justify-end gap-3" x-show="isFinished || errorMessage">
                        <button @click="closeModal()" class="wb-btn-secondary">Close</button>
                        <a x-show="detailsUrl" :href="detailsUrl" class="wb-btn-primary">View Details</a>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-app-layout>
