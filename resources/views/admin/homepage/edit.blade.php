@extends('layouts.app')

@section('title', 'Homepage Management')

@section('content')
<style>
    body.dark-mode .homepage-management-page .slide-card > div {
        background: #172554;
        border-color: #64748b !important;
    }

    body.dark-mode .homepage-management-page .slide-card h6,
    body.dark-mode .homepage-management-page .slide-card .form-label {
        color: #f8fafc !important;
    }

    body.dark-mode .homepage-management-page .table thead th {
        background: #111827 !important;
        color: #f8fafc !important;
    }

    body.dark-mode .homepage-management-page .table tbody td {
        background: #273449 !important;
        color: #f8fafc !important;
    }

    body.dark-mode .homepage-management-page .card-footer {
        background: #1e293b !important;
        color: #cbd5e1 !important;
    }
</style>
<div class="container-fluid homepage-management-page">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Homepage Management</h1>
            <p class="text-muted mb-0">Update public homepage content without changing EMS permissions or system behavior.</p>
        </div>
        <a href="{{ route('landing') }}" target="_blank" class="btn btn-outline-primary">
            <i class="fas fa-external-link-alt me-1"></i> Preview Homepage
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Please check the form.</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.homepage.update') }}">
        @csrf
        @method('PUT')
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header bg-white"><strong><i class="fas fa-pen me-2"></i>Public Content</strong></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="homepage_hero_title" class="form-label">Hero title</label>
                            <input id="homepage_hero_title" name="homepage_hero_title" class="form-control" maxlength="180" required value="{{ old('homepage_hero_title', $settings['homepage_hero_title'] ?? '') }}">
                        </div>
                        <div class="mb-3">
                            <label for="homepage_hero_text" class="form-label">Hero description</label>
                            <textarea id="homepage_hero_text" name="homepage_hero_text" class="form-control" rows="3" maxlength="500" required>{{ old('homepage_hero_text', $settings['homepage_hero_text'] ?? '') }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label for="homepage_about_text" class="form-label">About text</label>
                            <textarea id="homepage_about_text" name="homepage_about_text" class="form-control" rows="5" maxlength="1000" required>{{ old('homepage_about_text', $settings['homepage_about_text'] ?? '') }}</textarea>
                        </div>
                        <div>
                            <label for="homepage_support_text" class="form-label">Support/contact text</label>
                            <textarea id="homepage_support_text" name="homepage_support_text" class="form-control" rows="3" maxlength="500" required>{{ old('homepage_support_text', $settings['homepage_support_text'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header bg-white"><strong><i class="fas fa-image me-2"></i>Branding &amp; Background</strong></div>
                    <div class="card-body">
                        <p class="small text-muted">Optional public image URLs. Leave blank to use the current local logo and background.</p>
                        <div class="mb-3">
                            <label for="homepage_logo_url" class="form-label">Logo URL</label>
                            <input id="homepage_logo_url" name="homepage_logo_url" type="url" class="form-control" maxlength="1000" placeholder="https://..." value="{{ old('homepage_logo_url', $settings['homepage_logo_url'] ?? '') }}">
                        </div>
                        <div class="mb-3">
                            <label for="homepage_background_url" class="form-label">Background image URL</label>
                            <input id="homepage_background_url" name="homepage_background_url" type="url" class="form-control" maxlength="1000" placeholder="https://..." value="{{ old('homepage_background_url', $settings['homepage_background_url'] ?? '') }}">
                        </div>
                        <div class="alert alert-info small mb-0">
                            <i class="fas fa-circle-info me-1"></i> Branch names, IDs, and addresses on the homepage come from Branch Management automatically.
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-1"></i> Save Homepage Changes</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong><i class="fas fa-images me-2"></i>Homepage Carousel</strong>
                <button type="button" class="btn btn-sm btn-outline-primary" id="add-slide">
                    <i class="fas fa-plus me-1"></i> Add Slide
                </button>
            </div>
            <div class="card-body">
                <p class="small text-muted">Existing slides stay until you remove them. Add as many slides as needed, then save all homepage changes.</p>
                <div class="row g-4" id="slides-container">
                    @foreach($slides as $slideId => $slide)
                        <div class="col-lg-4 slide-card" data-slide-id="{{ $slideId }}" data-archive-url="{{ route('admin.homepage.slides.archive', $slideId) }}">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">Slide <span class="slide-number">{{ $loop->iteration }}</span></h6>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-slide"><i class="fas fa-trash"></i></button>
                                </div>
                                <input type="hidden" name="slides[{{ $slideId }}][remove]" value="0" class="remove-flag">
                                <div class="mb-3">
                                    <label class="form-label">Image URL</label>
                                    <input name="slides[{{ $slideId }}][image]" type="url" class="form-control" required value="{{ old('slides.'.$slideId.'.image', $slide['image'] ?? '') }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Slide heading</label>
                                    <input name="slides[{{ $slideId }}][title]" class="form-control" maxlength="120" required value="{{ old('slides.'.$slideId.'.title', $slide['title'] ?? '') }}">
                                </div>
                                <div>
                                    <label class="form-label">Slide description</label>
                                    <textarea name="slides[{{ $slideId }}][text]" class="form-control" rows="3" maxlength="300" required>{{ old('slides.'.$slideId.'.text', $slide['text'] ?? '') }}</textarea>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </form>

    <template id="slide-template">
        <div class="col-lg-4 slide-card" data-slide-id="__ID__">
            <div class="border rounded p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Slide <span class="slide-number"></span></h6>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-slide"><i class="fas fa-trash"></i></button>
                </div>
                <input type="hidden" name="slides[__ID__][remove]" value="0" class="remove-flag">
                <div class="mb-3"><label class="form-label">Image URL</label><input name="slides[__ID__][image]" type="url" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Slide heading</label><input name="slides[__ID__][title]" class="form-control" maxlength="120" required></div>
                <div><label class="form-label">Slide description</label><textarea name="slides[__ID__][text]" class="form-control" rows="3" maxlength="300" required></textarea></div>
            </div>
        </div>
    </template>

    <script>
        (() => {
            const container = document.getElementById('slides-container');
            const template = document.getElementById('slide-template');
            let nextId = Math.max(0, ...Array.from(container.querySelectorAll('.slide-card')).map((card) => Number(card.dataset.slideId) || 0)) + 1;

            const renumber = () => container.querySelectorAll('.slide-card').forEach((card, index) => {
                card.querySelector('.slide-number').textContent = index + 1;
            });

            document.getElementById('add-slide').addEventListener('click', () => {
                container.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__ID__', nextId++));
                renumber();
            });

            container.addEventListener('click', (event) => {
                const button = event.target.closest('.remove-slide');
                if (!button) return;
                const cards = container.querySelectorAll('.slide-card');
                if (cards.length === 1) {
                    alert('Keep at least one homepage carousel slide.');
                    return;
                }
                const card = button.closest('.slide-card');
                const archiveUrl = card.dataset.archiveUrl;

                if (!archiveUrl) {
                    card.remove();
                    renumber();
                    return;
                }

                button.disabled = true;
                fetch(archiveUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                })
                    .then(async (response) => {
                        const payload = await response.json().catch(() => ({}));
                        if (!response.ok) throw new Error(payload.message || 'The slide could not be archived.');
                        return payload;
                    })
                    .then(() => {
                        card.remove();
                        renumber();
                        window.location.reload();
                    })
                    .catch((error) => {
                        button.disabled = false;
                        alert(error.message);
                    });
            });
        })();
    </script>

    <div class="card mt-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-building me-2"></i>Homepage Branches</strong>
            <a href="{{ route('admin.branches.create') }}" class="btn btn-sm btn-primary">
                <i class="fas fa-plus me-1"></i> Add School Branch
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>School Name</th>
                        <th>School ID</th>
                        <th>Address</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($branches as $branch)
                        <tr>
                            <td class="fw-semibold">{{ $branch->branch_name }}</td>
                            <td><span class="badge bg-light text-dark">{{ $branch->branch_code }}</span></td>
                            <td>{{ $branch->address ?: 'No address provided' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.branches.edit', $branch) }}" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit me-1"></i> Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No school branches found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white text-muted small">
            These values are displayed automatically in the public homepage branch cards.
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-box-archive me-2"></i>Carousel Archives</strong>
            <span class="badge bg-secondary">{{ $archives->count() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Slide</th>
                        <th>Heading</th>
                        <th>Archived By</th>
                        <th>Archived At</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($archives as $archive)
                        <tr>
                            <td><img src="{{ $archive->image }}" alt="" style="width: 96px; height: 54px; object-fit: cover; border-radius: 6px;"></td>
                            <td>
                                <strong>{{ $archive->title }}</strong><br>
                                <small class="text-muted">{{ $archive->text }}</small>
                            </td>
                            <td>{{ $archive->archivedBy?->name ?? 'System Admin' }}</td>
                            <td>{{ $archive->archived_at?->format('M d, Y h:i A') }}</td>
                            <td class="text-end text-nowrap">
                                <form method="POST" action="{{ route('admin.homepage.archives.restore', $archive) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-rotate-left me-1"></i> Restore</button>
                                </form>
                                <form method="POST" action="{{ route('admin.homepage.archives.destroy', $archive) }}" class="d-inline" onsubmit="return confirm('Permanently delete this archived slide?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash me-1"></i> Delete Permanently</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No archived slides.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
