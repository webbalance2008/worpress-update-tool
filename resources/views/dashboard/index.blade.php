<x-app-layout>
    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-4 p-4 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm font-body">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-sm font-body">
            {{ session('error') }}
        </div>
    @endif

    {{-- Stats cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="wb-card p-5">
            <div class="wb-label mb-2">Total Sites</div>
            <div class="wb-metric">{{ $stats['total_sites'] }}</div>
        </div>
        <div class="wb-card p-5">
            <div class="wb-label mb-2">Connected</div>
            <div class="wb-metric text-emerald-400">{{ $stats['connected_sites'] }}</div>
        </div>
        <div class="wb-card p-5">
            <div class="wb-label mb-2">Pending Updates</div>
            <div class="wb-metric text-orange-400">{{ $stats['total_pending_updates'] }}</div>
        </div>
        <div class="wb-card p-5">
            <div class="wb-label mb-2">Errors</div>
            <div class="wb-metric text-red-400">0</div>
        </div>
    </div>

    {{-- Action bar: Update All + Auto-Updates --}}
    <div class="flex flex-wrap items-center gap-4 mb-8">
        {{-- Update All Sites button --}}
        <button data-update-all-btn
                class="wb-btn-primary flex items-center gap-2"
                @if($stats['total_pending_updates'] === 0) disabled style="opacity:0.5;cursor:not-allowed" @endif>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Update All Sites
        </button>

        {{-- Push Plugin Update to All Sites --}}
        <button data-push-plugin-btn class="wb-btn-secondary flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            Push Plugin Update
        </button>

        {{-- Auto-Update Toggle --}}
        <form method="POST" action="{{ route('updates.toggle-auto') }}" class="flex items-center gap-3">
            @csrf
            <button type="submit" class="flex items-center gap-2 px-4 py-2 rounded-lg border transition-colors text-sm font-mono tracking-wide
                {{ $autoUpdatesEnabled
                    ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20'
                    : 'border-white/10 bg-white/[0.03] text-white/60 hover:bg-white/[0.06]' }}">
                <span class="w-2 h-2 rounded-full {{ $autoUpdatesEnabled ? 'bg-emerald-400' : 'bg-white/30' }}"></span>
                Auto-Updates: {{ $autoUpdatesEnabled ? 'ON' : 'OFF' }}
            </button>
        </form>

        @if($autoUpdatesEnabled)
            <span class="text-sm text-white/50 font-body">1 site updated per hour, rotating through all connected sites</span>
        @endif
    </div>

    {{-- Sites table --}}
    <div class="wb-card">
        <div class="px-6 py-4 border-b border-white/[0.04] flex justify-between items-center">
            <span class="wb-label">Connected Sites</span>
            <a href="{{ route('sites.create') }}" class="wb-btn-primary">+ Add Site</a>
        </div>

        @if($sites->isEmpty())
            <div class="p-12 text-center">
                <p class="text-white/80 font-body text-sm">No sites connected yet.</p>
                <a href="{{ route('sites.create') }}" class="text-wb-teal text-sm mt-2 inline-block hover:underline">Add your first site</a>
            </div>
        @else
            <table class="w-full">
                <thead>
                    <tr class="border-b border-white/[0.04]">
                        <th class="wb-table-header px-6 py-3">Site</th>
                        <th class="wb-table-header px-4 py-3">Status</th>
                        <th class="wb-table-header px-4 py-3">Updates</th>
                        <th class="wb-table-header px-4 py-3 text-right">Last Seen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sites as $site)
                        <tr class="border-b border-white/[0.04] hover:bg-white/[0.02] transition-colors cursor-pointer" onclick="window.location='{{ route('sites.show', $site) }}'">
                            <td class="px-6 py-3">
                                <div class="text-sm text-white font-medium">{{ $site->name }}</div>
                                <div class="text-sm text-white/70 font-mono">{{ $site->url }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @switch($site->status->value)
                                    @case('connected') <span class="wb-badge-green">Connected</span> @break
                                    @case('pending') <span class="wb-badge-yellow">Pending</span> @break
                                    @case('disconnected') <span class="wb-badge-gray">Disconnected</span> @break
                                    @case('error') <span class="wb-badge-red">Error</span> @break
                                @endswitch
                            </td>
                            <td class="px-4 py-3">
                                @if($site->pending_updates_count > 0)
                                    <span class="wb-badge-orange">{{ $site->pending_updates_count }} pending</span>
                                @else
                                    <span class="text-sm text-white/80 font-mono">Up to date</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="text-sm text-white/80 font-mono">
                                    @if($site->last_seen_at)
                                        {{ $site->last_seen_at->diffForHumans() }}
                                    @else
                                        Never
                                    @endif
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Update All Sites — Alpine.js component --}}
    <div x-data="{
        showConfirm: false,
        showProgress: false,
        confirmText: '',
        submitting: false,
        errorMessage: null,
        jobs: [],
        totalSites: 0,
        pollInterval: null,

        openConfirm() {
            this.showConfirm = true;
            this.confirmText = '';
            this.errorMessage = null;
        },

        closeConfirm() {
            this.showConfirm = false;
            this.confirmText = '';
        },

        async submitUpdateAll() {
            if (this.confirmText !== 'Update') return;
            this.submitting = true;
            this.errorMessage = null;

            try {
                const res = await axios.post('{{ route('updates.all-sites') }}', {
                    confirmation_text: 'Update'
                }, { headers: { 'Accept': 'application/json' } });

                this.jobs = res.data.jobs.map(j => ({
                    ...j,
                    status: 'pending',
                    completed_items: 0,
                    items: []
                }));
                this.totalSites = res.data.total_sites;
                this.showConfirm = false;
                this.showProgress = true;
                this.startPolling();
            } catch (e) {
                this.errorMessage = e.response?.data?.error || 'Failed to start updates.';
                this.submitting = false;
            }
        },

        startPolling() {
            this.pollInterval = setInterval(() => this.fetchAllProgress(), 2500);
        },

        async fetchAllProgress() {
            let allDone = true;
            for (let job of this.jobs) {
                if (['completed', 'failed', 'partially_failed'].includes(job.status)) continue;
                try {
                    const res = await axios.get(job.progress_url);
                    job.status = res.data.status;
                    job.completed_items = res.data.completed_items;
                    job.total_items = res.data.total_items;
                    job.items = res.data.items;
                } catch (e) { /* retry next poll */ }
                if (!['completed', 'failed', 'partially_failed'].includes(job.status)) {
                    allDone = false;
                }
            }
            if (allDone) {
                clearInterval(this.pollInterval);
                this.submitting = false;
            }
        },

        get isFinished() {
            return this.jobs.length > 0 && this.jobs.every(j =>
                ['completed', 'failed', 'partially_failed'].includes(j.status)
            );
        },

        get totalItems() {
            return this.jobs.reduce((sum, j) => sum + j.total_items, 0);
        },

        get completedItems() {
            return this.jobs.reduce((sum, j) => sum + (j.completed_items || 0), 0);
        },

        get progressPercent() {
            return this.totalItems > 0 ? Math.round((this.completedItems / this.totalItems) * 100) : 0;
        },

        get overallStatus() {
            if (!this.isFinished) {
                return this.jobs.some(j => j.status === 'in_progress') ? 'Updating...' : 'Queued...';
            }
            const anyFailed = this.jobs.some(j => ['failed', 'partially_failed'].includes(j.status));
            const allFailed = this.jobs.every(j => j.status === 'failed');
            if (allFailed) return 'All Updates Failed';
            if (anyFailed) return 'Some Updates Failed';
            return 'All Updates Complete';
        },

        closeProgress() {
            if (this.isFinished) {
                this.showProgress = false;
                window.location.reload();
            }
        },

        // --- Push Plugin Update ---
        showPush: false,
        pushInProgress: false,
        pushResults: [],

        async pushPluginUpdate() {
            this.showPush = true;
            this.pushInProgress = true;
            this.pushResults = [];

            try {
                const res = await axios.post('{{ route('sites.push-plugin-update-all') }}', {}, {
                    headers: { 'Accept': 'application/json' }
                });
                this.pushResults = res.data.sites;
            } catch (e) {
                this.pushResults = [{ name: 'Error', success: false, error: e.response?.data?.message || 'Request failed.' }];
            }

            this.pushInProgress = false;
        },

        closePush() {
            this.showPush = false;
            window.location.reload();
        }
    }"
    x-init="
        document.querySelector('[data-update-all-btn]')?.addEventListener('click', () => openConfirm());
        document.querySelector('[data-push-plugin-btn]')?.addEventListener('click', () => pushPluginUpdate());
    ">

        {{-- Confirmation Modal --}}
        <div x-show="showConfirm" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100">
            <div class="wb-card p-8 max-w-md w-full mx-4 shadow-2xl" @click.outside="closeConfirm()">
                <h2 class="text-lg font-sans font-semibold text-white mb-2">Update All Sites</h2>
                <p class="text-sm text-white/70 font-body mb-1">
                    This will update <strong class="text-white">all connected sites</strong> in order:
                </p>
                <ol class="text-sm text-white/70 font-body mb-4 list-decimal list-inside space-y-1">
                    <li>WordPress Core</li>
                    <li>Themes</li>
                    <li>Plugins</li>
                </ol>
                <p class="text-sm text-orange-400 font-body mb-4">
                    Type <strong class="text-white font-mono">Update</strong> below to confirm.
                </p>

                <input type="text"
                       x-model="confirmText"
                       class="wb-input w-full mb-4"
                       placeholder="Type Update to confirm"
                       autocomplete="off"
                       @keydown.enter="submitUpdateAll()">

                <div x-show="errorMessage" class="mb-4 text-sm text-red-400 bg-red-500/10 border border-red-500/20 p-3 rounded-md">
                    <span x-text="errorMessage"></span>
                </div>

                <div class="flex gap-3 justify-end">
                    <button @click="closeConfirm()" class="wb-btn-secondary">Cancel</button>
                    <button @click="submitUpdateAll()"
                            :disabled="confirmText !== 'Update' || submitting"
                            class="px-4 py-2 rounded-lg bg-red-600 text-white font-mono text-sm tracking-wide hover:bg-red-500 transition-colors disabled:opacity-30 disabled:cursor-not-allowed">
                        <span x-show="!submitting">Confirm Update All</span>
                        <span x-show="submitting" x-cloak>Starting...</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Progress Modal --}}
        <div x-show="showProgress" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100">
            <div class="wb-card p-8 max-w-2xl w-full mx-4 shadow-2xl" @click.outside="closeProgress()">
                {{-- Header --}}
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-sans font-semibold text-white" x-text="overallStatus"></h2>
                    <span class="font-mono text-sm text-white/60" x-text="completedItems + ' / ' + totalItems + ' items'"></span>
                </div>

                {{-- Overall progress bar --}}
                <div class="w-full bg-white/[0.06] rounded-full h-2 mb-6 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500 ease-out"
                         :class="isFinished && overallStatus !== 'All Updates Complete' ? 'bg-red-500' : 'bg-wb-teal'"
                         :style="'width: ' + progressPercent + '%'">
                    </div>
                </div>

                {{-- Per-site breakdown --}}
                <div class="space-y-4 max-h-96 overflow-y-auto">
                    <template x-for="job in jobs" :key="job.job_id">
                        <div class="border border-white/[0.06] rounded-lg p-4">
                            {{-- Site header --}}
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <template x-if="job.status === 'completed'">
                                        <span class="text-emerald-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg></span>
                                    </template>
                                    <template x-if="job.status === 'failed' || job.status === 'partially_failed'">
                                        <span class="text-red-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></span>
                                    </template>
                                    <template x-if="job.status !== 'completed' && job.status !== 'failed' && job.status !== 'partially_failed'">
                                        <span class="text-white/30"><svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></span>
                                    </template>
                                    <span class="text-sm font-medium text-white" x-text="job.site_name"></span>
                                </div>
                                <span class="font-mono text-xs text-white/40" x-text="(job.completed_items || 0) + ' / ' + job.total_items"></span>
                            </div>

                            {{-- Site progress bar --}}
                            <div class="w-full bg-white/[0.04] rounded-full h-1 mb-2 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500 ease-out"
                                     :class="['failed','partially_failed'].includes(job.status) ? 'bg-red-500' : 'bg-wb-teal'"
                                     :style="'width: ' + (job.total_items > 0 ? Math.round(((job.completed_items||0) / job.total_items) * 100) : 0) + '%'">
                                </div>
                            </div>

                            {{-- Item list --}}
                            <div x-show="job.items && job.items.length > 0" class="space-y-0 mt-2">
                                <template x-for="item in job.items" :key="item.id">
                                    <div class="flex items-center justify-between py-1.5 border-b border-white/[0.03] last:border-0">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <template x-if="item.status === 'completed'">
                                                <span class="text-emerald-400 flex-shrink-0"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></span>
                                            </template>
                                            <template x-if="item.status === 'failed'">
                                                <span class="text-red-400 flex-shrink-0"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg></span>
                                            </template>
                                            <template x-if="item.status !== 'completed' && item.status !== 'failed'">
                                                <span class="text-white/20 flex-shrink-0"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"/></svg></span>
                                            </template>
                                            <span class="text-xs text-white/70 truncate" x-text="item.name"></span>
                                        </div>
                                        <span class="text-xs font-mono text-white/40 ml-2 flex-shrink-0"
                                              :class="item.status === 'completed' ? 'text-emerald-400' : (item.status === 'failed' ? 'text-red-400' : '')"
                                              x-text="item.old_version + ' → ' + (item.resulting_version || item.requested_version)"></span>
                                    </div>
                                </template>
                            </div>

                            {{-- Error messages --}}
                            <template x-for="item in (job.items || []).filter(i => i.error_message)" :key="'err-' + item.id">
                                <div class="mt-2 text-xs text-red-400 bg-red-500/10 border border-red-500/20 p-2 rounded font-mono">
                                    <span class="text-red-300" x-text="item.name + ': '"></span>
                                    <span x-text="item.error_message"></span>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                {{-- Footer --}}
                <div class="mt-6 flex justify-end" x-show="isFinished">
                    <button @click="closeProgress()" class="wb-btn-secondary">Close</button>
                </div>
            </div>
        </div>
        {{-- Push Plugin Update Modal --}}
        <div x-show="showPush" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100">
            <div class="wb-card p-8 max-w-lg w-full mx-4 shadow-2xl" @click.outside="!pushInProgress && closePush()">
                {{-- Header --}}
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-sans font-semibold text-white">Push Plugin Update</h2>
                    <span x-show="!pushInProgress" class="font-mono text-sm text-white/60"
                          x-text="pushResults.filter(r => r.success).length + ' / ' + pushResults.length + ' sites'"></span>
                </div>

                {{-- Loading state --}}
                <div x-show="pushInProgress" class="flex items-center gap-3 py-8 justify-center text-white/60">
                    <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span class="text-sm font-mono">Pushing plugin update to all sites...</span>
                </div>

                {{-- Results --}}
                <div x-show="!pushInProgress && pushResults.length > 0" class="space-y-0">
                    <template x-for="site in pushResults" :key="site.name">
                        <div class="flex items-center justify-between py-3 border-b border-white/[0.04] last:border-0">
                            <div class="flex items-center gap-3 min-w-0">
                                <template x-if="site.success">
                                    <span class="text-emerald-400 flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                </template>
                                <template x-if="!site.success">
                                    <span class="text-red-400 flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </span>
                                </template>
                                <div class="min-w-0">
                                    <div class="text-sm text-white truncate" x-text="site.name"></div>
                                    <div class="text-xs font-mono text-white/40 truncate" x-text="site.url"></div>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0 ml-4">
                                <template x-if="site.success && site.new_version">
                                    <span class="text-xs font-mono text-emerald-400" x-text="'v' + site.new_version"></span>
                                </template>
                                <template x-if="!site.success">
                                    <span class="text-xs font-mono text-red-400">Failed</span>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Error details --}}
                    <template x-for="site in pushResults.filter(s => !s.success && s.error)" :key="'push-err-' + site.name">
                        <div class="mt-3 text-xs text-red-400 bg-red-500/10 border border-red-500/20 p-3 rounded-md font-mono">
                            <span class="text-red-300" x-text="site.name + ': '"></span>
                            <span x-text="site.error"></span>
                        </div>
                    </template>
                </div>

                {{-- Footer --}}
                <div class="mt-6 flex justify-end" x-show="!pushInProgress">
                    <button @click="closePush()" class="wb-btn-secondary">Close</button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
