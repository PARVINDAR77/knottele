<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Media;
use App\Services\Admin\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class SettingController extends Controller
{
    private SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Render the admin settings blade view
     */
    public function index(): View
    {
        return view('admin.settings.index');
    }

    /**
     * Get all settings grouped by category for the settings manager form
     */
    public function getGroups(): JsonResponse
    {
        try {
            $settings = $this->settingService->getSettings();

            return response()->json([
                'success' => true,
                'data' => $settings,
                'message' => 'Settings retrieved successfully'
            ]);
        } catch (\Throwable $e) {
            Log::error('Admin SettingController getGroups error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update settings from admin form and synchronize to storefront
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'settings' => 'required|array',
                'settings.*.key' => 'required|string',
                'settings.*.value' => 'nullable'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                    'message' => 'Validation failed'
                ], 422);
            }

            $updated = $this->settingService->bulkUpdate($request->settings);

            // Extract map of updated settings for cross-module synchronization
            $settingsMap = [];
            foreach ($request->settings as $item) {
                if (!empty($item['key'])) {
                    $settingsMap[$item['key']] = $item['value'] ?? '';
                }
            }

            // Sync to Media Footer section if contact or social settings changed
            try {
                $footerMedia = Media::where('section', 'footer')
                    ->where('slot', 'section_settings')
                    ->first();

                if ($footerMedia) {
                    $meta = $footerMedia->metadata ?: [];

                    if (isset($settingsMap['store_phone'])) {
                        $meta['contact_phone'] = $settingsMap['store_phone'];
                        $meta['contact_phone_link'] = 'tel:' . preg_replace('/\s+/', '', $settingsMap['store_phone']);
                    }
                    if (isset($settingsMap['store_email'])) {
                        $meta['contact_email'] = $settingsMap['store_email'];
                        $meta['contact_email_link'] = 'mailto:' . $settingsMap['store_email'];
                    }
                    if (isset($settingsMap['store_address'])) {
                        $meta['contact_address'] = $settingsMap['store_address'];
                    }
                    if (isset($settingsMap['social_facebook'])) {
                        $meta['facebook_url'] = $settingsMap['social_facebook'];
                    }
                    if (isset($settingsMap['social_instagram'])) {
                        $meta['instagram_url'] = $settingsMap['social_instagram'];
                    }
                    if (isset($settingsMap['social_twitter'])) {
                        $meta['twitter_url'] = $settingsMap['social_twitter'];
                    }
                    if (isset($settingsMap['social_linkedin'])) {
                        $meta['linkedin_url'] = $settingsMap['social_linkedin'];
                    }

                    $footerMedia->metadata = $meta;
                    $footerMedia->save();
                }

                // Sync Announcement bar threshold if free_shipping_min changed
                if (isset($settingsMap['free_shipping_min'])) {
                    $navbarMedia = Media::where('page', 'global')
                        ->where('section', 'navbar')
                        ->where('slot', 'navbar_settings')
                        ->first();

                    if ($navbarMedia) {
                        $navMeta = $navbarMedia->metadata ?: [];
                        $minVal = (float)$settingsMap['free_shipping_min'];
                        $navMeta['announcement_text'] = "✨ Free Pan-India Delivery on all Orders above ₹" . number_format($minVal);
                        $navbarMedia->metadata = $navMeta;
                        $navbarMedia->save();
                    }
                }
            } catch (\Throwable $syncErr) {
                Log::warning('Media auto-sync on settings update notice: ' . $syncErr->getMessage());
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'updated_count' => $updated
                ],
                'message' => 'All settings synchronized successfully!'
            ]);

        } catch (\Throwable $e) {
            Log::error('Admin SettingController bulkUpdate error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset settings to system defaults
     */
    public function resetDefaults(): JsonResponse
    {
        try {
            $this->settingService->resetToDefaults();

            return response()->json([
                'success' => true,
                'message' => 'Settings reset to defaults successfully!'
            ]);
        } catch (\Throwable $e) {
            Log::error('Admin SettingController reset error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload Logo or Favicon asset from Appearance settings tab
     */
    public function uploadAsset(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:jpeg,png,jpg,gif,svg,ico,webp|max:4096',
                'key'  => 'nullable|string'
            ]);

            $file = $request->file('file');
            $key = $request->input('key', 'logo_url');

            $folder = $key === 'favicon_url' ? 'images/logo' : 'images/logo';
            $targetDir = public_path($folder);
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $prefix = $key === 'favicon_url' ? 'favicon_' : 'store_logo_';
            $filename = $prefix . time() . '_' . \Illuminate\Support\Str::random(4) . '.' . $file->getClientOriginalExtension();
            $file->move($targetDir, $filename);

            $relativePath = $folder . '/' . $filename;
            $fullUrl = asset($relativePath);

            // Update setting in database
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $fullUrl,
                    'group' => 'appearance',
                    'type' => 'text',
                    'label' => $key === 'favicon_url' ? 'Favicon URL' : 'Logo URL',
                    'is_public' => true
                ]
            );

            return response()->json([
                'success' => true,
                'url' => $fullUrl,
                'relative_path' => $relativePath,
                'message' => 'Asset uploaded and configured successfully!'
            ]);

        } catch (\Throwable $e) {
            Log::error('Admin SettingController uploadAsset error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload asset: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update current logged in admin credentials
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $admin = Auth::guard('admin')->user();
            if (!$admin) {
                return response()->json(['success' => false, 'message' => 'Admin not authenticated.'], 401);
            }

            $request->validate([
                'name' => 'required|string|max:100',
                'email' => 'required|email|max:150|unique:admins,email,' . $admin->id,
                'password' => 'nullable|string|min:6|confirmed'
            ]);

            $admin->name = $request->name;
            $admin->email = $request->email;
            if ($request->filled('password')) {
                $admin->password = Hash::make($request->password);
                $admin->password_changed_at = now();
            }
            $admin->save();

            return response()->json([
                'success' => true,
                'message' => 'Account profile updated successfully!'
            ]);
        } catch (\Throwable $e) {
            Log::error('Admin SettingController updateProfile error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile: ' . $e->getMessage()
            ], 500);
        }
    }
}
