<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\HomepageSlideArchive;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomepageController extends Controller
{
    private const SETTING_KEYS = [
        'homepage_hero_title',
        'homepage_hero_text',
        'homepage_about_text',
        'homepage_support_text',
        'homepage_logo_url',
        'homepage_background_url',
    ];

    private function authorizeHomepageManagement(): void
    {
        abort_unless(Auth::user()?->isSuperAdmin(), 403, 'Only Super Admins can manage the homepage.');
    }

    public function edit()
    {
        $this->authorizeHomepageManagement();

        $settings = SiteSetting::query()->whereIn('key', self::SETTING_KEYS)->pluck('value', 'key');
        $slideSettings = SiteSetting::query()
            ->where('key', 'like', 'homepage_slide_%')
            ->pluck('value', 'key');
        $slides = [];

        foreach ($slideSettings as $key => $value) {
            if (preg_match('/^homepage_slide_(\d+)_(image|title|text)$/', $key, $matches)) {
                $slides[(int) $matches[1]][$matches[2]] = $value;
            }
        }

        ksort($slides);
        $archives = HomepageSlideArchive::with('archivedBy')->latest('archived_at')->get();
        $branches = Branch::query()->orderBy('branch_name')->get();

        return view('admin.homepage.edit', compact('settings', 'slides', 'archives', 'branches'));
    }

    public function update(Request $request)
    {
        $this->authorizeHomepageManagement();

        $validated = $request->validate([
            'homepage_hero_title' => ['required', 'string', 'max:180'],
            'homepage_hero_text' => ['required', 'string', 'max:500'],
            'homepage_about_text' => ['required', 'string', 'max:1000'],
            'homepage_support_text' => ['required', 'string', 'max:500'],
            'homepage_logo_url' => ['nullable', 'url', 'max:1000'],
            'homepage_background_url' => ['nullable', 'url', 'max:1000'],
        ]);

        foreach (self::SETTING_KEYS as $key) {
            SiteSetting::setValue($key, $validated[$key] ?? null);
        }

        $slides = $request->input('slides', []);
        $activeSlides = collect($slides)->reject(fn ($slide) => !empty($slide['remove']))->count();

        if ($activeSlides === 0) {
            return back()->withErrors(['slides' => 'Keep at least one homepage carousel slide.'])->withInput();
        }

        foreach ($slides as $slideId => $slide) {
            if (!empty($slide['remove'])) {
                $slideData = [
                    'image' => SiteSetting::getValue("homepage_slide_{$slideId}_image"),
                    'title' => SiteSetting::getValue("homepage_slide_{$slideId}_title"),
                    'text' => SiteSetting::getValue("homepage_slide_{$slideId}_text"),
                ];

                if ($slideData['image'] && $slideData['title'] && $slideData['text']) {
                    HomepageSlideArchive::create([
                        'original_slide_id' => $slideId,
                        ...$slideData,
                        'archived_by' => Auth::id(),
                        'archived_at' => now(),
                    ]);
                }

                SiteSetting::query()
                    ->whereIn('key', [
                        "homepage_slide_{$slideId}_image",
                        "homepage_slide_{$slideId}_title",
                        "homepage_slide_{$slideId}_text",
                    ])
                    ->delete();
                continue;
            }

            validator($slide, [
                'image' => ['required', 'url', 'max:1000'],
                'title' => ['required', 'string', 'max:120'],
                'text' => ['required', 'string', 'max:300'],
            ])->validate();

            $activeSlides++;
            SiteSetting::setValue("homepage_slide_{$slideId}_image", $slide['image']);
            SiteSetting::setValue("homepage_slide_{$slideId}_title", $slide['title']);
            SiteSetting::setValue("homepage_slide_{$slideId}_text", $slide['text']);
        }

        return redirect()->route('admin.homepage.edit')->with('success', 'Homepage content updated successfully.');
    }

    public function restoreArchive(HomepageSlideArchive $archive)
    {
        $this->authorizeHomepageManagement();

        SiteSetting::setValue("homepage_slide_{$archive->original_slide_id}_image", $archive->image);
        SiteSetting::setValue("homepage_slide_{$archive->original_slide_id}_title", $archive->title);
        SiteSetting::setValue("homepage_slide_{$archive->original_slide_id}_text", $archive->text);
        $archive->delete();

        return back()->with('success', 'Archived carousel slide restored.');
    }

    public function destroyArchive(HomepageSlideArchive $archive)
    {
        $this->authorizeHomepageManagement();
        $archive->delete();

        return back()->with('success', 'Archived carousel slide permanently deleted.');
    }

    public function archiveSlide(int $slideId)
    {
        $this->authorizeHomepageManagement();

        $slideData = [
            'image' => SiteSetting::getValue("homepage_slide_{$slideId}_image"),
            'title' => SiteSetting::getValue("homepage_slide_{$slideId}_title"),
            'text' => SiteSetting::getValue("homepage_slide_{$slideId}_text"),
        ];

        abort_unless(collect($slideData)->every(fn ($value) => filled($value)), 404, 'Carousel slide not found.');

        $activeSlideCount = SiteSetting::query()
            ->where('key', 'like', 'homepage_slide_%_image')
            ->count();

        if ($activeSlideCount <= 1) {
            return response()->json(['message' => 'Keep at least one homepage carousel slide.'], 422);
        }

        DB::transaction(function () use ($slideId, $slideData): void {
            HomepageSlideArchive::create([
                'original_slide_id' => $slideId,
                ...$slideData,
                'archived_by' => Auth::id(),
                'archived_at' => now(),
            ]);

            SiteSetting::query()
                ->whereIn('key', [
                    "homepage_slide_{$slideId}_image",
                    "homepage_slide_{$slideId}_title",
                    "homepage_slide_{$slideId}_text",
                ])
                ->delete();
        });

        return response()->json(['message' => 'Carousel slide archived successfully.']);
    }
}
