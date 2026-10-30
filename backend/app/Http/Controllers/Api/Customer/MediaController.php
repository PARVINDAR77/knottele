<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Category;
use App\Models\Testimonial;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MediaController extends Controller
{
    /**
     * Get structured website media for customer storefront.
     * Cached for 30 minutes for instantaneous sub-10ms delivery.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $data = Cache::remember('knotelle_customer_homepage_media_v2', 1800, function () {
            // 0. Live Store Settings
            $rawSettings = Setting::all();
            $storeSettings = [];
            foreach ($rawSettings as $s) {
                $val = $s->value;
                if ($s->type === 'checkbox' || $s->type === 'boolean') {
                    $val = filter_var($val, FILTER_VALIDATE_BOOLEAN);
                } elseif (in_array($s->type, ['number', 'integer'])) {
                    $val = is_numeric($val) ? (int)$val : 0;
                } elseif (in_array($s->type, ['decimal', 'float'])) {
                    $val = is_numeric($val) ? (float)$val : 0.0;
                }
                $storeSettings[$s->key] = $val;
            }

            // 1. Hero Slides
            $heroMedia = Media::where('page', 'homepage')
            ->where('section', 'hero')
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get();

        $heroSlides = [];
        $heroDesktop = null;
        $heroMobile = null;

        if ($heroMedia->isNotEmpty()) {
            foreach ($heroMedia as $idx => $m) {
                $desktopUrl = $m->desktop_image_url ?: $m->url;
                $mobileUrl = $m->mobile_image_url ?: $desktopUrl;
                $tagline = $m->tag_text ?: ($m->subtitle ?: 'Good Things Are Handmade');
                $primaryBtnText = $m->cta_text ?: 'Shop Now';
                $primaryBtnLink = $m->cta_link ?: '/shop';
                $secondaryBtnText = $m->secondary_cta_text ?: 'Explore Collections';
                $secondaryBtnLink = $m->secondary_cta_link ?: '/shop';

                $slideData = [
                    'id' => $m->id,
                    'title' => $m->title ?: 'Hero Slide ' . ($idx + 1),
                    'tagline' => $tagline,
                    'subtitle' => $m->subtitle ?: ($m->description ?: ''),
                    'description' => $m->description ?: ($m->subtitle ?: ''),
                    'primary_button_text' => $primaryBtnText,
                    'primary_button_link' => $primaryBtnLink,
                    'secondary_button_text' => $secondaryBtnText,
                    'secondary_button_link' => $secondaryBtnLink,
                    'cta_text' => $primaryBtnText,
                    'cta_link' => $primaryBtnLink,
                    'secondary_cta_text' => $secondaryBtnText,
                    'secondary_cta_link' => $secondaryBtnLink,
                    'tag_text' => $tagline,
                    'alt' => $m->alt_text ?: ($m->title ?: 'Knotelle Handmade Boutique'),
                    'desktop' => $desktopUrl,
                    'desktop_image' => $desktopUrl,
                    'mobile' => $mobileUrl,
                    'mobile_image' => $mobileUrl,
                    'status' => (bool)$m->is_active,
                    'is_active' => (bool)$m->is_active,
                    'sort_order' => (int)$m->sort_order,
                ];
                $heroSlides[] = $slideData;

                if (!$heroDesktop) {
                    $heroDesktop = $desktopUrl;
                }
                if (!$heroMobile) {
                    $heroMobile = $mobileUrl;
                }
            }
        }

        // Fallbacks for Hero if empty
        if (empty($heroSlides)) {
            $fallbackSlides = [
                ['id' => 1, 'desktop' => asset('images/hero/hero-enhanced.jpg'), 'desktop_image' => asset('images/hero/hero-enhanced.jpg'), 'mobile' => asset('images/hero/hero-enhanced.jpg'), 'mobile_image' => asset('images/hero/hero-enhanced.jpg'), 'title' => 'Little Stitches Big Happiness', 'tagline' => 'Good Things Are Handmade', 'subtitle' => 'Handmade crochet products that add warmth to your everyday life.', 'description' => 'Handmade crochet products that add warmth to your everyday life.', 'primary_button_text' => 'Shop All Collections', 'primary_button_link' => '/shop', 'secondary_button_text' => 'Explore Collections', 'secondary_button_link' => '/shop', 'cta_text' => 'Shop All Collections', 'cta_link' => '/shop', 'tag_text' => 'Good Things Are Handmade', 'alt' => 'Little Stitches Big Happiness', 'status' => true, 'is_active' => true, 'sort_order' => 1],
                ['id' => 2, 'desktop' => asset('images/hero/2image.png'), 'desktop_image' => asset('images/hero/2image.png'), 'mobile' => asset('images/hero/2image.png'), 'mobile_image' => asset('images/hero/2image.png'), 'title' => 'Everyday Elegance Handcrafted Bags', 'tagline' => 'Every Stitch Has A Story', 'subtitle' => 'Artisanal granny square bags & wearable creations woven with love.', 'description' => 'Artisanal granny square bags & wearable creations woven with love.', 'primary_button_text' => 'Explore Bags', 'primary_button_link' => '/category/bags', 'secondary_button_text' => 'Shop Collections', 'secondary_button_link' => '/shop', 'cta_text' => 'Explore Bags', 'cta_link' => '/category/bags', 'tag_text' => 'Every Stitch Has A Story', 'alt' => 'Everyday Elegance Handcrafted Bags', 'status' => true, 'is_active' => true, 'sort_order' => 2],
                ['id' => 3, 'desktop' => asset('images/hero/3image.png'), 'desktop_image' => asset('images/hero/3image.png'), 'mobile' => asset('images/hero/3image.png'), 'mobile_image' => asset('images/hero/3image.png'), 'title' => 'Everlasting Blooms Made With Love', 'tagline' => 'Crafted Just For You', 'subtitle' => 'Bespoke everlasting flower bouquets crafted to brighten every moment.', 'description' => 'Bespoke everlasting flower bouquets crafted to brighten every moment.', 'primary_button_text' => 'Shop Flowers', 'primary_button_link' => '/category/flower', 'secondary_button_text' => 'Explore Collections', 'secondary_button_link' => '/shop', 'cta_text' => 'Shop Flowers', 'cta_link' => '/category/flower', 'tag_text' => 'Crafted Just For You', 'alt' => 'Everlasting Blooms Made With Love', 'status' => true, 'is_active' => true, 'sort_order' => 3],
                ['id' => 4, 'desktop' => asset('images/hero/4image.png'), 'desktop_image' => asset('images/hero/4image.png'), 'mobile' => asset('images/hero/4image.png'), 'mobile_image' => asset('images/hero/4image.png'), 'title' => 'Bespoke Creations Custom Crafted', 'tagline' => 'Pure Artisan Warmth', 'subtitle' => 'Personalized crochet treasures tailored specially for your memorable moments.', 'description' => 'Personalized crochet treasures tailored specially for your memorable moments.', 'primary_button_text' => 'Request Custom Order', 'primary_button_link' => '/custom-order', 'secondary_button_text' => 'Shop Now', 'secondary_button_link' => '/shop', 'cta_text' => 'Request Custom Order', 'cta_link' => '/custom-order', 'tag_text' => 'Pure Artisan Warmth', 'alt' => 'Bespoke Creations Custom Crafted', 'status' => true, 'is_active' => true, 'sort_order' => 4],
            ];
            $heroSlides = $fallbackSlides;
            $heroDesktop = $fallbackSlides[0]['desktop'];
            $heroMobile = $fallbackSlides[0]['mobile'];
        }

        if (!$heroMobile) {
            $heroMobile = $heroDesktop;
        }

        // 2. Custom Crochet Banner
        $customCrochetMedia = Media::where('page', 'homepage')
            ->where('section', 'custom_crochet')
            ->where('is_active', true)
            ->orderBy('updated_at', 'desc')
            ->first();

        if (!$customCrochetMedia) {
            $customCrochetMedia = Media::where('page', 'homepage')
                ->where('section', 'custom_crochet')
                ->orderBy('updated_at', 'desc')
                ->first();
        }

        $ccMeta = $customCrochetMedia && $customCrochetMedia->metadata ? $customCrochetMedia->metadata : [];

        $customCrochet = [
            'desktop' => $customCrochetMedia ? ($customCrochetMedia->desktop_image_url ?: $customCrochetMedia->url) : asset('images/homepage/middleimg.png'),
            'mobile' => $customCrochetMedia ? ($customCrochetMedia->mobile_image_url ?: ($customCrochetMedia->desktop_image_url ?: $customCrochetMedia->url)) : asset('images/homepage/middleimg.png'),
            'title' => $customCrochetMedia && $customCrochetMedia->title ? $customCrochetMedia->title : 'Custom Crochet',
            'subtitle' => $customCrochetMedia && $customCrochetMedia->subtitle ? $customCrochetMedia->subtitle : 'Just for You',
            'description' => $customCrochetMedia && $customCrochetMedia->description ? $customCrochetMedia->description : "Your imagination, our yarn. Let's create something special together.",
            'cta_text' => $customCrochetMedia && $customCrochetMedia->cta_text ? $customCrochetMedia->cta_text : 'Request Your Custom Order',
            'cta_link' => $customCrochetMedia && $customCrochetMedia->cta_link ? $customCrochetMedia->cta_link : '/custom-order',
            'tag_text' => $customCrochetMedia && $customCrochetMedia->tag_text ? $customCrochetMedia->tag_text : 'Turn Your Ideas Into Handmade Reality',
            'tag_active' => isset($ccMeta['tag_active']) ? (bool)$ccMeta['tag_active'] : true,
            'alt_text' => $customCrochetMedia && $customCrochetMedia->alt_text ? $customCrochetMedia->alt_text : 'Custom Crochet Banner',
            'is_active' => $customCrochetMedia ? (bool)$customCrochetMedia->is_active : true,
        ];

        // 3. Brand Story
        $brandStoryMedia = Media::where('page', 'homepage')
            ->where('section', 'brand_story')
            ->where('is_active', true)
            ->first();

        $bsMeta = $brandStoryMedia && $brandStoryMedia->metadata ? $brandStoryMedia->metadata : [];
        $defaultBsFeatures = [
            ['title' => 'Handmade with Love', 'icon' => 'heart'],
            ['title' => 'Premium Yarn Quality', 'icon' => 'sparkles'],
            ['title' => '100% Pure Natural Cotton', 'icon' => 'leaf'],
            ['title' => 'Happiness Guaranteed', 'icon' => 'smile'],
        ];

        $bsFallback = asset('images/homepage/middleimg.png');
        $bsImg = ($brandStoryMedia && $brandStoryMedia->file_path && file_exists(public_path($brandStoryMedia->file_path)))
            ? $brandStoryMedia->url
            : ($brandStoryMedia ? $brandStoryMedia->url : $bsFallback);

        $brandStory = [
            'desktop' => $bsImg ?: $bsFallback,
            'mobile' => $bsImg ?: $bsFallback,
            'badge' => $brandStoryMedia && $brandStoryMedia->tag_text ? $brandStoryMedia->tag_text : 'KNOTELLE Artisanal Crochet Craftsmanship',
            'title' => $brandStoryMedia && $brandStoryMedia->title && $brandStoryMedia->title !== 'Brand Story Desktop Background' ? $brandStoryMedia->title : 'Every Stitch',
            'subtitle' => $brandStoryMedia && $brandStoryMedia->subtitle ? $brandStoryMedia->subtitle : 'Has a Story',
            'description' => $brandStoryMedia && $brandStoryMedia->description ? $brandStoryMedia->description : 'More than just crochet, we create memories, happiness and a little bit of magic.',
            'cta_text' => $brandStoryMedia && $brandStoryMedia->cta_text ? $brandStoryMedia->cta_text : 'Read Our Story',
            'cta_link' => $brandStoryMedia && $brandStoryMedia->cta_link ? $brandStoryMedia->cta_link : '/about',
            'features' => !empty($bsMeta['features']) ? $bsMeta['features'] : $defaultBsFeatures,
            'is_active' => $brandStoryMedia ? (bool)$brandStoryMedia->is_active : true,
        ];

        // 4. Custom Order CTA
        $customOrderMedia = Media::where('page', 'homepage')
            ->where('section', 'custom_order')
            ->where('is_active', true)
            ->first();

        $rawCtaLink = $customOrderMedia && $customOrderMedia->cta_link ? trim($customOrderMedia->cta_link) : '/custom-order';
        // Sanitize accidental typos or suffixes like /custom-orderrbtydsg -> /custom-order
        if (str_starts_with($rawCtaLink, '/custom-order') && $rawCtaLink !== '/custom-order' && !str_starts_with($rawCtaLink, '/custom-orders')) {
            $rawCtaLink = '/custom-order';
        }

        $customDesc = $customOrderMedia && $customOrderMedia->description ? $customOrderMedia->description : "From custom color palettes and personalized initials to unique floral bouquets and character plushies — let's create something made especially for you.";
        if (str_contains($customDesc, 'gdsgwegbcxvsawr32b4gvqrefd')) {
            $customDesc = "From custom color palettes and personalized initials to unique floral bouquets and character plushies — let's create something made especially for you.";
        }

        $customOrder = [
            'desktop' => $customOrderMedia ? $customOrderMedia->url : null,
            'mobile' => $customOrderMedia ? $customOrderMedia->url : null,
            'badge' => $customOrderMedia && $customOrderMedia->tag_text ? $customOrderMedia->tag_text : 'Bespoke Handcrafting',
            'title_line1' => $customOrderMedia && $customOrderMedia->title ? $customOrderMedia->title : 'Your Idea.',
            'title_line2' => $customOrderMedia && $customOrderMedia->subtitle ? $customOrderMedia->subtitle : 'Our Yarn.',
            'description' => $customDesc,
            'cta_text' => $customOrderMedia && $customOrderMedia->cta_text ? $customOrderMedia->cta_text : 'Start a Custom Order',
            'cta_link' => $rawCtaLink,
            'is_active' => $customOrderMedia ? (bool)$customOrderMedia->is_active : true,
        ];

        $customOrderItems = Media::where('page', 'custom_order')
            ->where('section', 'custom_order_items')
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->title,
                    'title' => $item->title,
                    'subtitle' => $item->subtitle ?: 'Custom pattern',
                    'icon' => $item->tag_text ?: 'Flower2',
                    'sort_order' => (int)$item->sort_order,
                    'is_active' => (bool)$item->is_active,
                ];
            });

        $customOrder['items'] = $customOrderItems;

        // 5. Newsletter
        $newsletterMedia = Media::where('page', 'homepage')
            ->where('section', 'newsletter')
            ->where('is_active', true)
            ->first();

        $newsletter = [
            'bg' => $newsletterMedia ? $newsletterMedia->url : null,
            'left' => null,
            'right' => null,
            'title' => $newsletterMedia && $newsletterMedia->title ? $newsletterMedia->title : 'Join Our Creative Journey',
            'subtitle' => $newsletterMedia && $newsletterMedia->subtitle ? $newsletterMedia->subtitle : 'Get updates on new products, offers and handmade stories.',
            'description' => $newsletterMedia && $newsletterMedia->description ? $newsletterMedia->description : ($newsletterMedia && $newsletterMedia->subtitle ? $newsletterMedia->subtitle : 'Get updates on new products, offers and handmade stories.'),
            'tag_text' => $newsletterMedia && $newsletterMedia->tag_text ? $newsletterMedia->tag_text : 'KNOTELLE10',
            'cta_text' => $newsletterMedia && $newsletterMedia->cta_text ? $newsletterMedia->cta_text : 'Subscribe',
            'is_active' => $newsletterMedia ? (bool)$newsletterMedia->is_active : true,
        ];

        // 6. Footer Dynamic Configuration & Artwork
        $footerMedia = Media::where('section', 'footer')
            ->where('slot', 'section_settings')
            ->first();
        if (!$footerMedia) {
            $footerMedia = Media::where('section', 'footer')->first();
        }

        $footerMeta = $footerMedia && $footerMedia->metadata ? $footerMedia->metadata : [];
        
        // Find designated footer background slot (must be an actual image, not settings json)
        $footerBgMedia = Media::where('section', 'footer')
            ->where('slot', 'footer_bg')
            ->where('is_active', true)
            ->where('file_path', 'not like', '%.json%')
            ->orderByDesc('updated_at')
            ->first();

        // Fallback: any active image in footer section
        if (!$footerBgMedia) {
            $footerBgMedia = Media::where('section', 'footer')
                ->where('slot', '!=', 'section_settings')
                ->where('is_active', true)
                ->where('file_path', 'not like', '%.json%')
                ->orderByDesc('updated_at')
                ->first();
        }

        $footerBg = null;
        if ($footerBgMedia) {
            $candidate = $footerBgMedia->desktop_image_url ?: $footerBgMedia->url;
            if ($candidate && !str_ends_with(strtolower(parse_url($candidate, PHP_URL_PATH) ?? ''), '.json')) {
                $footerBg = $candidate;
            }
        }

        if (!$footerBg && !empty($footerMeta['bg_image']) && !str_ends_with(strtolower(parse_url($footerMeta['bg_image'], PHP_URL_PATH) ?? ''), '.json')) {
            $footerBg = $footerMeta['bg_image'];
        }
        if (!$footerBg && !empty($footerMeta['bg_image_url']) && !str_ends_with(strtolower(parse_url($footerMeta['bg_image_url'], PHP_URL_PATH) ?? ''), '.json')) {
            $footerBg = $footerMeta['bg_image_url'];
        }

        if (!$footerBg) {
            $footerBg = asset('images/categories/footer.png');
        }

        $footer = [
            'bg' => $footerBg,
            'image' => $footerBg,
            'title' => $footerMedia && $footerMedia->title ? $footerMedia->title : 'KNOTELLE',
            'subtitle' => $footerMedia && $footerMedia->subtitle ? $footerMedia->subtitle : 'Made with ♡ for a kinder, cozier world.',
            'tagline' => $footerMedia && $footerMedia->subtitle ? $footerMedia->subtitle : 'Made with ♡ for a kinder, cozier world.',
            'copyright_text' => $footerMeta['copyright_text'] ?? '© ' . date('Y') . ' Knotelle. All rights reserved.',
            'heart_tagline' => $footerMeta['heart_tagline'] ?? 'Made with ♡ for a kinder, cozier world.',
            'social' => [
                'instagram' => [
                    'url' => !empty($storeSettings['social_instagram']) ? $storeSettings['social_instagram'] : ($footerMeta['instagram_url'] ?? 'https://instagram.com/knotelleindia'),
                    'is_active' => isset($footerMeta['instagram_active']) ? (bool)$footerMeta['instagram_active'] : true,
                ],
                'facebook' => [
                    'url' => !empty($storeSettings['social_facebook']) ? $storeSettings['social_facebook'] : ($footerMeta['facebook_url'] ?? 'https://facebook.com/knotelleindia'),
                    'is_active' => isset($footerMeta['facebook_active']) ? (bool)$footerMeta['facebook_active'] : true,
                ],
                'pinterest' => [
                    'url' => $footerMeta['pinterest_url'] ?? 'https://pinterest.com/knotelleindia',
                    'is_active' => isset($footerMeta['pinterest_active']) ? (bool)$footerMeta['pinterest_active'] : true,
                ],
                'youtube' => [
                    'url' => $footerMeta['youtube_url'] ?? 'https://youtube.com/@knotelleindia',
                    'is_active' => isset($footerMeta['youtube_active']) ? (bool)$footerMeta['youtube_active'] : true,
                ],
                'twitter' => [
                    'url' => !empty($storeSettings['social_twitter']) ? $storeSettings['social_twitter'] : ($footerMeta['twitter_url'] ?? ''),
                    'is_active' => !empty($storeSettings['social_twitter']) || !empty($footerMeta['twitter_url']),
                ],
                'linkedin' => [
                    'url' => !empty($storeSettings['social_linkedin']) ? $storeSettings['social_linkedin'] : ($footerMeta['linkedin_url'] ?? ''),
                    'is_active' => !empty($storeSettings['social_linkedin']) || !empty($footerMeta['linkedin_url']),
                ],
            ],
            'column_1' => [
                'title' => $footerMeta['col1_title'] ?? 'Quick Links',
                'links' => $footerMeta['col1_links'] ?? [
                    ['label' => 'Home', 'url' => '/', 'is_active' => true],
                    ['label' => 'Shop', 'url' => '/shop', 'is_active' => true],
                    ['label' => 'Custom Order', 'url' => '/custom-order', 'is_active' => true],
                    ['label' => 'About', 'url' => '/about', 'is_active' => true],
                    ['label' => 'Contact', 'url' => '/contact', 'is_active' => true],
                ],
            ],
            'column_2' => [
                'title' => $footerMeta['col2_title'] ?? 'Customer Care',
                'links' => $footerMeta['col2_links'] ?? [
                    ['label' => 'Shipping Policy', 'url' => '/contact', 'is_active' => true],
                    ['label' => 'Return & Refund', 'url' => '/contact/return-refund', 'is_active' => true],
                    ['label' => 'FAQ', 'url' => '/contact/faq', 'is_active' => true],
                    ['label' => 'Track Order', 'url' => '/account/orders', 'is_active' => true],
                ],
            ],
            'column_3' => [
                'title' => $footerMeta['col3_title'] ?? 'Contact',
                'phone' => !empty($storeSettings['store_phone']) ? $storeSettings['store_phone'] : ($footerMeta['contact_phone'] ?? '+91 97730 39243'),
                'phone_link' => 'tel:' . preg_replace('/\s+/', '', (!empty($storeSettings['store_phone']) ? $storeSettings['store_phone'] : ($footerMeta['contact_phone'] ?? '+919773039243'))),
                'email' => !empty($storeSettings['store_email']) ? $storeSettings['store_email'] : ($footerMeta['contact_email'] ?? 'support@knotelle.in'),
                'email_link' => 'mailto:' . (!empty($storeSettings['store_email']) ? $storeSettings['store_email'] : ($footerMeta['contact_email'] ?? 'support@knotelle.in')),
                'address' => !empty($storeSettings['store_address']) ? $storeSettings['store_address'] : ($footerMeta['contact_address'] ?? 'India'),
                'address_link' => $footerMeta['contact_address_link'] ?? '',
            ],
            'is_active' => $footerMedia ? (bool)$footerMedia->is_active : true,
        ];

        // 7. Global Site Assets & Navbar
        $logoMedia = Media::where('page', 'global')
            ->where('section', 'global_assets')
            ->where('slot', 'main_logo')
            ->where('is_active', true)
            ->first();

        $mainLogo = !empty($storeSettings['logo_url']) ? $storeSettings['logo_url'] : ($logoMedia ? $logoMedia->url : asset('images/logo/Logo_1.png'));

        $navbarMedia = Media::where('page', 'global')
            ->where('section', 'navbar')
            ->where('slot', 'navbar_settings')
            ->first();

        $navbarMeta = $navbarMedia && $navbarMedia->metadata ? $navbarMedia->metadata : [];

        $freeMinThreshold = !empty($storeSettings['free_shipping_min']) ? (float)$storeSettings['free_shipping_min'] : 999;
        $defaultAnnouncement = '✨ Free Pan-India Delivery on all Orders above ₹' . number_format($freeMinThreshold);

        $navbar = [
            'logo' => $mainLogo,
            'announcement' => [
                'text' => $navbarMeta['announcement_text'] ?? $defaultAnnouncement,
                'link' => $navbarMeta['announcement_link'] ?? '/shop',
                'is_active' => isset($navbarMeta['announcement_active']) ? (bool)$navbarMeta['announcement_active'] : false,
            ],
            'nav_links' => $navbarMeta['nav_links'] ?? [
                ['name' => 'Home', 'href' => '/', 'is_highlighted' => false, 'is_active' => true],
                ['name' => 'Shop', 'href' => '/shop', 'is_highlighted' => false, 'is_active' => true],
                ['name' => 'Custom Order', 'href' => '/custom-order', 'is_highlighted' => true, 'is_active' => true],
                ['name' => 'About', 'href' => '/about', 'is_highlighted' => false, 'is_active' => true],
                ['name' => 'Contact', 'href' => '/contact', 'is_highlighted' => false, 'is_active' => true],
            ],
            'actions' => [
                'show_search' => isset($navbarMeta['show_search']) ? (bool)$navbarMeta['show_search'] : true,
                'show_wishlist' => isset($navbarMeta['show_wishlist']) ? (bool)$navbarMeta['show_wishlist'] : true,
                'show_account' => isset($navbarMeta['show_account']) ? (bool)$navbarMeta['show_account'] : true,
                'show_cart' => isset($navbarMeta['show_cart']) ? (bool)$navbarMeta['show_cart'] : true,
            ],
            'is_active' => $navbarMedia ? (bool)$navbarMedia->is_active : true,
        ];

        // 7. Categories for Homepage Grid & Section Metadata
        $categorySectionMedia = Media::where('page', 'homepage')
            ->where('section', 'categories')
            ->first();

        $categorySection = [
            'tag_text' => $categorySectionMedia && $categorySectionMedia->tag_text ? $categorySectionMedia->tag_text : '🌸',
            'title' => $categorySectionMedia && $categorySectionMedia->title ? $categorySectionMedia->title : 'Shop by Category',
            'subtitle' => $categorySectionMedia && $categorySectionMedia->subtitle ? $categorySectionMedia->subtitle : 'Explore our wide range of handmade crochet products.',
            'description' => $categorySectionMedia && $categorySectionMedia->description ? $categorySectionMedia->description : 'Explore our wide range of handmade crochet products.',
            'is_active' => $categorySectionMedia ? (bool)$categorySectionMedia->is_active : true,
        ];

        $categories = Category::where('status', 1)
            ->whereNull('parent_id')
            ->orderBy('sort_order', 'asc')
            ->with(['image'])
            ->withCount(['products' => function ($q) {
                $q->where('status', 'active');
            }])
            ->get()
            ->map(function ($cat) {
                $imageUrl = null;
                if ($cat->image && $cat->image->file_path) {
                    $fp = $cat->image->file_path;
                    if (str_starts_with($fp, 'http://') || str_starts_with($fp, 'https://')) {
                        $imageUrl = $fp;
                    } elseif (str_starts_with($fp, 'images/')) {
                        $imageUrl = asset($fp);
                    } else {
                        $imageUrl = asset('storage/' . ltrim($fp, '/'));
                    }
                } else {
                    $imageUrl = asset('images/categories/' . $cat->slug . '.jpg');
                }

                return [
                    'id' => 'cat-' . $cat->id,
                    'db_id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'image' => $imageUrl,
                    'itemCount' => (int)($cat->products_count ?? 0),
                    'sort_order' => $cat->sort_order,
                ];
            });

        // 8. Shop Page Media
        $shopBannerDesktop = Media::where('page', 'shop')->where('section', 'shop_banner')->where('slot', 'shop_banner_desktop')->where('is_active', true)->first();
        $shopBannerMobile = Media::where('page', 'shop')->where('section', 'shop_banner')->where('slot', 'shop_banner_mobile')->where('is_active', true)->first();
        $shopPromo = Media::where('page', 'shop')->where('section', 'shop_promo')->where('is_active', true)->first();

        $shop = [
            'banner' => [
                'desktop' => $shopBannerDesktop ? $shopBannerDesktop->url : null,
                'mobile' => $shopBannerMobile ? $shopBannerMobile->url : ($shopBannerDesktop ? $shopBannerDesktop->url : null),
                'title' => $shopBannerDesktop && $shopBannerDesktop->title ? $shopBannerDesktop->title : 'Artisanal Handcrafted Creations',
                'subtitle' => $shopBannerDesktop && $shopBannerDesktop->subtitle ? $shopBannerDesktop->subtitle : 'Discover unique crochet treasures woven with love, patience, and 100% natural cotton fibers.',
                'tag_text' => $shopBannerDesktop && $shopBannerDesktop->tag_text ? $shopBannerDesktop->tag_text : 'Handmade with Love',
                'cta_text' => $shopBannerDesktop ? $shopBannerDesktop->cta_text : null,
                'cta_link' => $shopBannerDesktop ? $shopBannerDesktop->cta_link : null,
                'is_active' => $shopBannerDesktop ? (bool)$shopBannerDesktop->is_active : true,
            ],
            'promo' => [
                'banner' => $shopPromo ? $shopPromo->url : null,
                'title' => $shopPromo && $shopPromo->title ? $shopPromo->title : 'Bespoke Custom Creations',
                'subtitle' => $shopPromo && $shopPromo->subtitle ? $shopPromo->subtitle : 'Can’t find your dream bouquet or plushie? We craft made-to-order treasures tailored especially for you.',
                'cta_text' => $shopPromo && $shopPromo->cta_text ? $shopPromo->cta_text : 'Request Custom Order',
                'cta_link' => $shopPromo && $shopPromo->cta_link ? $shopPromo->cta_link : '/custom-order',
                'is_active' => $shopPromo ? (bool)$shopPromo->is_active : true,
            ]
        ];

        // 9. About Page Dynamic Media & Content
        $aboutStoryMedia = Media::where('page', 'about')
            ->where('section', 'about_story')
            ->where('is_active', true)
            ->first();

        $storyMeta = $aboutStoryMedia && $aboutStoryMedia->metadata ? $aboutStoryMedia->metadata : [];

        $aboutStory = [
            'id' => $aboutStoryMedia ? $aboutStoryMedia->id : null,
            'badge' => $aboutStoryMedia && $aboutStoryMedia->tag_text ? $aboutStoryMedia->tag_text : ($aboutStoryMedia && $aboutStoryMedia->subtitle ? $aboutStoryMedia->subtitle : 'The KNOTELLE Story'),
            'title' => $aboutStoryMedia && $aboutStoryMedia->title ? $aboutStoryMedia->title : 'Every Loop Tells a Story',
            'tagline' => $aboutStoryMedia && $aboutStoryMedia->subtitle ? $aboutStoryMedia->subtitle : '',
            'description' => $aboutStoryMedia && $aboutStoryMedia->description ? $aboutStoryMedia->description : 'In a world flooded with disposable factory goods, KNOTELLE was born from a yearning for slow-made warmth. What began as a tiny home workshop in Bengaluru has grown into a vibrant collective of women artisans who share a deep love for yarn, color harmony, and delicate floral silhouettes.',
            'secondary_description' => $storyMeta['paragraph_2'] ?? 'When you order a bouquet of crochet roses, a customized bunny keychain, or a granny square tote, you are not simply purchasing an object. You are welcoming hours of human patience, intention, and joy into your home.',
            'paragraph_1' => $aboutStoryMedia && $aboutStoryMedia->description ? $aboutStoryMedia->description : 'In a world flooded with disposable factory goods, KNOTELLE was born from a yearning for slow-made warmth. What began as a tiny home workshop in Bengaluru has grown into a vibrant collective of women artisans who share a deep love for yarn, color harmony, and delicate floral silhouettes.',
            'paragraph_2' => $storyMeta['paragraph_2'] ?? 'When you order a bouquet of crochet roses, a customized bunny keychain, or a granny square tote, you are not simply purchasing an object. You are welcoming hours of human patience, intention, and joy into your home.',
            'desktop_image' => $aboutStoryMedia ? ($aboutStoryMedia->desktop_image_url ?: $aboutStoryMedia->url) : 'https://images.unsplash.com/photo-1544816155-12df9643f363?q=80&w=1000&auto=format&fit=crop',
            'image' => $aboutStoryMedia ? ($aboutStoryMedia->desktop_image_url ?: $aboutStoryMedia->url) : 'https://images.unsplash.com/photo-1544816155-12df9643f363?q=80&w=1000&auto=format&fit=crop',
            'mobile_image' => $aboutStoryMedia ? ($aboutStoryMedia->mobile_image_url ?: $aboutStoryMedia->url) : null,
            'alt_text' => $aboutStoryMedia && $aboutStoryMedia->alt_text ? $aboutStoryMedia->alt_text : 'Artisan stitching crochet with wooden hook',
            'floating_badge' => [
                'title' => $storyMeta['floating_badge_title'] ?? '100% Handcrafted',
                'subtitle' => $storyMeta['floating_badge_subtitle'] ?? 'Never mass machine produced',
                'icon' => $storyMeta['floating_badge_icon'] ?? 'Heart',
                'is_active' => isset($storyMeta['floating_badge_active']) ? (bool)$storyMeta['floating_badge_active'] : true,
            ],
            'cta' => [
                'text' => $aboutStoryMedia && $aboutStoryMedia->cta_text ? $aboutStoryMedia->cta_text : 'Request a Custom Creation',
                'url' => $aboutStoryMedia && $aboutStoryMedia->cta_link ? $aboutStoryMedia->cta_link : '/custom-order',
                'link' => $aboutStoryMedia && $aboutStoryMedia->cta_link ? $aboutStoryMedia->cta_link : '/custom-order',
                'is_active' => isset($storyMeta['cta_visible']) ? (bool)$storyMeta['cta_visible'] : true,
            ],
            'cta_text' => $aboutStoryMedia && $aboutStoryMedia->cta_text ? $aboutStoryMedia->cta_text : 'Request a Custom Creation',
            'cta_link' => $aboutStoryMedia && $aboutStoryMedia->cta_link ? $aboutStoryMedia->cta_link : '/custom-order',
            'is_active' => $aboutStoryMedia ? (bool)$aboutStoryMedia->is_active : true,
        ];

        // Craft Pillars
        $pillarsSettings = Media::where('page', 'about')
            ->where('section', 'craft_pillars')
            ->where('slot', 'section_settings')
            ->first();

        $pillarsItems = Media::where('page', 'about')
            ->where('section', 'craft_pillars')
            ->where('slot', '!=', 'section_settings')
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(function ($item) {
                $meta = $item->metadata ?: [];
                $iconName = $item->tag_text ?: ($meta['icon_name'] ?? 'Leaf');
                $isSvgOrImg = $item->file_type === 'image' || str_ends_with($item->file_path, '.svg');
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'description' => $item->description,
                    'icon' => $iconName,
                    'icon_name' => $iconName,
                    'icon_type' => $meta['icon_type'] ?? ($isSvgOrImg ? 'svg' : 'preset'),
                    'icon_url' => $item->file_path && !str_starts_with($item->file_path, 'icon_') ? $item->url : null,
                    'sort_order' => (int)$item->sort_order,
                    'is_active' => (bool)$item->is_active,
                ];
            });

        // Fallback default pillars if none seeded
        if ($pillarsItems->isEmpty()) {
            $pillarsItems = collect([
                [
                    'id' => 1,
                    'title' => 'Natural Materials',
                    'description' => 'We use 100% pure milk cotton and mercerized organic fibers that are gentle on skin and kind to mother nature.',
                    'icon' => 'Leaf',
                    'icon_name' => 'Leaf',
                    'icon_type' => 'preset',
                    'icon_url' => null,
                    'sort_order' => 1,
                    'is_active' => true,
                ],
                [
                    'id' => 2,
                    'title' => 'Bespoke Atelier',
                    'description' => 'No two requests are alike. We collaborate with you on colors, sizing, and details to make every piece unique.',
                    'icon' => 'Sparkles',
                    'icon_name' => 'Sparkles',
                    'icon_type' => 'preset',
                    'icon_url' => null,
                    'sort_order' => 2,
                    'is_active' => true,
                ],
                [
                    'id' => 3,
                    'title' => 'Empowering Artisans',
                    'description' => 'We provide fair wages, flexible hours, and creative dignity to our women-led artisan crafting community in India.',
                    'icon' => 'Heart',
                    'icon_name' => 'Heart',
                    'icon_type' => 'preset',
                    'icon_url' => null,
                    'sort_order' => 3,
                    'is_active' => true,
                ],
            ]);
        }

        $craftPillars = [
            'title' => $pillarsSettings && $pillarsSettings->title ? $pillarsSettings->title : 'Our Craft Pillars',
            'subtitle' => $pillarsSettings && $pillarsSettings->subtitle ? $pillarsSettings->subtitle : ($pillarsSettings && $pillarsSettings->description ? $pillarsSettings->description : 'Guiding principles behind every stitch we make.'),
            'badge' => $pillarsSettings && $pillarsSettings->tag_text ? $pillarsSettings->tag_text : 'Artisan Standards',
            'is_active' => $pillarsSettings ? (bool)$pillarsSettings->is_active : true,
            'items' => $pillarsItems,
        ];

        $about = [
            'story' => $aboutStory,
            'hero' => $aboutStory,
            'craftPillars' => $craftPillars,
            'pillars' => $craftPillars,
        ];

        // 10. Contact Page Media & Dynamic Content
        $contact = $this->formatContactData();

        // 11. Blog & Reels / Behind The Stitches
        $blogReelsSettings = Media::where('page', 'homepage')
            ->where('section', 'blog_reels')
            ->where('slot', 'section_settings')
            ->first();

        $blogReelsItems = Media::where('page', 'homepage')
            ->where('section', 'blog_reels')
            ->where('slot', '!=', 'section_settings')
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => (string)$item->id,
                    'db_id' => $item->id,
                    'title' => $item->title,
                    'subtitle' => $item->subtitle ?: ($item->tag_text ?: ''),
                    'description' => $item->description ?: '',
                    'caption' => $item->description ?: ($item->subtitle ?: $item->title),
                    'content_type' => $item->content_type ?: 'reel',
                    'category' => $item->category_name ?: 'Studio ASMR',
                    'thumbnail' => $item->desktop_image_url ?: $item->url,
                    'mobile_thumbnail' => $item->mobile_image_url ?: ($item->desktop_image_url ?: $item->url),
                    'video_url' => $item->video_stream_url ?: $item->video_url,
                    'videoUrl' => $item->video_stream_url ?: $item->video_url,
                    'duration' => $item->duration ?: '00:48',
                    'audio_name' => $item->audio_name ?: 'Original Sound',
                    'audio_track' => $item->audio_name ?: 'Original Sound',
                    'audioTrack' => $item->audio_name ?: 'Original Sound',
                    'likes' => $item->formatted_likes,
                    'likes_count' => (int)$item->likes_count,
                    'comments' => (string)($item->comments_count ?: 0),
                    'comments_count' => (int)$item->comments_count,
                    'views' => $item->formatted_views,
                    'views_count' => (int)$item->views_count,
                    'is_featured' => (bool)$item->is_featured,
                    'sort_order' => (int)$item->sort_order,
                    'author' => [
                        'name' => 'Krina Jani',
                        'role' => 'Lead Artisan',
                        'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=200&auto=format&fit=crop',
                    ],
                    'tags' => ['#' . str_replace(' ', '', $item->category_name ?: 'Crochet'), '#Knotelle', '#Handmade'],
                ];
            });

        $blogReels = [
            'title' => $blogReelsSettings && $blogReelsSettings->title ? $blogReelsSettings->title : 'Behind the Stitches',
            'subtitle' => $blogReelsSettings && $blogReelsSettings->subtitle ? $blogReelsSettings->subtitle : 'Watch our artisans hand-craft each creation, styling guides, and cozy studio ASMR unboxings.',
            'tag_text' => $blogReelsSettings && $blogReelsSettings->tag_text ? $blogReelsSettings->tag_text : 'Studio Journal & Video Reels',
            'cta_text' => $blogReelsSettings && $blogReelsSettings->cta_text ? $blogReelsSettings->cta_text : 'Follow @knotelleindia',
            'cta_link' => $blogReelsSettings && $blogReelsSettings->cta_link ? $blogReelsSettings->cta_link : 'https://instagram.com/knotelleindia',
            'is_active' => $blogReelsSettings ? (bool)$blogReelsSettings->is_active : true,
            'items' => $blogReelsItems,
        ];

        return [
            'hero' => [
                'desktop' => $heroDesktop,
                'mobile' => $heroMobile,
                'slides' => $heroSlides,
            ],
            'categories' => $categories,
            'categorySection' => $categorySection,
            'customCrochet' => $customCrochet,
            'brandStory' => $brandStory,
            'customOrder' => $customOrder,
            'customOrderItems' => $customOrderItems,
            'newsletter' => $newsletter,
            'footer' => $footer,
            'navbar' => $navbar,
            'shop' => $shop,
            'about' => $about,
            'contact' => $contact,
            'blogReels' => $blogReels,
            'testimonials' => Testimonial::where('is_active', true)->orderBy('id', 'desc')->get(),
            'global' => [
                'logo' => !empty($storeSettings['logo_url']) ? $storeSettings['logo_url'] : $mainLogo,
                'mobileLogo' => !empty($storeSettings['logo_url']) ? $storeSettings['logo_url'] : $mainLogo,
                'favicon' => !empty($storeSettings['favicon_url']) ? $storeSettings['favicon_url'] : asset('favicon.ico'),
                'defaultProduct' => asset('images/logo/Logo_1.png'),
                'defaultCategory' => asset('images/logo/Logo_1.png'),
                'store_name' => $storeSettings['store_name'] ?? 'KNOTELLE',
                'store_email' => $storeSettings['store_email'] ?? 'hello@knotelle.com',
                'store_phone' => $storeSettings['store_phone'] ?? '+91 966048417',
                'store_phone_alt' => $storeSettings['store_phone_alt'] ?? '',
                'store_address' => $storeSettings['store_address'] ?? 'India',
                'currency' => $storeSettings['currency'] ?? 'INR',
                'theme_color' => $storeSettings['theme_color'] ?? '#913638',
                'default_shipping_rate' => (float)($storeSettings['default_shipping_rate'] ?? 99),
                'free_shipping_min' => (float)($storeSettings['free_shipping_min'] ?? 999),
                'tax_rate' => (float)($storeSettings['tax_rate'] ?? 0),
                'cod_enabled' => filter_var($storeSettings['cod_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'razorpay_enabled' => filter_var($storeSettings['razorpay_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'razorpay_key_id' => $storeSettings['razorpay_key_id'] ?? '',
            ],
            'settings' => $storeSettings,
        ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
        } catch (\Throwable $e) {
            \Log::error('Customer MediaController index error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving storefront media: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Increment Video / Reel View Count on Playback
     */
    public function incrementView($id): JsonResponse
    {
        $media = Media::where('page', 'homepage')
            ->where('section', 'blog_reels')
            ->where('id', $id)
            ->first();

        if ($media) {
            $media->increment('views_count');
            return response()->json([
                'success' => true,
                'views' => $media->views_count,
                'formatted_views' => $media->formatted_views,
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Video not found.'], 404);
    }

    /**
     * Get Dedicated About Page Data
     */
    public function getAboutPage(Request $request): JsonResponse
    {
        $data = Cache::remember('knotelle_customer_about_data', 1800, function () {
            $aboutStoryMedia = Media::where('page', 'about')
                ->where('section', 'about_story')
                ->where('is_active', true)
                ->first();

            $storyMeta = $aboutStoryMedia && $aboutStoryMedia->metadata ? $aboutStoryMedia->metadata : [];

        $aboutStory = [
            'id' => $aboutStoryMedia ? $aboutStoryMedia->id : null,
            'badge' => $aboutStoryMedia && $aboutStoryMedia->tag_text ? $aboutStoryMedia->tag_text : ($aboutStoryMedia && $aboutStoryMedia->subtitle ? $aboutStoryMedia->subtitle : 'The KNOTELLE Story'),
            'title' => $aboutStoryMedia && $aboutStoryMedia->title ? $aboutStoryMedia->title : 'Every Loop Tells a Story',
            'tagline' => $aboutStoryMedia && $aboutStoryMedia->subtitle ? $aboutStoryMedia->subtitle : '',
            'description' => $aboutStoryMedia && $aboutStoryMedia->description ? $aboutStoryMedia->description : 'In a world flooded with disposable factory goods, KNOTELLE was born from a yearning for slow-made warmth. What began as a tiny home workshop in Bengaluru has grown into a vibrant collective of women artisans who share a deep love for yarn, color harmony, and delicate floral silhouettes.',
            'secondary_description' => $storyMeta['paragraph_2'] ?? 'When you order a bouquet of crochet roses, a customized bunny keychain, or a granny square tote, you are not simply purchasing an object. You are welcoming hours of human patience, intention, and joy into your home.',
            'paragraph_1' => $aboutStoryMedia && $aboutStoryMedia->description ? $aboutStoryMedia->description : 'In a world flooded with disposable factory goods, KNOTELLE was born from a yearning for slow-made warmth. What began as a tiny home workshop in Bengaluru has grown into a vibrant collective of women artisans who share a deep love for yarn, color harmony, and delicate floral silhouettes.',
            'paragraph_2' => $storyMeta['paragraph_2'] ?? 'When you order a bouquet of crochet roses, a customized bunny keychain, or a granny square tote, you are not simply purchasing an object. You are welcoming hours of human patience, intention, and joy into your home.',
            'desktop_image' => $aboutStoryMedia ? ($aboutStoryMedia->desktop_image_url ?: $aboutStoryMedia->url) : 'https://images.unsplash.com/photo-1544816155-12df9643f363?q=80&w=1000&auto=format&fit=crop',
            'image' => $aboutStoryMedia ? ($aboutStoryMedia->desktop_image_url ?: $aboutStoryMedia->url) : 'https://images.unsplash.com/photo-1544816155-12df9643f363?q=80&w=1000&auto=format&fit=crop',
            'mobile_image' => $aboutStoryMedia ? ($aboutStoryMedia->mobile_image_url ?: $aboutStoryMedia->url) : null,
            'alt_text' => $aboutStoryMedia && $aboutStoryMedia->alt_text ? $aboutStoryMedia->alt_text : 'Artisan stitching crochet with wooden hook',
            'floating_badge' => [
                'title' => $storyMeta['floating_badge_title'] ?? '100% Handcrafted',
                'subtitle' => $storyMeta['floating_badge_subtitle'] ?? 'Never mass machine produced',
                'icon' => $storyMeta['floating_badge_icon'] ?? 'Heart',
                'is_active' => isset($storyMeta['floating_badge_active']) ? (bool)$storyMeta['floating_badge_active'] : true,
            ],
            'cta' => [
                'text' => $aboutStoryMedia && $aboutStoryMedia->cta_text ? $aboutStoryMedia->cta_text : 'Request a Custom Creation',
                'url' => $aboutStoryMedia && $aboutStoryMedia->cta_link ? $aboutStoryMedia->cta_link : '/custom-order',
                'link' => $aboutStoryMedia && $aboutStoryMedia->cta_link ? $aboutStoryMedia->cta_link : '/custom-order',
                'is_active' => isset($storyMeta['cta_visible']) ? (bool)$storyMeta['cta_visible'] : true,
            ],
            'cta_text' => $aboutStoryMedia && $aboutStoryMedia->cta_text ? $aboutStoryMedia->cta_text : 'Request a Custom Creation',
            'cta_link' => $aboutStoryMedia && $aboutStoryMedia->cta_link ? $aboutStoryMedia->cta_link : '/custom-order',
            'is_active' => $aboutStoryMedia ? (bool)$aboutStoryMedia->is_active : true,
        ];

        // Craft Pillars
        $pillarsSettings = Media::where('page', 'about')
            ->where('section', 'craft_pillars')
            ->where('slot', 'section_settings')
            ->first();

        $pillarsItems = Media::where('page', 'about')
            ->where('section', 'craft_pillars')
            ->where('slot', '!=', 'section_settings')
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(function ($item) {
                $meta = $item->metadata ?: [];
                $iconName = $item->tag_text ?: ($meta['icon_name'] ?? 'Leaf');
                $isSvgOrImg = $item->file_type === 'image' || str_ends_with($item->file_path, '.svg');
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'description' => $item->description,
                    'icon' => $iconName,
                    'icon_name' => $iconName,
                    'icon_type' => $meta['icon_type'] ?? ($isSvgOrImg ? 'svg' : 'preset'),
                    'icon_url' => $item->file_path && !str_starts_with($item->file_path, 'icon_') ? $item->url : null,
                    'sort_order' => (int)$item->sort_order,
                    'is_active' => (bool)$item->is_active,
                ];
            });

        // Fallback default pillars if none seeded
        if ($pillarsItems->isEmpty()) {
            $pillarsItems = collect([
                [
                    'id' => 1,
                    'title' => 'Natural Materials',
                    'description' => 'We use 100% pure milk cotton and mercerized organic fibers that are gentle on skin and kind to mother nature.',
                    'icon' => 'Leaf',
                    'icon_name' => 'Leaf',
                    'icon_type' => 'preset',
                    'icon_url' => null,
                    'sort_order' => 1,
                    'is_active' => true,
                ],
                [
                    'id' => 2,
                    'title' => 'Bespoke Atelier',
                    'description' => 'No two requests are alike. We collaborate with you on colors, sizing, and details to make every piece unique.',
                    'icon' => 'Sparkles',
                    'icon_name' => 'Sparkles',
                    'icon_type' => 'preset',
                    'icon_url' => null,
                    'sort_order' => 2,
                    'is_active' => true,
                ],
                [
                    'id' => 3,
                    'title' => 'Empowering Artisans',
                    'description' => 'We provide fair wages, flexible hours, and creative dignity to our women-led artisan crafting community in India.',
                    'icon' => 'Heart',
                    'icon_name' => 'Heart',
                    'icon_type' => 'preset',
                    'icon_url' => null,
                    'sort_order' => 3,
                    'is_active' => true,
                ],
            ]);
        }

        $craftPillars = [
            'title' => $pillarsSettings && $pillarsSettings->title ? $pillarsSettings->title : 'Our Craft Pillars',
            'subtitle' => $pillarsSettings && $pillarsSettings->subtitle ? $pillarsSettings->subtitle : ($pillarsSettings && $pillarsSettings->description ? $pillarsSettings->description : 'Guiding principles behind every stitch we make.'),
            'badge' => $pillarsSettings && $pillarsSettings->tag_text ? $pillarsSettings->tag_text : 'Artisan Standards',
            'is_active' => $pillarsSettings ? (bool)$pillarsSettings->is_active : true,
            'items' => $pillarsItems,
        ];

            return [
                'story' => $aboutStory,
                'hero' => $aboutStory,
                'craftPillars' => $craftPillars,
                'pillars' => $craftPillars,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get Dedicated Contact Page Data
     */
    public function getContactPage(Request $request): JsonResponse
    {
        $contact = Cache::remember('knotelle_customer_contact_data', 1800, function () {
            return $this->formatContactData();
        });

        return response()->json([
            'success' => true,
            'data' => $contact
        ]);
    }

    /**
     * Build unified structured Contact page data payload.
     */
    protected function formatContactData(): array
    {
        // 1. Intro
        $introMedia = Media::where('page', 'contact')
            ->where('section', 'contact_intro')
            ->where('slot', 'intro_banner')
            ->first();

        $intro = [
            'id' => $introMedia ? $introMedia->id : null,
            'badge' => $introMedia && $introMedia->tag_text ? $introMedia->tag_text : "Let's Connect",
            'title' => $introMedia && $introMedia->title ? $introMedia->title : "Let's Connect",
            'tagline' => $introMedia && $introMedia->subtitle ? $introMedia->subtitle : '',
            'subtitle' => $introMedia && $introMedia->subtitle ? $introMedia->subtitle : "Have a question about a product, custom order, or collaboration? We'd love to hear from you.",
            'description' => $introMedia && $introMedia->description ? $introMedia->description : "We're here to help bring your handcrafted crochet dreams to life. Reach out directly or fill out our message form below.",
            'image' => $introMedia ? ($introMedia->desktop_image_url ?: $introMedia->url) : 'https://images.unsplash.com/photo-1544816155-12df9643f363?q=80&w=1200&auto=format&fit=crop',
            'desktop_image' => $introMedia ? ($introMedia->desktop_image_url ?: $introMedia->url) : 'https://images.unsplash.com/photo-1544816155-12df9643f363?q=80&w=1200&auto=format&fit=crop',
            'alt_text' => $introMedia && $introMedia->alt_text ? $introMedia->alt_text : 'KNOTELLE Artisan Studio Contact',
            'cta_text' => $introMedia && $introMedia->cta_text ? $introMedia->cta_text : 'Send Us a Message',
            'cta_link' => $introMedia && $introMedia->cta_link ? $introMedia->cta_link : '#contact-form',
            'is_active' => $introMedia ? (bool)$introMedia->is_active : true,
        ];

        // 2. Contact Info Header & Items
        $infoSettings = Media::where('page', 'contact')
            ->where('section', 'contact_info')
            ->where('slot', 'section_settings')
            ->first();

        $infoMeta = $infoSettings && $infoSettings->metadata ? $infoSettings->metadata : [];

        $infoItems = Media::where('page', 'contact')
            ->where('section', 'contact_info')
            ->where('slot', '!=', 'section_settings')
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(function ($item) {
                $meta = $item->metadata ?: [];
                $iconName = $item->tag_text ?: ($meta['icon_name'] ?? 'MapPin');
                return [
                    'id' => $item->id,
                    'icon' => $iconName,
                    'icon_name' => $iconName,
                    'title' => $item->title,
                    'value' => $item->description,
                    'description' => $item->description,
                    'address_line_2' => $meta['address_line_2'] ?? '',
                    'link' => $item->cta_link ?: '',
                    'cta_link' => $item->cta_link ?: '',
                    'sort_order' => (int)$item->sort_order,
                    'is_active' => (bool)$item->is_active,
                ];
            })
            ->values()
            ->all();

        // Fallback default info items if none found
        if (empty($infoItems)) {
            $infoItems = [
                [
                    'id' => 1,
                    'icon' => 'MapPin',
                    'icon_name' => 'MapPin',
                    'title' => 'Visit Our Studio',
                    'value' => 'KNOTELLE Studio, 12th Main Road, Indiranagar',
                    'description' => 'KNOTELLE Studio, 12th Main Road, Indiranagar',
                    'address_line_2' => 'Bengaluru, Karnataka 560038, India',
                    'link' => 'https://maps.google.com/?q=Indiranagar+Bengaluru',
                    'cta_link' => 'https://maps.google.com/?q=Indiranagar+Bengaluru',
                    'sort_order' => 1,
                    'is_active' => true,
                ],
                [
                    'id' => 2,
                    'icon' => 'Phone',
                    'icon_name' => 'Phone',
                    'title' => 'Call / WhatsApp Us',
                    'value' => '+91 98765 43210',
                    'description' => '+91 98765 43210',
                    'address_line_2' => 'Mon – Sat, 10:00 AM – 7:00 PM IST',
                    'link' => 'tel:+919876543210',
                    'cta_link' => 'tel:+919876543210',
                    'sort_order' => 2,
                    'is_active' => true,
                ],
                [
                    'id' => 3,
                    'icon' => 'Mail',
                    'icon_name' => 'Mail',
                    'title' => 'Write to Us',
                    'value' => 'hello@knotelle.com',
                    'description' => 'hello@knotelle.com',
                    'address_line_2' => 'We usually reply within 24 hours',
                    'link' => 'mailto:hello@knotelle.com',
                    'cta_link' => 'mailto:hello@knotelle.com',
                    'sort_order' => 3,
                    'is_active' => true,
                ],
                [
                    'id' => 4,
                    'icon' => 'Clock',
                    'icon_name' => 'Clock',
                    'title' => 'Studio Hours',
                    'value' => 'Monday – Saturday: 10:00 AM – 7:00 PM',
                    'description' => 'Monday – Saturday: 10:00 AM – 7:00 PM',
                    'address_line_2' => 'Sunday: Closed (Crafting in silence)',
                    'link' => '',
                    'cta_link' => '',
                    'sort_order' => 4,
                    'is_active' => true,
                ],
            ];
        }

        // Live sync store settings into contact info items
        $contactSettings = Setting::all()->pluck('value', 'key')->toArray();
        foreach ($infoItems as &$item) {
            $t = strtolower(($item['title'] ?? '') . ' ' . ($item['icon_name'] ?? ''));
            if (str_contains($t, 'phone') || str_contains($t, 'call') || str_contains($t, 'whatsapp')) {
                if (!empty($contactSettings['store_phone'])) {
                    $item['value'] = $contactSettings['store_phone'];
                    $item['description'] = $contactSettings['store_phone'];
                    $item['link'] = 'tel:' . preg_replace('/\s+/', '', $contactSettings['store_phone']);
                    $item['cta_link'] = $item['link'];
                    if (!empty($contactSettings['store_phone_alt'])) {
                        $item['address_line_2'] = 'Alt: ' . $contactSettings['store_phone_alt'] . ' • Mon – Sat, 10:00 AM – 7:00 PM IST';
                    }
                }
            } elseif (str_contains($t, 'mail') || str_contains($t, 'email')) {
                if (!empty($contactSettings['store_email'])) {
                    $item['value'] = $contactSettings['store_email'];
                    $item['description'] = $contactSettings['store_email'];
                    $item['link'] = 'mailto:' . $contactSettings['store_email'];
                    $item['cta_link'] = $item['link'];
                }
            } elseif (str_contains($t, 'studio') || str_contains($t, 'visit') || str_contains($t, 'address') || str_contains($t, 'mappin')) {
                if (!empty($contactSettings['store_address'])) {
                    $item['value'] = $contactSettings['store_address'];
                    $item['description'] = $contactSettings['store_address'];
                    $item['link'] = 'https://maps.google.com/?q=' . urlencode($contactSettings['store_address']);
                    $item['cta_link'] = $item['link'];
                }
            }
        }
        unset($item);

        $contactDetails = [
            'badge' => $infoSettings && $infoSettings->tag_text ? $infoSettings->tag_text : 'Atelier Studio',
            'title' => $infoSettings && $infoSettings->title ? $infoSettings->title : (($contactSettings['store_name'] ?? 'KNOTELLE') . ' Studio'),
            'subtitle' => $infoSettings && $infoSettings->subtitle ? $infoSettings->subtitle : 'Handmade with love in India',
            'is_active' => $infoSettings ? (bool)$infoSettings->is_active : true,
            'items' => $infoItems,
            'custom_order_box' => [
                'title' => $infoMeta['custom_order_box_title'] ?? 'Looking for Custom Orders?',
                'text' => $infoMeta['custom_order_box_text'] ?? 'Have a specific design, color palette, or bouquet arrangement in mind? Request a bespoke piece directly.',
                'link' => $infoMeta['custom_order_box_link'] ?? '/custom-order',
                'is_active' => isset($infoMeta['custom_order_box_active']) ? (bool)$infoMeta['custom_order_box_active'] : true,
            ],
        ];

        // 3. Form Settings
        $formSettings = Media::where('page', 'contact')
            ->where('section', 'contact_form')
            ->where('slot', 'form_settings')
            ->first();

        $formMeta = $formSettings && $formSettings->metadata ? $formSettings->metadata : [];
        $defaultFields = [
            ['key' => 'name', 'label' => 'Your Name', 'placeholder' => 'Enter your full name', 'required' => true, 'is_active' => true],
            ['key' => 'email', 'label' => 'Email Address', 'placeholder' => 'Enter your email address', 'required' => true, 'is_active' => true],
            ['key' => 'phone', 'label' => 'Phone Number', 'placeholder' => 'Enter your 10-digit phone number (optional)', 'required' => false, 'is_active' => true],
            ['key' => 'subject', 'label' => 'Subject', 'placeholder' => 'What is this regarding?', 'required' => false, 'is_active' => true],
            ['key' => 'message', 'label' => 'Your Message', 'placeholder' => 'Tell us how we can help you...', 'required' => true, 'is_active' => true],
        ];

        $form = [
            'badge' => $formSettings && $formSettings->tag_text ? $formSettings->tag_text : 'Get In Touch',
            'title' => $formSettings && $formSettings->title ? $formSettings->title : 'Send Us a Message',
            'subtitle' => $formSettings && $formSettings->subtitle ? $formSettings->subtitle : 'Fill in your details and our team will get back to you promptly.',
            'submit_btn_text' => $formSettings && $formSettings->cta_text ? $formSettings->cta_text : 'Send Message',
            'cta_text' => $formSettings && $formSettings->cta_text ? $formSettings->cta_text : 'Send Message',
            'success_title' => $formMeta['success_title'] ?? 'Message Sent!',
            'success_message' => $formMeta['success_message'] ?? 'Thank you! Your message has been sent successfully. We will get back to you shortly.',
            'error_message' => $formMeta['error_message'] ?? 'Something went wrong while sending your message. Please check the form and try again.',
            'is_active' => $formSettings ? (bool)$formSettings->is_active : true,
            'fields' => !empty($formMeta['fields']) ? $formMeta['fields'] : $defaultFields,
        ];

        // 4. FAQs Header & Items
        $faqsSettings = Media::where('page', 'contact')
            ->where('section', 'contact_faqs')
            ->where('slot', 'section_settings')
            ->first();

        $faqItems = Media::where('page', 'contact')
            ->where('section', 'contact_faqs')
            ->where('slot', '!=', 'section_settings')
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'question' => $item->title,
                    'title' => $item->title,
                    'answer' => $item->description,
                    'description' => $item->description,
                    'sort_order' => (int)$item->sort_order,
                    'is_active' => (bool)$item->is_active,
                ];
            })
            ->values()
            ->all();

        // Fallback default FAQs if none seeded
        if (empty($faqItems)) {
            $faqItems = [
                [
                    'id' => 1,
                    'question' => 'How long does a custom order take?',
                    'title' => 'How long does a custom order take?',
                    'answer' => 'Custom orders usually take 7–14 working days depending on complexity and yarn availability. Once dispatched, courier delivery takes 3–5 business days.',
                    'description' => 'Custom orders usually take 7–14 working days depending on complexity and yarn availability. Once dispatched, courier delivery takes 3–5 business days.',
                    'sort_order' => 1,
                    'is_active' => true,
                ],
                [
                    'id' => 2,
                    'question' => 'Do you ship across India?',
                    'title' => 'Do you ship across India?',
                    'answer' => 'Yes! We offer Pan-India shipping with trusted courier partners. Express delivery options and tracked shipping are provided for all orders.',
                    'description' => 'Yes! We offer Pan-India shipping with trusted courier partners. Express delivery options and tracked shipping are provided for all orders.',
                    'sort_order' => 2,
                    'is_active' => true,
                ],
                [
                    'id' => 3,
                    'question' => 'How do I care for my crochet items?',
                    'title' => 'How do I care for my crochet items?',
                    'answer' => 'Hand-wash gently in cold water using mild liquid detergent. Lay flat on a clean dry towel in shade to dry. Do not machine wash, bleach, or wring.',
                    'description' => 'Hand-wash gently in cold water using mild liquid detergent. Lay flat on a clean dry towel in shade to dry. Do not machine wash, bleach, or wring.',
                    'sort_order' => 3,
                    'is_active' => true,
                ],
                [
                    'id' => 4,
                    'question' => 'Can I customize the colors of a flower bouquet?',
                    'title' => 'Can I customize the colors of a flower bouquet?',
                    'answer' => 'Absolutely! Every everlasting bouquet can be customized with your choice of flower types, stem count, color shades, and personalized gift wrapping notes.',
                    'description' => 'Absolutely! Every everlasting bouquet can be customized with your choice of flower types, stem count, color shades, and personalized gift wrapping notes.',
                    'sort_order' => 4,
                    'is_active' => true,
                ],
            ];
        }

        $faqs = [
            'badge' => $faqsSettings && $faqsSettings->tag_text ? $faqsSettings->tag_text : 'Help & Support',
            'title' => $faqsSettings && $faqsSettings->title ? $faqsSettings->title : 'Frequently Asked Questions',
            'subtitle' => $faqsSettings && $faqsSettings->subtitle ? $faqsSettings->subtitle : 'Quick answers about our handmade creations, custom orders, and delivery.',
            'is_active' => $faqsSettings ? (bool)$faqsSettings->is_active : true,
            'items' => $faqItems,
        ];

        $firstItem = !empty($infoItems[0]) ? $infoItems[0] : [];
        $phoneItem = collect($infoItems)->firstWhere('icon', 'Phone') ?? ($infoItems[1] ?? []);
        $emailItem = collect($infoItems)->firstWhere('icon', 'Mail') ?? ($infoItems[2] ?? []);
        $hoursItem = collect($infoItems)->firstWhere('icon', 'Clock') ?? ($infoItems[3] ?? []);

        return [
            'intro' => $intro,
            'info' => $contactDetails,
            'details' => $contactDetails,
            'contactDetails' => $contactDetails,
            'contactInfo' => $contactDetails,
            'form' => $form,
            'formSettings' => $form,
            'faqs' => $faqs,
            'settings' => [
                'studio_name' => $contactDetails['title'],
                'studio_badge' => $contactDetails['badge'],
                'address_line_1' => $firstItem['value'] ?? '',
                'address_line_2' => $firstItem['address_line_2'] ?? '',
                'phone' => $phoneItem['value'] ?? '',
                'email' => $emailItem['value'] ?? '',
                'hours' => $hoursItem['value'] ?? '',
            ]
        ];
    }
}
