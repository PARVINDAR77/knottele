<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Category;
use App\Models\Product;
use App\Models\Testimonial;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    protected function getAdminId(): int
    {
        return Auth::guard('admin')->id() ?? auth('admin_api')->id() ?? 1;
    }

    public function index()
    {
        return view('admin.media.index');
    }

    /**
     * Get Structured Website Media Manager Data
     */
    public function getManagerData(Request $request): JsonResponse
    {
        if (Media::count() === 0) {
            $this->syncLocalImagesToDatabase();
        }

        // Fetch existing hero slides dynamically from DB
        $heroMediaList = Media::where('page', 'homepage')
            ->where('section', 'hero')
            ->orderBy('sort_order', 'asc')
            ->get();

        $heroSlots = [];
        if ($heroMediaList->isNotEmpty()) {
            foreach ($heroMediaList as $idx => $hm) {
                $slideNum = $hm->sort_order ?: ($idx + 1);
                $deviceLabel = $hm->device === 'all' ? 'All Devices' : ucfirst($hm->device);
                $heroSlots[] = [
                    'slot' => $hm->slot ?: 'hero_slide_' . $hm->id,
                    'title' => $hm->title ?: "Hero Slide {$slideNum} ({$deviceLabel})",
                    'description' => $hm->description ?: 'Hero carousel slide visual and banner.',
                    'recommended_dimensions' => $hm->device === 'mobile' ? '768 × 1000' : '1920 × 700',
                    'device' => $hm->device ?: 'desktop',
                    'page' => 'homepage',
                    'section' => 'hero',
                    'sort_order' => $slideNum,
                    'is_custom_slide' => true,
                    'media_id' => $hm->id,
                ];
            }
        } else {
            $heroSlots = [
                [
                    'slot' => 'hero_slide_1_desktop',
                    'title' => 'Hero Slide 1 (Desktop)',
                    'description' => 'Primary panoramic hero slide on desktop screens.',
                    'recommended_dimensions' => '1920 × 700',
                    'device' => 'desktop',
                    'page' => 'homepage',
                    'section' => 'hero',
                    'sort_order' => 1,
                ],
                [
                    'slot' => 'hero_slide_1_mobile',
                    'title' => 'Hero Slide 1 (Mobile)',
                    'description' => 'Mobile-optimized vertical hero slide (optional fallback to desktop).',
                    'recommended_dimensions' => '768 × 1000',
                    'device' => 'mobile',
                    'page' => 'homepage',
                    'section' => 'hero',
                    'sort_order' => 1,
                ],
                [
                    'slot' => 'hero_slide_2_desktop',
                    'title' => 'Hero Slide 2 (Everyday Elegance)',
                    'description' => 'Second slide featuring handcrafted bags & wearable creations.',
                    'recommended_dimensions' => '1920 × 700',
                    'device' => 'desktop',
                    'page' => 'homepage',
                    'section' => 'hero',
                    'sort_order' => 2,
                ],
                [
                    'slot' => 'hero_slide_3_desktop',
                    'title' => 'Hero Slide 3 (Everlasting Blooms)',
                    'description' => 'Third slide featuring everlasting flower bouquets.',
                    'recommended_dimensions' => '1920 × 700',
                    'device' => 'desktop',
                    'page' => 'homepage',
                    'section' => 'hero',
                    'sort_order' => 3,
                ],
                [
                    'slot' => 'hero_slide_4_desktop',
                    'title' => 'Hero Slide 4 (Bespoke Creations)',
                    'description' => 'Fourth slide featuring customized & tailored gifts.',
                    'recommended_dimensions' => '1920 × 700',
                    'device' => 'desktop',
                    'page' => 'homepage',
                    'section' => 'hero',
                    'sort_order' => 4,
                ],
            ];
        }

        // 1. Defined Slots Configuration
        $sections = [
            [
                'id' => 'hero',
                'page' => 'homepage',
                'title' => 'Hero Banner',
                'description' => 'Main panoramic homepage hero carousel slides and visual banners.',
                'badge' => 'Carousel & Banner',
                'is_hero_section' => true,
                'slots' => $heroSlots,
            ],
            [
                'id' => 'categories',
                'page' => 'homepage',
                'title' => 'Shop by Category',
                'description' => 'Circular category thumbnails displayed on the homepage and catalog.',
                'badge' => 'Category-Driven',
                'is_category_section' => true,
                'metadata' => (function() {
                    $m = Media::where('page', 'homepage')->where('section', 'categories')->first();
                    return [
                        'id' => $m ? $m->id : null,
                        'page' => 'homepage',
                        'section' => 'categories',
                        'slot' => 'section_settings',
                        'tag_text' => $m && $m->tag_text ? $m->tag_text : '🌸',
                        'badge' => $m && $m->tag_text ? $m->tag_text : '🌸',
                        'title' => $m && $m->title ? $m->title : 'Shop by Category',
                        'title_line1' => $m && $m->title ? $m->title : 'Shop by Category',
                        'subtitle' => $m && $m->subtitle ? $m->subtitle : 'Explore our wide range of handmade crochet products.',
                        'description' => $m && $m->description ? $m->description : 'Explore our wide range of handmade crochet products.',
                        'is_active' => $m ? (bool)$m->is_active : true,
                    ];
                })(),
            ],
            [
                'id' => 'custom_crochet',
                'page' => 'homepage',
                'title' => 'Custom Crochet Banner',
                'description' => 'Middle promotional banner ("Custom Crochet Just for You") with request button and hanging paper note ("Turn Your Ideas Into Handmade Reality").',
                'badge' => 'Promotional Banner',
                'is_custom_crochet_section' => true,
                'metadata' => (function() {
                    $m = Media::where('page', 'homepage')->where('section', 'custom_crochet')->orderBy('updated_at', 'desc')->first();
                    $meta = $m && $m->metadata ? $m->metadata : [];
                    return [
                        'id' => $m ? $m->id : null,
                        'page' => 'homepage',
                        'section' => 'custom_crochet',
                        'slot' => 'custom_crochet_visual',
                        'title' => $m && $m->title ? $m->title : 'Custom Crochet',
                        'subtitle' => $m && $m->subtitle ? $m->subtitle : 'Just for You',
                        'description' => $m && $m->description ? $m->description : "Your imagination, our yarn. Let's create something special together.",
                        'cta_text' => $m && $m->cta_text ? $m->cta_text : 'Request Your Custom Order',
                        'cta_link' => $m && $m->cta_link ? $m->cta_link : '/custom-order',
                        'tag_text' => $m && $m->tag_text ? $m->tag_text : 'Turn Your Ideas Into Handmade Reality',
                        'tag_active' => isset($meta['tag_active']) ? (bool)$meta['tag_active'] : true,
                        'desktop_image' => $m ? ($m->desktop_image_url ?: $m->url) : asset('images/homepage/middleimg.png'),
                        'mobile_image' => $m ? ($m->mobile_image_url ?: '') : '',
                        'alt_text' => $m && $m->alt_text ? $m->alt_text : 'Custom Crochet Banner',
                        'is_active' => $m ? (bool)$m->is_active : true,
                    ];
                })(),
            ],
            [
                'id' => 'brand_story',
                'page' => 'homepage',
                'title' => 'Brand Story ("Every Stitch Has a Story")',
                'description' => 'Artisan storytelling section with background craftsmanship visual, narrative, and 4 trust features.',
                'badge' => 'Storytelling & Trust',
                'is_brand_story_section' => true,
                'metadata' => (function() {
                    $m = Media::where('page', 'homepage')->where('section', 'brand_story')->orderBy('updated_at', 'desc')->first();
                    $meta = $m && $m->metadata ? $m->metadata : [];
                    $defaultFeatures = [
                        ['title' => 'Handmade with Love', 'icon' => 'heart'],
                        ['title' => 'Premium Yarn Quality', 'icon' => 'sparkles'],
                        ['title' => '100% Pure Natural Cotton', 'icon' => 'leaf'],
                        ['title' => 'Happiness Guaranteed', 'icon' => 'smile'],
                    ];
                    return [
                        'id' => $m ? $m->id : null,
                        'page' => 'homepage',
                        'section' => 'brand_story',
                        'slot' => 'brand_story_desktop',
                        'badge' => $m && $m->tag_text ? $m->tag_text : 'KNOTELLE Artisanal Crochet Craftsmanship',
                        'title' => $m && $m->title ? $m->title : 'Every Stitch',
                        'subtitle' => $m && $m->subtitle ? $m->subtitle : 'Has a Story',
                        'description' => $m && $m->description ? $m->description : 'More than just crochet, we create memories, happiness and a little bit of magic.',
                        'cta_text' => $m && $m->cta_text ? $m->cta_text : 'Read Our Story',
                        'cta_link' => $m && $m->cta_link ? $m->cta_link : '/about',
                        'features' => !empty($meta['features']) ? $meta['features'] : $defaultFeatures,
                        'desktop_image' => $m ? ($m->desktop_image_url ?: $m->url) : asset('images/homepage/middleimg.png'),
                        'image_url' => $m ? ($m->desktop_image_url ?: $m->url) : asset('images/homepage/middleimg.png'),
                        'is_active' => $m ? (bool)$m->is_active : true,
                    ];
                })(),
            ],
            [
                'id' => 'bestsellers',
                'page' => 'homepage',
                'title' => 'Best Sellers',
                'description' => 'Product-driven section. Images and pricing are managed via Products.',
                'badge' => 'Product-Driven',
                'is_product_section' => true,
            ],
            [
                'id' => 'trust_benefits',
                'page' => 'homepage',
                'title' => 'Trust & Benefits',
                'description' => 'Highlights (Handmade with Love, 100% Pure Cotton, Happiness Guaranteed).',
                'badge' => 'Icon & Content Driven',
                'is_content_section' => true,
            ],
            [
                'id' => 'custom_order',
                'page' => 'homepage',
                'title' => 'Custom Order ("Your Idea. Our Yarn.")',
                'description' => 'Manage headline titles, badge tagline, description, and button links for bespoke custom orders.',
                'badge' => 'Text & CTA Driven',
                'is_text_only_section' => true,
                'metadata' => (function() {
                    $m = Media::where('page', 'homepage')->where('section', 'custom_order')->first();
                    return [
                        'id' => $m ? $m->id : null,
                        'page' => 'homepage',
                        'section' => 'custom_order',
                        'slot' => 'default',
                        'badge' => $m && $m->tag_text ? $m->tag_text : 'Bespoke Handcrafting',
                        'tag_text' => $m && $m->tag_text ? $m->tag_text : 'Bespoke Handcrafting',
                        'title' => $m && $m->title ? $m->title : 'Your Idea.',
                        'title_line1' => $m && $m->title ? $m->title : 'Your Idea.',
                        'subtitle' => $m && $m->subtitle ? $m->subtitle : 'Our Yarn.',
                        'title_line2' => $m && $m->subtitle ? $m->subtitle : 'Our Yarn.',
                        'description' => $m && $m->description ? $m->description : "From custom color palettes and personalized initials to unique floral bouquets and character plushies — let's create something made especially for you.",
                        'cta_text' => $m && $m->cta_text ? $m->cta_text : 'Start a Custom Order',
                        'cta_link' => $m && $m->cta_link ? $m->cta_link : '/custom-order',
                        'is_active' => $m ? (bool)$m->is_active : true,
                    ];
                })(),
            ],
            [
                'id' => 'newsletter',
                'page' => 'homepage',
                'title' => 'Newsletter / Creative Journey',
                'description' => 'Manage newsletter heading, description, coupon code badge, and subscription settings.',
                'badge' => 'Text & Form Driven',
                'is_text_only_section' => true,
                'metadata' => (function() {
                    $m = Media::where('page', 'homepage')->where('section', 'newsletter')->first();
                    return [
                        'id' => $m ? $m->id : null,
                        'page' => 'homepage',
                        'section' => 'newsletter',
                        'slot' => 'default',
                        'title' => $m && $m->title ? $m->title : 'Join Our Creative Journey',
                        'subtitle' => $m && $m->subtitle ? $m->subtitle : 'Get updates on new products, offers and handmade stories.',
                        'description' => $m && $m->description ? $m->description : 'Get updates on new products, offers and handmade stories.',
                        'tag_text' => $m && $m->tag_text ? $m->tag_text : 'KNOTELLE10',
                        'cta_text' => $m && $m->cta_text ? $m->cta_text : 'Subscribe',
                        'cta_link' => $m && $m->cta_link ? $m->cta_link : '',
                        'is_active' => $m ? (bool)$m->is_active : true,
                    ];
                })(),
            ],
            [
                'id' => 'testimonials',
                'page' => 'homepage',
                'title' => 'Customer Reviews & Testimonials',
                'description' => 'Manage verified customer reviews, 5-star ratings, patron stories, and quotes displayed on the website carousel.',
                'badge' => 'Dynamic Reviews',
                'is_testimonial_section' => true,
                'testimonials' => Testimonial::orderBy('id', 'desc')->get(),
            ],
            [
                'id' => 'blog_reels',
                'page' => 'homepage',
                'title' => 'BLOG / VIDEOS & REELS',
                'description' => 'Manage website blogs, videos, reels and social-style content.',
                'badge' => 'Video & Reels Dynamic Stream',
                'is_blog_reels_section' => true,
                'metadata' => (function() {
                    $m = Media::where('page', 'homepage')->where('section', 'blog_reels')->where('slot', 'section_settings')->first();
                    return [
                        'id' => $m ? $m->id : null,
                        'page' => 'homepage',
                        'section' => 'blog_reels',
                        'slot' => 'section_settings',
                        'title' => $m && $m->title ? $m->title : 'Behind the Stitches',
                        'subtitle' => $m && $m->subtitle ? $m->subtitle : 'Watch our artisans hand-craft each creation, styling guides, and cozy studio ASMR unboxings.',
                        'tag_text' => $m && $m->tag_text ? $m->tag_text : 'Studio Journal & Video Reels',
                        'cta_text' => $m && $m->cta_text ? $m->cta_text : 'Follow @knotelleindia',
                        'cta_link' => $m && $m->cta_link ? $m->cta_link : 'https://instagram.com/knotelleindia',
                        'is_active' => $m ? (bool)$m->is_active : true,
                    ];
                })(),
                'section_settings' => (function() {
                    $m = Media::where('page', 'homepage')->where('section', 'blog_reels')->where('slot', 'section_settings')->first();
                    return [
                        'id' => $m ? $m->id : null,
                        'page' => 'homepage',
                        'section' => 'blog_reels',
                        'slot' => 'section_settings',
                        'title' => $m && $m->title ? $m->title : 'Behind the Stitches',
                        'subtitle' => $m && $m->subtitle ? $m->subtitle : 'Watch our artisans hand-craft each creation, styling guides, and cozy studio ASMR unboxings.',
                        'tag_text' => $m && $m->tag_text ? $m->tag_text : 'Studio Journal & Video Reels',
                        'cta_text' => $m && $m->cta_text ? $m->cta_text : 'Follow @knotelleindia',
                        'cta_link' => $m && $m->cta_link ? $m->cta_link : 'https://instagram.com/knotelleindia',
                        'is_active' => $m ? (bool)$m->is_active : true,
                    ];
                })(),
                'items' => (function() {
                    return Media::where('page', 'homepage')
                        ->where('section', 'blog_reels')
                        ->where('slot', '!=', 'section_settings')
                        ->orderBy('sort_order', 'asc')
                        ->get()
                        ->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'title' => $item->title,
                                'tagline' => $item->tag_text ?: $item->subtitle,
                                'subtitle' => $item->subtitle,
                                'description' => $item->description,
                                'content_type' => $item->content_type ?: 'reel',
                                'category' => $item->category_name ?: 'Studio ASMR',
                                'category_name' => $item->category_name ?: 'Studio ASMR',
                                'thumbnail' => $item->desktop_image_url ?: $item->url,
                                'thumbnail_url' => $item->desktop_image_url ?: $item->url,
                                'mobile_thumbnail' => $item->mobile_image_url ?: ($item->desktop_image_url ?: $item->url),
                                'video_url' => $item->video_stream_url ?: $item->video_url,
                                'video_path' => $item->video_url,
                                'duration' => $item->duration ?: '00:48',
                                'audio_name' => $item->audio_name ?: 'Original Sound',
                                'audio_track' => $item->audio_name ?: 'Original Sound',
                                'likes' => $item->likes_count ?: 0,
                                'likes_count' => $item->likes_count ?: 0,
                                'likes_formatted' => $item->formatted_likes,
                                'comments' => $item->comments_count ?: 0,
                                'comments_count' => $item->comments_count ?: 0,
                                'views' => $item->views_count ?: 0,
                                'views_count' => $item->views_count ?: 0,
                                'views_formatted' => $item->formatted_views,
                                'is_featured' => (bool)$item->is_featured,
                                'is_active' => (bool)$item->is_active,
                                'status' => (bool)$item->is_active,
                                'sort_order' => (int)$item->sort_order,
                                'file_size_formatted' => $this->formatSize($item->file_size ?: 0),
                                'updated_at_formatted' => $item->updated_at ? $item->updated_at->format('M d, Y') : '',
                            ];
                        });
                })(),
                'reels' => (function() {
                    return Media::where('page', 'homepage')
                        ->where('section', 'blog_reels')
                        ->where('slot', '!=', 'section_settings')
                        ->orderBy('sort_order', 'asc')
                        ->get()
                        ->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'title' => $item->title,
                                'tagline' => $item->tag_text ?: $item->subtitle,
                                'subtitle' => $item->subtitle,
                                'description' => $item->description,
                                'content_type' => $item->content_type ?: 'reel',
                                'category' => $item->category_name ?: 'Studio ASMR',
                                'category_name' => $item->category_name ?: 'Studio ASMR',
                                'thumbnail' => $item->desktop_image_url ?: $item->url,
                                'thumbnail_url' => $item->desktop_image_url ?: $item->url,
                                'mobile_thumbnail' => $item->mobile_image_url ?: ($item->desktop_image_url ?: $item->url),
                                'video_url' => $item->video_stream_url ?: $item->video_url,
                                'video_path' => $item->video_url,
                                'duration' => $item->duration ?: '00:48',
                                'audio_name' => $item->audio_name ?: 'Original Sound',
                                'audio_track' => $item->audio_name ?: 'Original Sound',
                                'likes' => $item->likes_count ?: 0,
                                'likes_count' => $item->likes_count ?: 0,
                                'likes_formatted' => $item->formatted_likes,
                                'comments' => $item->comments_count ?: 0,
                                'comments_count' => $item->comments_count ?: 0,
                                'views' => $item->views_count ?: 0,
                                'views_count' => $item->views_count ?: 0,
                                'views_formatted' => $item->formatted_views,
                                'is_featured' => (bool)$item->is_featured,
                                'is_active' => (bool)$item->is_active,
                                'status' => (bool)$item->is_active,
                                'sort_order' => (int)$item->sort_order,
                                'file_size_formatted' => $this->formatSize($item->file_size ?: 0),
                                'updated_at_formatted' => $item->updated_at ? $item->updated_at->format('M d, Y') : '',
                            ];
                        });
                })(),
            ],
            [
                'id' => 'footer',
                'page' => 'footer',
                'title' => 'Footer Artwork & Navigation',
                'description' => 'Full-width panoramic boutique footer artwork, 3 navigation columns, social channels, contact info, and copyright note.',
                'badge' => 'Footer & Links',
                'is_footer_section' => true,
                'metadata' => (function() {
                    $m = Media::where('section', 'footer')->where('slot', 'section_settings')->first();
                    if (!$m) {
                        $m = Media::where('section', 'footer')->first();
                    }
                    $meta = $m && $m->metadata ? $m->metadata : [];
                    $bgMedia = Media::where('section', 'footer')
                        ->where('slot', 'footer_bg')
                        ->where('is_active', true)
                        ->where('file_path', 'not like', '%.json%')
                        ->orderByDesc('updated_at')
                        ->first();
                    $bgUrl = null;
                    if ($bgMedia) {
                        $candidate = $bgMedia->desktop_image_url ?: $bgMedia->url;
                        if ($candidate && !str_ends_with(strtolower(parse_url($candidate, PHP_URL_PATH) ?? ''), '.json')) {
                            $bgUrl = $candidate;
                        }
                    }
                    if (!$bgUrl && !empty($meta['bg_image']) && !str_ends_with(strtolower(parse_url($meta['bg_image'], PHP_URL_PATH) ?? ''), '.json')) {
                        $bgUrl = $meta['bg_image'];
                    }
                    if (!$bgUrl && !empty($meta['bg_image_url']) && !str_ends_with(strtolower(parse_url($meta['bg_image_url'], PHP_URL_PATH) ?? ''), '.json')) {
                        $bgUrl = $meta['bg_image_url'];
                    }
                    if (!$bgUrl) {
                        $bgUrl = asset('images/categories/footer.png');
                    }

                    return [
                        'id' => $m ? $m->id : null,
                        'bg_image' => $bgUrl,
                        'desktop_image' => $bgUrl,
                        'title' => $m && $m->title ? $m->title : 'KNOTELLE Boutique Footer',
                        'subtitle' => $m && $m->subtitle ? $m->subtitle : 'Made with ♡ for a kinder, cozier world.',
                        'col1_title' => $meta['col1_title'] ?? 'Quick Links',
                        'col1_links' => $meta['col1_links'] ?? [
                            ['label' => 'Home', 'url' => '/', 'is_active' => true],
                            ['label' => 'Shop', 'url' => '/shop', 'is_active' => true],
                            ['label' => 'Custom Order', 'url' => '/custom-order', 'is_active' => true],
                            ['label' => 'About', 'url' => '/about', 'is_active' => true],
                            ['label' => 'Contact', 'url' => '/contact', 'is_active' => true],
                        ],
                        'col2_title' => $meta['col2_title'] ?? 'Help',
                        'col2_links' => $meta['col2_links'] ?? [
                            ['label' => 'Shipping Policy', 'url' => '/contact', 'is_active' => true],
                            ['label' => 'Return & Refund', 'url' => '/contact', 'is_active' => true],
                            ['label' => 'FAQ', 'url' => '/contact', 'is_active' => true],
                            ['label' => 'Track Order', 'url' => '/account/orders', 'is_active' => true],
                        ],
                        'col3_title' => $meta['col3_title'] ?? 'Contact',
                        'contact_phone' => $meta['contact_phone'] ?? '+91 97730 39243',
                        'contact_phone_link' => $meta['contact_phone_link'] ?? 'tel:+919773039243',
                        'contact_email' => $meta['contact_email'] ?? 'support@knotelle.in',
                        'contact_email_link' => $meta['contact_email_link'] ?? 'mailto:support@knotelle.in',
                        'contact_address' => $meta['contact_address'] ?? 'India',
                        'contact_address_link' => $meta['contact_address_link'] ?? '',
                        'instagram_url' => $meta['instagram_url'] ?? 'https://instagram.com/knotelleindia',
                        'instagram_active' => isset($meta['instagram_active']) ? (bool)$meta['instagram_active'] : true,
                        'facebook_url' => $meta['facebook_url'] ?? 'https://facebook.com/knotelleindia',
                        'facebook_active' => isset($meta['facebook_active']) ? (bool)$meta['facebook_active'] : true,
                        'pinterest_url' => $meta['pinterest_url'] ?? 'https://pinterest.com/knotelleindia',
                        'pinterest_active' => isset($meta['pinterest_active']) ? (bool)$meta['pinterest_active'] : true,
                        'youtube_url' => $meta['youtube_url'] ?? 'https://youtube.com/@knotelleindia',
                        'youtube_active' => isset($meta['youtube_active']) ? (bool)$meta['youtube_active'] : true,
                        'copyright_text' => $meta['copyright_text'] ?? '© {year} Knotelle. All rights reserved.',
                        'heart_tagline' => $meta['heart_tagline'] ?? 'Made with ♡ for a kinder, cozier world.',
                        'is_active' => $m ? (bool)$m->is_active : true,
                    ];
                })(),
                'slots' => [
                    [
                        'slot' => 'footer_bg',
                        'title' => 'Footer Panoramic Background',
                        'description' => 'Full-width panoramic background artwork image in footer across the entire store.',
                        'recommended_dimensions' => '1920 × 600',
                        'device' => 'all',
                        'page' => 'footer',
                        'section' => 'footer',
                        'sort_order' => 1,
                    ],
                ]
            ],
            [
                'id' => 'navbar_settings',
                'page' => 'global',
                'title' => 'Navbar & Header Navigation',
                'description' => 'Announcement bar banner, header links, sparkle highlight pill, and action buttons visibility.',
                'badge' => 'Navigation & Announcement',
                'is_navbar_section' => true,
                'metadata' => (function() {
                    $m = Media::where('page', 'global')->where('section', 'navbar')->where('slot', 'navbar_settings')->first();
                    $meta = $m && $m->metadata ? $m->metadata : [];
                    return [
                        'id' => $m ? $m->id : null,
                        'page' => 'global',
                        'section' => 'navbar',
                        'slot' => 'navbar_settings',
                        'title' => $m && $m->title ? $m->title : 'Navbar Navigation & Announcement',
                        'announcement_text' => $meta['announcement_text'] ?? '✨ Free Pan-India Delivery on all Orders above ₹999',
                        'announcement_link' => $meta['announcement_link'] ?? '/shop',
                        'announcement_active' => isset($meta['announcement_active']) ? (bool)$meta['announcement_active'] : false,
                        'nav_links' => $meta['nav_links'] ?? [
                            ['name' => 'Home', 'href' => '/', 'is_highlighted' => false, 'is_active' => true, 'sort_order' => 1],
                            ['name' => 'Shop', 'href' => '/shop', 'is_highlighted' => false, 'is_active' => true, 'sort_order' => 2],
                            ['name' => 'Custom Order', 'href' => '/custom-order', 'is_highlighted' => true, 'is_active' => true, 'sort_order' => 3],
                            ['name' => 'About', 'href' => '/about', 'is_highlighted' => false, 'is_active' => true, 'sort_order' => 4],
                            ['name' => 'Contact', 'href' => '/contact', 'is_highlighted' => false, 'is_active' => true, 'sort_order' => 5],
                        ],
                        'show_search' => isset($meta['show_search']) ? (bool)$meta['show_search'] : true,
                        'show_wishlist' => isset($meta['show_wishlist']) ? (bool)$meta['show_wishlist'] : true,
                        'show_account' => isset($meta['show_account']) ? (bool)$meta['show_account'] : true,
                        'show_cart' => isset($meta['show_cart']) ? (bool)$meta['show_cart'] : true,
                        'is_active' => $m ? (bool)$m->is_active : true,
                    ];
                })(),
            ],
            [
                'id' => 'global_assets',
                'page' => 'global',
                'title' => 'Global Site Assets',
                'description' => 'Brand logos, favicons, and default fallback images used across all pages.',
                'badge' => 'Site-Wide',
                'slots' => [
                    [
                        'slot' => 'main_logo',
                        'title' => 'Primary Boutique Logo (Crown)',
                        'description' => 'Main logo used in navbar, footer, and branding.',
                        'recommended_dimensions' => '300 × 120',
                        'device' => 'all',
                        'page' => 'global',
                        'section' => 'global_assets',
                        'sort_order' => 1,
                    ],
                    [
                        'slot' => 'mobile_logo',
                        'title' => 'Mobile Navbar Logo',
                        'description' => 'Compact logo for small screens (defaults to primary logo).',
                        'recommended_dimensions' => '180 × 80',
                        'device' => 'mobile',
                        'page' => 'global',
                        'section' => 'global_assets',
                        'sort_order' => 2,
                    ],
                    [
                        'slot' => 'favicon',
                        'title' => 'Browser Favicon',
                        'description' => 'Browser tab favicon icon.',
                        'recommended_dimensions' => '64 × 64',
                        'device' => 'all',
                        'page' => 'global',
                        'section' => 'global_assets',
                        'sort_order' => 3,
                    ],
                ]
            ],


            // CUSTOM ORDER PAGE SECTIONS
            [
                'id' => 'custom_order_items',
                'page' => 'custom_order',
                'title' => 'Custom Order Items / Categories',
                'description' => 'Manage handmade creation categories selectable by customers in Step 1 of the Custom Order wizard.',
                'badge' => 'Step 1 Item Types',
                'is_custom_order_items_section' => true,
                'items' => (function() {
                    $count = Media::where('page', 'custom_order')->where('section', 'custom_order_items')->count();
                    if ($count === 0) {
                        $defaults = [
                            ['title' => 'Keychain', 'subtitle' => 'Custom pattern', 'sort_order' => 1],
                            ['title' => 'Bouquet', 'subtitle' => 'Hand-tied florals', 'sort_order' => 2],
                            ['title' => 'Soft Toys', 'subtitle' => 'Amigurumi plushies', 'sort_order' => 3],
                            ['title' => 'Bags', 'subtitle' => 'Tote & clutch styles', 'sort_order' => 4],
                            ['title' => 'Coin Purse', 'subtitle' => 'Pocket essentials', 'sort_order' => 5],
                            ['title' => 'Flower pot', 'subtitle' => 'Evergreen decor', 'sort_order' => 6],
                            ['title' => 'Phone Cover', 'subtitle' => 'Sleeve & case wrap', 'sort_order' => 7],
                            ['title' => 'Cup', 'subtitle' => 'Cozy mug hugger', 'sort_order' => 8],
                            ['title' => 'Bookmark', 'subtitle' => 'Loom stitched', 'sort_order' => 9],
                            ['title' => 'Hair Accessories', 'subtitle' => 'Scrunchies & clips', 'sort_order' => 10],
                            ['title' => 'Clothing', 'subtitle' => 'Cardigans & tops', 'sort_order' => 11],
                            ['title' => 'Custom Concept', 'subtitle' => 'Brand new idea', 'sort_order' => 12],
                        ];
                        foreach ($defaults as $d) {
                            Media::create([
                                'page' => 'custom_order',
                                'section' => 'custom_order_items',
                                'slot' => 'item_' . \Illuminate\Support\Str::slug($d['title']),
                                'title' => $d['title'],
                                'subtitle' => $d['subtitle'],
                                'tag_text' => 'Flower2',
                                'sort_order' => $d['sort_order'],
                                'is_active' => true,
                                'uploaded_by' => 1,
                                'uploader_type' => 'admin',
                                'file_name' => 'custom_order.png',
                                'file_path' => 'images/categories/categories_bg.png',
                                'mime_type' => 'image/png',
                                'disk' => 'local'
                            ]);
                        }
                    }

                    return Media::where('page', 'custom_order')
                        ->where('section', 'custom_order_items')
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
                })(),
            ],

            // ABOUT PAGE SECTIONS
            [
                'id' => 'about_story',
                'page' => 'about',
                'title' => 'The KNOTELLE Story',
                'description' => 'Manage Story headings, two descriptive paragraphs, artisan crafting visual, floating 100% handcrafted badge, and custom creation button.',
                'badge' => 'Story & Atelier',
                'is_about_story_section' => true,
                'metadata' => (function() {
                    $m = Media::where('page', 'about')->where('section', 'about_story')->first();
                    $meta = $m && $m->metadata ? $m->metadata : [];
                    return [
                        'id' => $m ? $m->id : null,
                        'page' => 'about',
                        'section' => 'about_story',
                        'slot' => 'story_hero',
                        'badge' => $m && $m->tag_text ? $m->tag_text : 'The KNOTELLE Story',
                        'tag_text' => $m && $m->tag_text ? $m->tag_text : 'The KNOTELLE Story',
                        'title' => $m && $m->title ? $m->title : 'Every Loop Tells a Story',
                        'subtitle' => $m && $m->subtitle ? $m->subtitle : '',
                        'tagline' => $m && $m->subtitle ? $m->subtitle : '',
                        'description' => $m && $m->description ? $m->description : 'In a world flooded with disposable factory goods, KNOTELLE was born from a yearning for slow-made warmth. What began as a tiny home workshop in Bengaluru has grown into a vibrant collective of women artisans who share a deep love for yarn, color harmony, and delicate floral silhouettes.',
                        'paragraph_1' => $m && $m->description ? $m->description : 'In a world flooded with disposable factory goods, KNOTELLE was born from a yearning for slow-made warmth. What began as a tiny home workshop in Bengaluru has grown into a vibrant collective of women artisans who share a deep love for yarn, color harmony, and delicate floral silhouettes.',
                        'paragraph_2' => $meta['paragraph_2'] ?? 'When you order a bouquet of crochet roses, a customized bunny keychain, or a granny square tote, you are not simply purchasing an object. You are welcoming hours of human patience, intention, and joy into your home.',
                        'desktop_image' => $m ? ($m->desktop_image_url ?: $m->url) : 'https://images.unsplash.com/photo-1544816155-12df9643f363?q=80&w=1000&auto=format&fit=crop',
                        'mobile_image' => $m ? ($m->mobile_image_url ?: '') : '',
                        'alt_text' => $m && $m->alt_text ? $m->alt_text : 'Artisan stitching crochet with wooden hook',
                        'floating_badge_title' => $meta['floating_badge_title'] ?? '100% Handcrafted',
                        'floating_badge_subtitle' => $meta['floating_badge_subtitle'] ?? 'Never mass machine produced',
                        'floating_badge_icon' => $meta['floating_badge_icon'] ?? 'Heart',
                        'floating_badge_active' => isset($meta['floating_badge_active']) ? (bool)$meta['floating_badge_active'] : true,
                        'cta_text' => $m && $m->cta_text ? $m->cta_text : 'Request a Custom Creation',
                        'cta_link' => $m && $m->cta_link ? $m->cta_link : '/custom-order',
                        'cta_visible' => isset($meta['cta_visible']) ? (bool)$meta['cta_visible'] : true,
                        'is_active' => $m ? (bool)$m->is_active : true,
                    ];
                })(),
            ],
            [
                'id' => 'craft_pillars',
                'page' => 'about',
                'title' => 'Our Craft Pillars',
                'description' => 'Manage section heading, description, and dynamic list of Craft Pillars (icons, titles, descriptions, sort order, and status).',
                'badge' => 'Craft Pillars',
                'is_craft_pillars_section' => true,
                'metadata' => (function() {
                    $m = Media::where('page', 'about')->where('section', 'craft_pillars')->where('slot', 'section_settings')->first();
                    return [
                        'id' => $m ? $m->id : null,
                        'title' => $m && $m->title ? $m->title : 'Our Craft Pillars',
                        'subtitle' => $m && $m->subtitle ? $m->subtitle : ($m && $m->description ? $m->description : 'Guiding principles behind every stitch we make.'),
                        'tag_text' => $m && $m->tag_text ? $m->tag_text : 'Artisan Standards',
                        'is_active' => $m ? (bool)$m->is_active : true,
                    ];
                })(),
                'items' => Media::where('page', 'about')
                    ->where('section', 'craft_pillars')
                    ->where('slot', '!=', 'section_settings')
                    ->orderBy('sort_order', 'asc')
                    ->get()
                    ->map(function ($item) {
                        $meta = $item->metadata ?: [];
                        $iconName = $item->tag_text ?: ($meta['icon_name'] ?? 'Leaf');
                        $isCustom = $item->file_path && !str_starts_with($item->file_path, 'icon_') && ($item->file_type === 'image' || str_ends_with($item->file_path, '.svg'));
                        return [
                            'id' => $item->id,
                            'title' => $item->title,
                            'description' => $item->description,
                            'icon' => $iconName,
                            'icon_name' => $iconName,
                            'icon_type' => $meta['icon_type'] ?? ($isCustom ? 'custom' : 'preset'),
                            'icon_url' => $isCustom ? $item->url : null,
                            'sort_order' => (int)$item->sort_order,
                            'is_active' => (bool)$item->is_active,
                            'status' => (bool)$item->is_active,
                            'updated_at_formatted' => $item->updated_at ? $item->updated_at->format('M d, Y') : '',
                        ];
                    }),
            ],

            // CONTACT PAGE SECTIONS
            [
                'id' => 'contact_intro',
                'page' => 'contact',
                'title' => 'Contact Introduction / Hero',
                'description' => 'Manage main headline ("Let\'s Connect"), badge, subtitle tagline, introductory description, and banner artwork visual.',
                'badge' => 'Hero & Intro',
                'is_contact_intro_section' => true,
                'metadata' => (function() {
                    $m = Media::where('page', 'contact')->where('section', 'contact_intro')->where('slot', 'intro_banner')->first();
                    return [
                        'id' => $m ? $m->id : null,
                        'badge' => $m && $m->tag_text ? $m->tag_text : "Let's Connect",
                        'tag_text' => $m && $m->tag_text ? $m->tag_text : "Let's Connect",
                        'title' => $m && $m->title ? $m->title : "Let's Connect",
                        'subtitle' => $m && $m->subtitle ? $m->subtitle : "Have a question about a product, custom order, or collaboration? We'd love to hear from you.",
                        'tagline' => $m && $m->subtitle ? $m->subtitle : '',
                        'description' => $m && $m->description ? $m->description : "We're here to help bring your handcrafted crochet dreams to life. Reach out directly or fill out our message form below.",
                        'desktop_image' => $m ? ($m->desktop_image_url ?: $m->url) : 'https://images.unsplash.com/photo-1544816155-12df9643f363?q=80&w=1200&auto=format&fit=crop',
                        'mobile_image' => $m ? ($m->mobile_image_url ?: '') : '',
                        'alt_text' => $m && $m->alt_text ? $m->alt_text : 'KNOTELLE Artisan Studio Contact',
                        'cta_text' => $m && $m->cta_text ? $m->cta_text : 'Send Us a Message',
                        'cta_link' => $m && $m->cta_link ? $m->cta_link : '#contact-form',
                        'is_active' => $m ? (bool)$m->is_active : true,
                    ];
                })(),
            ],
            [
                'id' => 'contact_info',
                'page' => 'contact',
                'title' => 'Contact Information & Atelier Studio',
                'description' => 'Manage studio name/badge, custom order helper box, and dynamic contact items (Phone, Email, Address, Hours, Socials).',
                'badge' => 'Contact Details & Studio',
                'is_contact_info_section' => true,
                'metadata' => (function() {
                    $m = Media::where('page', 'contact')->where('section', 'contact_info')->where('slot', 'section_settings')->first();
                    $meta = $m && $m->metadata ? $m->metadata : [];
                    return [
                        'id' => $m ? $m->id : null,
                        'badge' => $m && $m->tag_text ? $m->tag_text : 'Atelier Studio',
                        'tag_text' => $m && $m->tag_text ? $m->tag_text : 'Atelier Studio',
                        'title' => $m && $m->title ? $m->title : 'KNOTELLE Studio',
                        'subtitle' => $m && $m->subtitle ? $m->subtitle : 'Handmade with love in Bengaluru, India',
                        'custom_order_box_title' => $meta['custom_order_box_title'] ?? 'Looking for Custom Orders?',
                        'custom_order_box_text' => $meta['custom_order_box_text'] ?? 'Have a specific design, color palette, or bouquet arrangement in mind? Request a bespoke piece directly.',
                        'custom_order_box_link' => $meta['custom_order_box_link'] ?? '/custom-order',
                        'custom_order_box_active' => isset($meta['custom_order_box_active']) ? (bool)$meta['custom_order_box_active'] : true,
                        'is_active' => $m ? (bool)$m->is_active : true,
                    ];
                })(),
                'items' => Media::where('page', 'contact')
                    ->where('section', 'contact_info')
                    ->where('slot', '!=', 'section_settings')
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
                            'status' => (bool)$item->is_active,
                            'updated_at_formatted' => $item->updated_at ? $item->updated_at->format('M d, Y') : '',
                        ];
                    }),
            ],
            [
                'id' => 'contact_form',
                'page' => 'contact',
                'title' => 'Send Us a Message (Contact Form)',
                'description' => 'Manage form headline, subtitle, submit button text, success message, error message, and input fields.',
                'badge' => 'Form Configuration',
                'is_contact_form_section' => true,
                'metadata' => (function() {
                    $m = Media::where('page', 'contact')->where('section', 'contact_form')->where('slot', 'form_settings')->first();
                    $meta = $m && $m->metadata ? $m->metadata : [];
                    $defaultFields = [
                        ['key' => 'name', 'label' => 'Your Name', 'placeholder' => 'Enter your full name', 'required' => true, 'is_active' => true],
                        ['key' => 'email', 'label' => 'Email Address', 'placeholder' => 'Enter your email address', 'required' => true, 'is_active' => true],
                        ['key' => 'phone', 'label' => 'Phone Number', 'placeholder' => 'Enter your 10-digit phone number (optional)', 'required' => false, 'is_active' => true],
                        ['key' => 'subject', 'label' => 'Subject', 'placeholder' => 'What is this regarding?', 'required' => false, 'is_active' => true],
                        ['key' => 'message', 'label' => 'Your Message', 'placeholder' => 'Tell us how we can help you...', 'required' => true, 'is_active' => true],
                    ];
                    return [
                        'id' => $m ? $m->id : null,
                        'badge' => $m && $m->tag_text ? $m->tag_text : 'Get In Touch',
                        'tag_text' => $m && $m->tag_text ? $m->tag_text : 'Get In Touch',
                        'title' => $m && $m->title ? $m->title : 'Send Us a Message',
                        'subtitle' => $m && $m->subtitle ? $m->subtitle : 'Fill in your details and our team will get back to you promptly.',
                        'cta_text' => $m && $m->cta_text ? $m->cta_text : 'Send Message',
                        'submit_btn_text' => $m && $m->cta_text ? $m->cta_text : 'Send Message',
                        'success_title' => $meta['success_title'] ?? 'Message Sent!',
                        'success_message' => $meta['success_message'] ?? 'Thank you! Your message has been sent successfully. We will get back to you shortly.',
                        'error_message' => $meta['error_message'] ?? 'Something went wrong while sending your message. Please check the form and try again.',
                        'fields' => !empty($meta['fields']) ? $meta['fields'] : $defaultFields,
                        'is_active' => $m ? (bool)$m->is_active : true,
                    ];
                })(),
            ],
            [
                'id' => 'contact_faqs',
                'page' => 'contact',
                'title' => 'Frequently Asked Questions (FAQs)',
                'description' => 'Manage FAQ section heading, subtitle, and dynamic list of questions and answers (Add, Edit, Delete, Toggle, Reorder).',
                'badge' => 'FAQ Accordion Stream',
                'is_contact_faqs_section' => true,
                'metadata' => (function() {
                    $m = Media::where('page', 'contact')->where('section', 'contact_faqs')->where('slot', 'section_settings')->first();
                    return [
                        'id' => $m ? $m->id : null,
                        'badge' => $m && $m->tag_text ? $m->tag_text : 'Help & Support',
                        'tag_text' => $m && $m->tag_text ? $m->tag_text : 'Help & Support',
                        'title' => $m && $m->title ? $m->title : 'Frequently Asked Questions',
                        'subtitle' => $m && $m->subtitle ? $m->subtitle : 'Quick answers about our handmade creations, custom orders, and delivery.',
                        'is_active' => $m ? (bool)$m->is_active : true,
                    ];
                })(),
                'items' => Media::where('page', 'contact')
                    ->where('section', 'contact_faqs')
                    ->where('slot', '!=', 'section_settings')
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
                            'status' => (bool)$item->is_active,
                            'updated_at_formatted' => $item->updated_at ? $item->updated_at->format('M d, Y') : '',
                        ];
                    }),
            ],
        ];

        // 2. Fetch assigned media grouped by slot
        $allMedia = Media::whereNotNull('slot')->get();
        $mediaBySlot = [];

        foreach ($allMedia as $m) {
            if ($m->page && $m->section && $m->slot) {
                $key = "{$m->page}_{$m->section}_{$m->slot}";
                if (!isset($mediaBySlot[$key]) || $m->is_active) {
                    $mediaBySlot[$key] = [
                        'id' => $m->id,
                        'url' => $m->url,
                        'file_name' => $m->file_name,
                        'desktop_image' => $m->desktop_image_url ?: $m->url,
                        'mobile_image' => $m->mobile_image_url ?: ($m->desktop_image_url ?: $m->url),
                        'mobile_image_path' => $m->mobile_image_path,
                        'title' => $m->title,
                        'tagline' => $m->tag_text ?: $m->subtitle,
                        'subtitle' => $m->subtitle,
                        'description' => $m->description,
                        'cta_text' => $m->cta_text,
                        'cta_link' => $m->cta_link,
                        'primary_button_text' => $m->cta_text ?: 'Shop Now',
                        'primary_button_link' => $m->cta_link ?: '/shop',
                        'secondary_cta_text' => $m->secondary_cta_text,
                        'secondary_cta_link' => $m->secondary_cta_link,
                        'secondary_button_text' => $m->secondary_cta_text ?: 'Explore Collections',
                        'secondary_button_link' => $m->secondary_cta_link ?: '/shop',
                        'tag_text' => $m->tag_text,
                        'alt_text' => $m->alt_text,
                        'device' => $m->device,
                        'sort_order' => $m->sort_order,
                        'size_formatted' => $this->formatSize($m->file_size ?: 0),
                        'mime_type' => $m->mime_type,
                        'is_active' => (bool)$m->is_active,
                        'status' => (bool)$m->is_active,
                        'updated_at_formatted' => $m->updated_at ? $m->updated_at->format('M d, Y') : '',
                    ];
                }
            }
        }

        // Attach media to slots
        foreach ($sections as &$sec) {
            if (isset($sec['slots'])) {
                foreach ($sec['slots'] as &$slotDef) {
                    $key = "{$slotDef['page']}_{$slotDef['section']}_{$slotDef['slot']}";
                    if (isset($mediaBySlot[$key])) {
                        $slotDef['media'] = $mediaBySlot[$key];
                    } elseif (isset($slotDef['media_id'])) {
                        $found = $allMedia->firstWhere('id', $slotDef['media_id']);
                        if ($found) {
                            $slotDef['media'] = [
                                'id' => $found->id,
                                'url' => $found->url,
                                'file_name' => $found->file_name,
                                'desktop_image' => $found->desktop_image_url ?: $found->url,
                                'mobile_image' => $found->mobile_image_url ?: ($found->desktop_image_url ?: $found->url),
                                'mobile_image_path' => $found->mobile_image_path,
                                'title' => $found->title,
                                'tagline' => $found->tag_text ?: $found->subtitle,
                                'subtitle' => $found->subtitle,
                                'description' => $found->description,
                                'cta_text' => $found->cta_text,
                                'cta_link' => $found->cta_link,
                                'primary_button_text' => $found->cta_text ?: 'Shop Now',
                                'primary_button_link' => $found->cta_link ?: '/shop',
                                'secondary_cta_text' => $found->secondary_cta_text,
                                'secondary_cta_link' => $found->secondary_cta_link,
                                'secondary_button_text' => $found->secondary_cta_text ?: 'Explore Collections',
                                'secondary_button_link' => $found->secondary_cta_link ?: '/shop',
                                'tag_text' => $found->tag_text,
                                'alt_text' => $found->alt_text,
                                'device' => $found->device,
                                'sort_order' => $found->sort_order,
                                'size_formatted' => $this->formatSize($found->file_size ?: 0),
                                'mime_type' => $found->mime_type,
                                'is_active' => (bool)$found->is_active,
                                'status' => (bool)$found->is_active,
                                'updated_at_formatted' => $found->updated_at ? $found->updated_at->format('M d, Y') : '',
                            ];
                        }
                    }
                }
            }
        }

        // 3. Fetch Categories
        $categories = Category::with('image')->orderBy('sort_order', 'asc')->get()->map(function ($cat) {
            $imageUrl = null;
            if ($cat->image && $cat->image->file_path) {
                $imageUrl = $cat->image->url;
            } else {
                $imageUrl = asset('images/categories/' . $cat->slug . '.jpg');
            }

            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'image_id' => $cat->image_id,
                'image_url' => $imageUrl,
                'status' => (bool)$cat->status,
                'sort_order' => $cat->sort_order ?: 1,
                'product_count' => $cat->products()->where('status', 'active')->count(),
            ];
        });

        // 4. Fetch Best Sellers from Products
        $bestSellers = Product::with(['defaultVariant.images'])
            ->where('status', 'active')
            ->where(function ($q) {
                $q->where('is_bestseller', true)->orWhere('is_featured', true);
            })
            ->limit(8)
            ->get()
            ->map(function ($p) {
                $img = null;
                if ($p->defaultVariant && $p->defaultVariant->images->isNotEmpty()) {
                    $img = $p->defaultVariant->images->first()->url;
                }
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'price' => $p->defaultVariant ? $p->defaultVariant->price : 0,
                    'image' => $img ?: asset('images/logo/Logo_1.png'),
                    'is_bestseller' => (bool)$p->is_bestseller,
                    'is_featured' => (bool)$p->is_featured,
                ];
            });

        $footerSec = collect($sections)->firstWhere('id', 'footer');
        $navbarSec = collect($sections)->firstWhere('id', 'navbar_settings');

        return response()->json([
            'success' => true,
            'data' => [
                'sections' => $sections,
                'categories' => $categories,
                'best_sellers' => $bestSellers,
                'total_media' => $allMedia->count(),
                'footer_settings' => $footerSec['metadata'] ?? null,
                'navbar_settings' => $navbarSec['metadata'] ?? null,
            ]
        ]);
    }

    /**
     * Assign or Upload image directly to a slot
     */
    public function assignSlot(Request $request): JsonResponse
    {
        $request->validate([
            'page' => 'required|string',
            'section' => 'required|string',
            'slot' => 'required|string',
            'device' => 'nullable|string',
            'media_id' => 'nullable',
            'file' => 'nullable|file|max:20480',
            'title' => 'nullable|string',
            'subtitle' => 'nullable|string',
            'description' => 'nullable|string',
            'cta_text' => 'nullable|string',
            'cta_link' => 'nullable|string',
            'tag_text' => 'nullable|string',
            'alt_text' => 'nullable|string',
        ]);

        $page = $request->input('page');
        $section = $request->input('section');
        $slot = $request->input('slot');
        $device = $request->input('device', 'all');
        $title = $request->input('title');
        $subtitle = $request->input('subtitle');
        $description = $request->input('description');
        $ctaText = $request->input('cta_text');
        $ctaLink = $request->input('cta_link');
        $tagText = $request->input('tag_text');
        $altText = $request->input('alt_text');

        // If file uploaded directly
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $uniqueName = "{$section}_{$slot}_" . time() . '_' . Str::random(4) . '.' . $extension;

            if ($page === 'about' || str_starts_with($section, 'about_') || $section === 'craft_pillars') {
                $targetFolder = 'images/about';
            } elseif ($page === 'contact' || str_starts_with($section, 'contact_')) {
                $targetFolder = 'images/contact';
            } elseif ($section === 'hero') {
                $targetFolder = 'images/hero';
            } elseif ($section === 'footer') {
                $targetFolder = 'images/footer';
            } elseif ($section === 'categories') {
                $targetFolder = 'images/categories';
            } elseif ($page === 'shop' || str_starts_with($section, 'shop_')) {
                $targetFolder = 'images/shop';
            } else {
                $targetFolder = 'images/homepage';
            }
            $targetDirectory = public_path($targetFolder);
            if (!File::isDirectory($targetDirectory)) {
                File::makeDirectory($targetDirectory, 0755, true, true);
            }

            $file->move($targetDirectory, $uniqueName);
            $relPath = $targetFolder . '/' . $uniqueName;
            $fileSize = file_exists($targetDirectory . DIRECTORY_SEPARATOR . $uniqueName) ? filesize($targetDirectory . DIRECTORY_SEPARATOR . $uniqueName) : 0;

            // Deactivate existing media on this slot
            Media::where('page', $page)
                ->where('section', $section)
                ->where('slot', $slot)
                ->update(['is_active' => false]);

            $media = Media::create([
                'file_name' => $originalName,
                'file_path' => $relPath,
                'disk' => 'local',
                'mime_type' => 'image/' . ($extension === 'png' ? 'png' : ($extension === 'webp' ? 'webp' : ($extension === 'svg' ? 'svg+xml' : 'jpeg'))),
                'file_type' => 'image',
                'file_size' => $fileSize,
                'alt_text' => $altText ?: $title ?: pathinfo($originalName, PATHINFO_FILENAME),
                'page' => $page,
                'section' => $section,
                'slot' => $slot,
                'device' => $device,
                'title' => $title ?: ucfirst(str_replace('_', ' ', $slot)),
                'subtitle' => $subtitle,
                'description' => $description,
                'cta_text' => $ctaText,
                'cta_link' => $ctaLink,
                'tag_text' => $tagText,
                'is_active' => true,
                'uploaded_by' => $this->getAdminId(),
                'uploader_type' => 'admin',
            ]);

            if ($section === 'categories' || str_starts_with($slot, 'category_')) {
                $catSlug = str_replace('category_', '', $slot);
                Category::where('slug', $catSlug)->update(['image_id' => $media->id]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded and applied to slot successfully!',
                'data' => [
                    'media_id' => $media->id,
                    'url' => $media->url,
                    'slot' => $slot,
                ]
            ]);
        }

        // If assigning an existing media record or updating without a new file
        $media = null;
        if ($request->filled('media_id') && is_numeric($request->input('media_id'))) {
            $media = Media::find($request->input('media_id'));
        }

        if (!$media) {
            $media = Media::where('page', $page)
                ->where('section', $section)
                ->where('slot', $slot)
                ->first();
        }

        if ($media) {
            Media::where('page', $page)
                ->where('section', $section)
                ->where('slot', $slot)
                ->where('id', '!=', $media->id)
                ->update(['is_active' => false]);

            $media->update([
                'page' => $page,
                'section' => $section,
                'slot' => $slot,
                'device' => $device,
                'title' => $title ?: $media->title ?: ucfirst(str_replace('_', ' ', $slot)),
                'subtitle' => $subtitle !== null ? $subtitle : $media->subtitle,
                'description' => $description !== null ? $description : $media->description,
                'cta_text' => $ctaText !== null ? $ctaText : $media->cta_text,
                'cta_link' => $ctaLink !== null ? $ctaLink : $media->cta_link,
                'tag_text' => $tagText !== null ? $tagText : $media->tag_text,
                'alt_text' => $altText !== null ? $altText : $media->alt_text,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Slot configured and updated successfully!',
                'data' => [
                    'media_id' => $media->id,
                    'url' => $media->url,
                    'slot' => $slot,
                ]
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Please select a file to upload.'], 400);
    }

    /**
     * Update Media Metadata & Rich Section Content
     */
    public function updateMetadata(Request $request, $id = null): JsonResponse
    {
        $request->validate([
            'title' => 'nullable|string',
            'subtitle' => 'nullable|string',
            'description' => 'nullable|string',
            'cta_text' => 'nullable|string',
            'cta_link' => 'nullable|string',
            'tag_text' => 'nullable|string',
            'alt_text' => 'nullable|string',
            'device' => 'nullable|string|in:all,desktop,mobile,tablet',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable',
            'page' => 'nullable|string',
            'section' => 'nullable|string',
            'slot' => 'nullable|string',
        ]);

        $media = null;
        if ($id && is_numeric($id) && (int)$id > 0) {
            $media = Media::find($id);
        }

        if (!$media && $request->filled('slot')) {
            $media = Media::where('page', $request->input('page', 'homepage'))
                ->where('section', $request->input('section'))
                ->where('slot', $request->input('slot'))
                ->first();
        }

        if (!$media && $request->filled('section')) {
            $media = Media::where('page', $request->input('page', 'homepage'))
                ->where('section', $request->input('section'))
                ->first();
        }

        if (!$media && ($request->filled('slot') || $request->filled('section'))) {
            $media = Media::create([
                'file_name' => ($request->input('slot') ?: $request->input('section')) . '.png',
                'file_path' => 'images/logo/Logo_1.png',
                'disk' => 'local',
                'mime_type' => 'image/png',
                'file_type' => 'image',
                'page' => $request->input('page', 'homepage'),
                'section' => $request->input('section', 'homepage'),
                'slot' => $request->input('slot') ?: 'default',
                'device' => $request->input('device', 'all'),
                'title' => $request->input('title', ucfirst(str_replace('_', ' ', $request->input('slot') ?: $request->input('section')))),
                'is_active' => true,
                'uploaded_by' => $this->getAdminId(),
                'uploader_type' => 'admin',
            ]);
        }

        if (!$media) {
            return response()->json(['success' => false, 'message' => 'Media record not found.'], 404);
        }

        $updateData = [];
        if ($request->has('title')) $updateData['title'] = $request->input('title');
        if ($request->has('subtitle')) $updateData['subtitle'] = $request->input('subtitle');
        if ($request->has('description')) $updateData['description'] = $request->input('description');
        if ($request->has('cta_text')) $updateData['cta_text'] = $request->input('cta_text');
        if ($request->has('cta_link')) $updateData['cta_link'] = $request->input('cta_link');
        if ($request->has('tag_text')) $updateData['tag_text'] = $request->input('tag_text');
        if ($request->has('alt_text')) $updateData['alt_text'] = $request->input('alt_text');
        if ($request->has('device')) $updateData['device'] = $request->input('device');
        if ($request->has('sort_order')) $updateData['sort_order'] = $request->input('sort_order');
        if ($request->has('is_active')) $updateData['is_active'] = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);

        $media->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Section options and metadata saved successfully!',
            'data' => $media
        ]);
    }

    /**
     * Detach Media from Slot safely (does NOT physically delete file)
     */
    public function detachSlot(Request $request): JsonResponse
    {
        $request->validate([
            'page' => 'required|string',
            'section' => 'required|string',
            'slot' => 'required|string',
        ]);

        Media::where('page', $request->input('page'))
            ->where('section', $request->input('section'))
            ->where('slot', $request->input('slot'))
            ->update([
                'slot' => null,
                'is_active' => false,
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Image detached from slot successfully. Fallback image will be used.'
        ]);
    }

    /**
     * Toggle Media Active Status
     */
    public function toggleStatus($id): JsonResponse
    {
        $media = Media::findOrFail($id);
        $media->is_active = !$media->is_active;
        $media->save();

        return response()->json([
            'success' => true,
            'message' => 'Status changed to ' . ($media->is_active ? 'Active' : 'Inactive'),
            'is_active' => $media->is_active
        ]);
    }

    /**
     * Update Category Image / Metadata from Media Manager
     */
    public function updateCategory(Request $request, $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $uniqueName = "{$category->slug}_" . time() . '.' . $extension;

            $targetDirectory = public_path('images/categories');
            if (!File::isDirectory($targetDirectory)) {
                File::makeDirectory($targetDirectory, 0755, true, true);
            }

            $file->move($targetDirectory, $uniqueName);
            $relPath = 'images/categories/' . $uniqueName;

            $media = Media::create([
                'file_name' => $originalName,
                'file_path' => $relPath,
                'disk' => 'local',
                'mime_type' => 'image/' . ($extension === 'png' ? 'png' : 'jpeg'),
                'file_type' => 'image',
                'file_size' => filesize($targetDirectory . DIRECTORY_SEPARATOR . $uniqueName),
                'alt_text' => $category->name . ' Category',
                'page' => 'homepage',
                'section' => 'categories',
                'slot' => 'category_' . $category->slug,
                'device' => 'all',
                'title' => $category->name . ' Category Image',
                'is_active' => true,
                'uploaded_by' => $this->getAdminId(),
                'uploader_type' => 'admin',
            ]);

            $category->image_id = $media->id;
        } elseif ($request->filled('media_id')) {
            $category->image_id = $request->input('media_id');
        }

        if ($request->has('sort_order')) {
            $category->sort_order = (int) $request->input('sort_order');
        }

        if ($request->has('status')) {
            $category->status = filter_var($request->input('status'), FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        }

        $category->save();

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully!',
            'data' => [
                'id' => $category->id,
                'name' => $category->name,
                'image_url' => $category->image ? $category->image->url : asset('images/categories/' . $category->slug . '.jpg'),
                'status' => (bool)$category->status,
                'sort_order' => $category->sort_order,
            ]
        ]);
    }

    /**
     * Add a new Hero Carousel Slide
     */
    public function addHeroSlide(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string',
            'tagline' => 'nullable|string',
            'subtitle' => 'nullable|string',
            'description' => 'nullable|string',
            'primary_button_text' => 'nullable|string',
            'primary_button_link' => 'nullable|string',
            'secondary_button_text' => 'nullable|string',
            'secondary_button_link' => 'nullable|string',
            'cta_text' => 'nullable|string',
            'cta_link' => 'nullable|string',
            'tag_text' => 'nullable|string',
            'alt_text' => 'nullable|string',
            'status' => 'nullable',
            'is_active' => 'nullable',
            'sort_order' => 'nullable',
            'file' => 'nullable|file|max:20480',
            'desktop_image' => 'nullable|file|max:20480',
            'mobile_file' => 'nullable|file|max:20480',
            'mobile_image' => 'nullable|file|max:20480',
            'desktop_media_id' => 'nullable',
            'mobile_media_id' => 'nullable',
        ]);

        $maxSort = Media::where('page', 'homepage')->where('section', 'hero')->max('sort_order') ?: 0;
        $sortOrder = $request->filled('sort_order') && (int)$request->input('sort_order') > 0
            ? (int)$request->input('sort_order')
            : ($maxSort + 1);

        $title = $request->input('title') ?: ('Hero Slide ' . $sortOrder);
        $tagline = $request->input('tagline') ?: ($request->input('tag_text') ?: ($request->input('subtitle') ?: 'Good Things Are Handmade'));
        $description = $request->input('description') ?: ($request->input('subtitle') ?: '');
        $primaryBtnText = $request->input('primary_button_text') ?: ($request->input('cta_text') ?: 'Shop Now');
        $primaryBtnLink = $request->input('primary_button_link') ?: ($request->input('cta_link') ?: '/shop');
        $secondaryBtnText = $request->input('secondary_button_text') ?: ($request->input('secondary_cta_text') ?: 'Explore Collections');
        $secondaryBtnLink = $request->input('secondary_button_link') ?: ($request->input('secondary_cta_link') ?: '/shop');
        $altText = $request->input('alt_text') ?: $title;
        $isActive = $request->has('status') 
            ? filter_var($request->input('status'), FILTER_VALIDATE_BOOLEAN)
            : ($request->has('is_active') ? filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN) : true);

        // Handle Desktop Image
        $desktopRelPath = 'images/hero/hero-enhanced.jpg';
        $originalName = 'hero-slide.jpg';
        $mimeType = 'image/jpeg';
        $fileSize = 0;

        $targetFolder = 'images/hero';
        $targetDirectory = public_path($targetFolder);
        if (!File::isDirectory($targetDirectory)) {
            File::makeDirectory($targetDirectory, 0755, true, true);
        }

        $desktopFile = $request->file('file') ?: $request->file('desktop_image');
        if ($desktopFile) {
            $originalName = $desktopFile->getClientOriginalName();
            $ext = strtolower($desktopFile->getClientOriginalExtension() ?: 'jpg');
            $uniqueName = 'hero_desktop_' . $sortOrder . '_' . time() . '.' . $ext;
            $desktopFile->move($targetDirectory, $uniqueName);
            $desktopRelPath = $targetFolder . '/' . $uniqueName;
            $mimeType = 'image/' . ($ext === 'png' ? 'png' : ($ext === 'webp' ? 'webp' : ($ext === 'svg' ? 'svg+xml' : 'jpeg')));
            $fileSize = file_exists($targetDirectory . '/' . $uniqueName) ? filesize($targetDirectory . '/' . $uniqueName) : 0;
        } elseif ($request->filled('desktop_media_id') && is_numeric($request->input('desktop_media_id'))) {
            $existingMedia = Media::find($request->input('desktop_media_id'));
            if ($existingMedia) {
                $desktopRelPath = $existingMedia->file_path;
                $originalName = $existingMedia->file_name;
                $mimeType = $existingMedia->mime_type;
                $fileSize = $existingMedia->file_size;
            }
        } elseif ($request->filled('desktop_image_path')) {
            $desktopRelPath = $request->input('desktop_image_path');
        }

        // Handle Mobile Image
        $mobileRelPath = null;
        $mobileFile = $request->file('mobile_file') ?: $request->file('mobile_image');
        if ($mobileFile) {
            $mobileExt = strtolower($mobileFile->getClientOriginalExtension() ?: 'jpg');
            $mobileUniqueName = 'hero_mobile_' . $sortOrder . '_' . time() . '.' . $mobileExt;
            $mobileFile->move($targetDirectory, $mobileUniqueName);
            $mobileRelPath = $targetFolder . '/' . $mobileUniqueName;
        } elseif ($request->filled('mobile_media_id') && is_numeric($request->input('mobile_media_id'))) {
            $existingMobileMedia = Media::find($request->input('mobile_media_id'));
            if ($existingMobileMedia) {
                $mobileRelPath = $existingMobileMedia->file_path;
            }
        } elseif ($request->filled('mobile_image_path')) {
            $mobileRelPath = $request->input('mobile_image_path');
        }

        $slotName = 'hero_slide_' . $sortOrder . '_' . time();

        $media = Media::create([
            'file_name' => $originalName,
            'file_path' => $desktopRelPath,
            'mobile_image_path' => $mobileRelPath,
            'disk' => 'local',
            'mime_type' => $mimeType,
            'file_type' => 'image',
            'file_size' => $fileSize,
            'alt_text' => $altText,
            'page' => 'homepage',
            'section' => 'hero',
            'slot' => $slotName,
            'device' => 'all',
            'title' => $title,
            'subtitle' => $description,
            'description' => $description,
            'cta_text' => $primaryBtnText,
            'cta_link' => $primaryBtnLink,
            'secondary_cta_text' => $secondaryBtnText,
            'secondary_cta_link' => $secondaryBtnLink,
            'tag_text' => $tagline,
            'sort_order' => $sortOrder,
            'is_active' => $isActive,
            'uploaded_by' => $this->getAdminId(),
            'uploader_type' => 'admin',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Hero slide created successfully.',
            'data' => [
                'id' => $media->id,
                'media_id' => $media->id,
                'title' => $media->title,
                'tagline' => $media->tag_text,
                'url' => $media->url,
                'desktop_image' => $media->desktop_image_url,
                'mobile_image' => $media->mobile_image_url,
                'status' => (bool)$media->is_active,
                'sort_order' => $media->sort_order,
                'slot' => $slotName,
            ]
        ]);
    }

    /**
     * Get a single Hero Slide data for editing
     */
    public function getHeroSlide($id): JsonResponse
    {
        $media = Media::where('page', 'homepage')
            ->where('section', 'hero')
            ->where('id', $id)
            ->first();

        if (!$media) {
            $media = Media::find($id);
        }

        if (!$media) {
            return response()->json(['success' => false, 'message' => 'Hero slide not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $media->id,
                'title' => $media->title,
                'tagline' => $media->tag_text ?: $media->subtitle,
                'subtitle' => $media->subtitle,
                'description' => $media->description,
                'primary_button_text' => $media->cta_text ?: 'Shop Now',
                'primary_button_link' => $media->cta_link ?: '/shop',
                'secondary_button_text' => $media->secondary_cta_text ?: 'Explore Collections',
                'secondary_button_link' => $media->secondary_cta_link ?: '/shop',
                'cta_text' => $media->cta_text,
                'cta_link' => $media->cta_link,
                'secondary_cta_text' => $media->secondary_cta_text,
                'secondary_cta_link' => $media->secondary_cta_link,
                'tag_text' => $media->tag_text,
                'alt_text' => $media->alt_text,
                'desktop_image' => $media->desktop_image_url,
                'mobile_image' => $media->mobile_image_url,
                'desktop_image_path' => $media->file_path,
                'mobile_image_path' => $media->mobile_image_path,
                'status' => (bool)$media->is_active,
                'is_active' => (bool)$media->is_active,
                'sort_order' => (int)$media->sort_order,
            ]
        ]);
    }

    /**
     * Update an existing Hero Slide
     */
    public function updateHeroSlide(Request $request, $id): JsonResponse
    {
        $media = Media::where('page', 'homepage')
            ->where('section', 'hero')
            ->where('id', $id)
            ->first();

        if (!$media) {
            $media = Media::find($id);
        }

        if (!$media) {
            return response()->json(['success' => false, 'message' => 'Hero slide not found.'], 404);
        }

        $request->validate([
            'title' => 'required|string',
            'tagline' => 'nullable|string',
            'subtitle' => 'nullable|string',
            'description' => 'nullable|string',
            'primary_button_text' => 'nullable|string',
            'primary_button_link' => 'nullable|string',
            'secondary_button_text' => 'nullable|string',
            'secondary_button_link' => 'nullable|string',
            'cta_text' => 'nullable|string',
            'cta_link' => 'nullable|string',
            'tag_text' => 'nullable|string',
            'alt_text' => 'nullable|string',
            'status' => 'nullable',
            'is_active' => 'nullable',
            'sort_order' => 'nullable',
            'file' => 'nullable|file|max:20480',
            'desktop_image' => 'nullable|file|max:20480',
            'mobile_file' => 'nullable|file|max:20480',
            'mobile_image' => 'nullable|file|max:20480',
        ]);

        $targetFolder = 'images/hero';
        $targetDirectory = public_path($targetFolder);
        if (!File::isDirectory($targetDirectory)) {
            File::makeDirectory($targetDirectory, 0755, true, true);
        }

        $desktopFile = $request->file('file') ?: $request->file('desktop_image');
        if ($desktopFile) {
            $ext = strtolower($desktopFile->getClientOriginalExtension() ?: 'jpg');
            $uniqueName = 'hero_desktop_' . ($media->sort_order ?: $media->id) . '_' . time() . '.' . $ext;
            $desktopFile->move($targetDirectory, $uniqueName);
            $media->file_path = $targetFolder . '/' . $uniqueName;
            $media->file_name = $desktopFile->getClientOriginalName();
            $media->mime_type = 'image/' . ($ext === 'png' ? 'png' : ($ext === 'webp' ? 'webp' : ($ext === 'svg' ? 'svg+xml' : 'jpeg')));
            $media->file_size = file_exists($targetDirectory . '/' . $uniqueName) ? filesize($targetDirectory . '/' . $uniqueName) : 0;
        } elseif ($request->filled('desktop_media_id') && is_numeric($request->input('desktop_media_id'))) {
            $chosen = Media::find($request->input('desktop_media_id'));
            if ($chosen) {
                $media->file_path = $chosen->file_path;
                $media->file_name = $chosen->file_name;
                $media->mime_type = $chosen->mime_type;
                $media->file_size = $chosen->file_size;
            }
        }

        $mobileFile = $request->file('mobile_file') ?: $request->file('mobile_image');
        if ($mobileFile) {
            $mobileExt = strtolower($mobileFile->getClientOriginalExtension() ?: 'jpg');
            $mobileUniqueName = 'hero_mobile_' . ($media->sort_order ?: $media->id) . '_' . time() . '.' . $mobileExt;
            $mobileFile->move($targetDirectory, $mobileUniqueName);
            $media->mobile_image_path = $targetFolder . '/' . $mobileUniqueName;
        } elseif ($request->filled('mobile_media_id') && is_numeric($request->input('mobile_media_id'))) {
            $chosenMobile = Media::find($request->input('mobile_media_id'));
            if ($chosenMobile) {
                $media->mobile_image_path = $chosenMobile->file_path;
            }
        }

        if ($request->has('title')) $media->title = $request->input('title');
        if ($request->has('tagline')) $media->tag_text = $request->input('tagline');
        elseif ($request->has('tag_text')) $media->tag_text = $request->input('tag_text');
        
        if ($request->has('description')) {
            $media->description = $request->input('description');
            $media->subtitle = $request->input('description');
        } elseif ($request->has('subtitle')) {
            $media->subtitle = $request->input('subtitle');
            $media->description = $request->input('subtitle');
        }

        if ($request->has('primary_button_text')) $media->cta_text = $request->input('primary_button_text');
        elseif ($request->has('cta_text')) $media->cta_text = $request->input('cta_text');

        if ($request->has('primary_button_link')) $media->cta_link = $request->input('primary_button_link');
        elseif ($request->has('cta_link')) $media->cta_link = $request->input('cta_link');

        if ($request->has('secondary_button_text')) $media->secondary_cta_text = $request->input('secondary_button_text');
        elseif ($request->has('secondary_cta_text')) $media->secondary_cta_text = $request->input('secondary_cta_text');

        if ($request->has('secondary_button_link')) $media->secondary_cta_link = $request->input('secondary_button_link');
        elseif ($request->has('secondary_cta_link')) $media->secondary_cta_link = $request->input('secondary_cta_link');

        if ($request->has('alt_text')) $media->alt_text = $request->input('alt_text');

        if ($request->has('sort_order') && (int)$request->input('sort_order') > 0) {
            $media->sort_order = (int)$request->input('sort_order');
        }

        if ($request->has('status')) {
            $media->is_active = filter_var($request->input('status'), FILTER_VALIDATE_BOOLEAN);
        } elseif ($request->has('is_active')) {
            $media->is_active = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
        }

        $media->save();

        return response()->json([
            'success' => true,
            'message' => 'Hero slide updated successfully.',
            'data' => [
                'id' => $media->id,
                'title' => $media->title,
                'tagline' => $media->tag_text,
                'url' => $media->url,
                'desktop_image' => $media->desktop_image_url,
                'mobile_image' => $media->mobile_image_url,
                'status' => (bool)$media->is_active,
                'sort_order' => $media->sort_order,
            ]
        ]);
    }

    /**
     * Delete a Hero Slide
     */
    public function deleteHeroSlide($id): JsonResponse
    {
        $media = Media::where('page', 'homepage')
            ->where('section', 'hero')
            ->where('id', $id)
            ->first();

        if (!$media) {
            $media = Media::find($id);
        }

        if (!$media) {
            return response()->json([
                'success' => false,
                'message' => 'Hero slide not found.'
            ], 404);
        }

        // Delete physical file if custom upload
        if (str_starts_with($media->file_path, 'images/hero/') && file_exists(public_path($media->file_path))) {
            @unlink(public_path($media->file_path));
        }

        if ($media->mobile_image_path && str_starts_with($media->mobile_image_path, 'images/hero/') && file_exists(public_path($media->mobile_image_path))) {
            @unlink(public_path($media->mobile_image_path));
        }

        $media->delete();

        return response()->json([
            'success' => true,
            'message' => 'Hero slide deleted successfully!'
        ]);
    }

    /**
     * Get Media Data for Modals/AJAX (directly from MySQL Database)
     */
    public function getData(Request $request)
    {
        $query = Media::query()->latest('id');

        // Page/Section filters
        if ($request->filled('page') && $request->page !== 'all') {
            $query->where('page', $request->page);
        }
        if ($request->filled('section') && $request->section !== 'all') {
            $query->where('section', $request->section);
        }
        if ($request->filled('device') && $request->device !== 'all') {
            $query->whereIn('device', [$request->device, 'all']);
        }

        // Search filter
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('file_name', 'like', "%{$search}%")
                  ->orWhere('alt_text', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('section', 'like', "%{$search}%")
                  ->orWhere('slot', 'like', "%{$search}%")
                  ->orWhere('file_path', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('file_type', $request->type);
        }

        $perPage = (int) $request->input('per_page', 24);
        $mediaPaginated = $query->paginate($perPage);

        $data = $mediaPaginated->getCollection()->map(function ($item) {
            return [
                'id' => $item->id,
                'url' => $item->url,
                'thumbnail_url' => $item->thumb_url,
                'thumb_url' => $item->thumb_url,
                'full_url' => $item->full_url,
                'file_path' => $item->file_path,
                'file_name' => $item->file_name,
                'filename' => $item->file_name,
                'name' => $item->title ?: $item->file_name,
                'title' => $item->title,
                'page' => $item->page,
                'section' => $item->section,
                'slot' => $item->slot,
                'device' => $item->device,
                'is_active' => (bool)$item->is_active,
                'sort_order' => $item->sort_order,
                'mime_type' => $item->mime_type ?: 'image/jpeg',
                'size' => $item->file_size ?: 0,
                'size_formatted' => $this->formatSize($item->file_size ?: 0),
                'is_image' => str_starts_with($item->mime_type ?? 'image/', 'image/'),
                'created_at' => $item->created_at ? $item->created_at->toIso8601String() : now()->toIso8601String(),
                'created_at_formatted' => $item->created_at ? $item->created_at->format('M d, Y') : '',
                'alt_text' => $item->alt_text,
            ];
        });

        return response()->json([
            'current_page' => $mediaPaginated->currentPage(),
            'data' => $data,
            'last_page' => $mediaPaginated->lastPage(),
            'total' => $mediaPaginated->total(),
            'per_page' => $mediaPaginated->perPage(),
            'from' => $mediaPaginated->firstItem(),
            'to' => $mediaPaginated->lastItem(),
            'prev_page_url' => $mediaPaginated->previousPageUrl(),
            'next_page_url' => $mediaPaginated->nextPageUrl(),
            'links' => $mediaPaginated->linkCollection()->toArray(),
        ]);
    }

    /**
     * Upload Media from Desktop/Mobile Gallery
     */
    public function upload(Request $request)
    {
        $request->validate([
            'files' => 'nullable|array',
            'files.*' => 'file|mimes:jpeg,jpg,png,webp,gif,svg,jfif,avif,heic|max:20480',
            'file' => 'nullable|file|mimes:jpeg,jpg,png,webp,gif,svg,jfif,avif,heic|max:20480',
            'page' => 'nullable|string',
            'section' => 'nullable|string',
            'slot' => 'nullable|string',
            'device' => 'nullable|string',
        ]);

        $files = [];
        if ($request->hasFile('files')) {
            $files = $request->file('files');
        } elseif ($request->hasFile('file')) {
            $files = [$request->file('file')];
        }

        if (empty($files)) {
            return response()->json(['success' => false, 'message' => 'No files were uploaded.'], 400);
        }

        $page = $request->input('page', 'homepage');
        $section = $request->input('section', 'unassigned');
        $slot = $request->input('slot', 'default');
        $device = $request->input('device', 'all');

        $uploadedMedia = [];
        $errors = [];

        $targetFolder = 'images/' . ($section === 'hero' ? 'hero' : ($section === 'categories' ? 'categories' : ($section === 'footer' ? 'footer' : 'products')));
        $targetDirectory = public_path($targetFolder);
        if (!File::isDirectory($targetDirectory)) {
            @File::makeDirectory($targetDirectory, 0777, true, true);
        }
        @chmod($targetDirectory, 0777);

        // Fallback: If public/images/... is not writable, fall back to storage/app/public
        $useStorageFallback = false;
        if (!is_writable($targetDirectory)) {
            $storageFolder = storage_path('app/public/' . $targetFolder);
            if (!File::isDirectory($storageFolder)) {
                @File::makeDirectory($storageFolder, 0777, true, true);
            }
            @chmod($storageFolder, 0777);
            if (is_writable($storageFolder)) {
                $targetDirectory = $storageFolder;
                $useStorageFallback = true;
            }
        }

        foreach ($files as $file) {
            try {
                $originalName = $file->getClientOriginalName();
                $allowedExtensions = ['jpeg', 'jpg', 'png', 'webp', 'gif', 'svg', 'jfif', 'avif', 'heic'];
                $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg');
                if (!in_array($extension, $allowedExtensions)) {
                    $clientExt = strtolower($file->getClientOriginalExtension());
                    if (in_array($clientExt, $allowedExtensions)) {
                        $extension = $clientExt;
                    } else {
                        throw new \Exception("File extension .{$extension} is not permitted.");
                    }
                }
                $rawName = pathinfo($originalName, PATHINFO_FILENAME);
                $cleanSlug = Str::slug($rawName);
                $uniqueName = ($cleanSlug ?: 'knotelle-media') . '_' . time() . '_' . Str::random(4) . '.' . $extension;

                $file->move($targetDirectory, $uniqueName);
                $relPath = $useStorageFallback ? ('storage/' . $targetFolder . '/' . $uniqueName) : ($targetFolder . '/' . $uniqueName);
                $fullDiskPath = $targetDirectory . DIRECTORY_SEPARATOR . $uniqueName;
                $fileSize = file_exists($fullDiskPath) ? filesize($fullDiskPath) : 0;
                @chmod($fullDiskPath, 0666);

                $mimeType = match ($extension) {
                    'png' => 'image/png',
                    'webp' => 'image/webp',
                    'gif' => 'image/gif',
                    'svg' => 'image/svg+xml',
                    'avif' => 'image/avif',
                    'heic' => 'image/heic',
                    default => 'image/jpeg',
                };

                $media = Media::create([
                    'file_name' => $originalName,
                    'file_path' => $relPath,
                    'disk' => $useStorageFallback ? 'public' : 'local',
                    'mime_type' => $mimeType,
                    'file_type' => 'image',
                    'file_size' => $fileSize,
                    'alt_text' => $rawName,
                    'page' => $page,
                    'section' => $section,
                    'slot' => $slot,
                    'device' => $device,
                    'title' => $rawName,
                    'is_active' => true,
                    'uploaded_by' => $this->getAdminId(),
                    'uploader_type' => 'admin',
                ]);

                $uploadedMedia[] = [
                    'id' => $media->id,
                    'url' => $media->url,
                    'thumb_url' => $media->thumb_url,
                    'thumbnail_url' => $media->thumbnail_url,
                    'full_url' => $media->full_url,
                    'file_name' => $originalName,
                    'size_formatted' => $this->formatSize($fileSize),
                ];
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Media upload error: " . $e->getMessage());
                $errors[] = "Failed to upload {$file->getClientOriginalName()}: " . $e->getMessage();
            }
        }

        $success = count($uploadedMedia) > 0;

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Files uploaded successfully!' : (!empty($errors) ? implode('; ', $errors) : 'Failed to upload files'),
            'data' => $success ? $uploadedMedia[0] : null,
            'all_uploaded' => $uploadedMedia,
            'errors' => $errors,
        ], $success ? 200 : 422);
    }

    /**
     * Delete Media with Reference Checking
     */
    public function destroy($id)
    {
        $media = Media::findOrFail($id);

        // Check if media is referenced by categories
        if ($media->categories()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete media: It is linked as a primary category image. Please reassign the category first.'
            ], 400);
        }

        // Check if referenced by product variants
        if ($media->variantImages()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete media: It is assigned to one or more Product Variants. Please remove it from the product first.'
            ], 400);
        }

        // Delete physical file if created inside upload folders
        if (str_starts_with($media->file_path, 'images/products/') && file_exists(public_path($media->file_path))) {
            @unlink(public_path($media->file_path));
        }

        $media->delete();

        return response()->json(['success' => true, 'message' => 'Media deleted successfully']);
    }

    /**
     * Helper to auto-sync local Knotelle directory images into database
     */
    public function syncLocalImagesToDatabase(): void
    {
        $folders = [
            'images/products' => 'products',
            'images/categories' => 'categories',
            'images/logo' => 'global_assets',
            'images/hero' => 'hero',
            'images/homepage' => 'homepage',
            'images/footer' => 'footer',
        ];

        foreach ($folders as $relFolder => $group) {
            $absPath = public_path($relFolder);
            if (!File::isDirectory($absPath)) {
                continue;
            }

            $files = File::files($absPath);
            foreach ($files as $file) {
                $fileName = $file->getFilename();
                $filePath = $relFolder . '/' . $fileName;

                $exists = Media::where('file_path', $filePath)->first();
                if (!$exists) {
                    $ext = strtolower($file->getExtension());
                    $mime = ($ext === 'png') ? 'image/png' : (($ext === 'webp') ? 'image/webp' : 'image/jpeg');

                    Media::create([
                        'file_name' => $fileName,
                        'file_path' => $filePath,
                        'disk' => 'local',
                        'mime_type' => $mime,
                        'file_type' => 'image',
                        'file_size' => $file->getSize(),
                        'alt_text' => pathinfo($fileName, PATHINFO_FILENAME),
                        'page' => ($group === 'global_assets') ? 'global' : 'homepage',
                        'section' => $group,
                        'slot' => pathinfo($fileName, PATHINFO_FILENAME),
                        'device' => 'all',
                        'title' => pathinfo($fileName, PATHINFO_FILENAME),
                        'is_active' => true,
                        'uploaded_by' => 1,
                        'uploader_type' => 'admin',
                    ]);
                }
            }
        }
    }

    private function formatSize(int $bytes): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $pow = floor(log($bytes, 1024));
        return round($bytes / (1024 ** $pow), 1) . ' ' . $units[$pow];
    }

    /**
     * Create or Update Customer Review / Testimonial
     */
    public function saveTestimonial(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'nullable',
            'name' => 'required|string|max:255',
            'designation' => 'nullable|string|max:255',
            'message' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
            'is_active' => 'nullable',
        ]);

        $id = $request->input('id');
        $testimonial = ($id && is_numeric($id)) ? Testimonial::find($id) : new Testimonial();

        if (!$testimonial && $id) {
            return response()->json(['success' => false, 'message' => 'Review not found.'], 404);
        }

        if (!$testimonial) {
            $testimonial = new Testimonial();
        }

        $testimonial->name = trim($request->input('name'));
        $testimonial->designation = trim($request->input('designation')) ?: 'Verified Patron';
        $testimonial->message = trim($request->input('message'));
        $testimonial->rating = (int)$request->input('rating', 5);
        $testimonial->image = null; // Removed image option per design specification

        if ($request->has('is_active')) {
            $val = $request->input('is_active');
            $testimonial->is_active = ($val === '1' || $val === 1 || $val === true || $val === 'true' || $val === 'on');
        } else {
            $testimonial->is_active = false;
        }

        $testimonial->save();

        return response()->json([
            'success' => true,
            'message' => $id ? 'Customer review updated successfully!' : 'Customer review added successfully!',
            'data' => $testimonial,
        ]);
    }

    /**
     * Get single testimonial for editing
     */
    public function getTestimonial($id): JsonResponse
    {
        $testimonial = Testimonial::findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $testimonial,
        ]);
    }

    /**
     * Delete customer review
     */
    public function deleteTestimonial($id): JsonResponse
    {
        $testimonial = Testimonial::findOrFail($id);
        $testimonial->delete();
        return response()->json([
            'success' => true,
            'message' => 'Customer review deleted successfully.',
        ]);
    }

    /**
     * Toggle customer review active status
     */
    public function toggleTestimonial($id): JsonResponse
    {
        $testimonial = Testimonial::findOrFail($id);
        $testimonial->is_active = !$testimonial->is_active;
        $testimonial->save();
        return response()->json([
            'success' => true,
            'message' => 'Customer review status updated.',
            'is_active' => (bool)$testimonial->is_active,
        ]);
    }

    /**
     * Create / Add a new Video / Reel / Blog Video
     */
    public function addVideoReel(Request $request): JsonResponse
    {
        // Pre-check for PHP file upload size error before standard validation to give clear error
        if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] !== UPLOAD_ERR_OK && $_FILES['video_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $err = $_FILES['video_file']['error'];
            if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
                if ($request->filled('video_url')) {
                    unset($_FILES['video_file']);
                    $request->request->remove('video_file');
                } else {
                    $maxPhp = ini_get('upload_max_filesize') ?: '2M';
                    return response()->json([
                        'success' => false,
                        'message' => "The uploaded video exceeds server upload_max_filesize limit ({$maxPhp}). Please enter a direct video URL or upload a smaller file.",
                        'errors' => [
                            'video_file' => ["The uploaded video exceeds server limit ({$maxPhp}). Please enter a direct video URL or upload a smaller file."]
                        ]
                    ], 422);
                }
            }
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'content_type' => 'nullable|string|in:video,reel,blog_video',
            'category' => 'nullable|string|max:150',
            'category_name' => 'nullable|string|max:150',
            'tagline' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'nullable|string|max:50',
            'audio_name' => 'nullable|string|max:255',
            'likes' => 'nullable|integer|min:0',
            'comments' => 'nullable|integer|min:0',
            'views' => 'nullable|integer|min:0',
            'is_featured' => 'nullable',
            'status' => 'nullable',
            'is_active' => 'nullable',
            'sort_order' => 'nullable|integer|min:1',
            'video_file' => 'nullable|file|mimes:mp4,webm,mov,ogg,mkv,avi,qt,m4v,3gp,flv|max:204800',
            'video_url' => 'nullable|string|max:1000',
            'thumbnail_file' => 'nullable|file|image|max:20480',
            'thumbnail_media_id' => 'nullable',
            'thumbnail_url' => 'nullable|string|max:1000',
            'mobile_thumbnail_file' => 'nullable|file|image|max:20480',
        ]);

        $maxSort = Media::where('page', 'homepage')->where('section', 'blog_reels')->where('slot', '!=', 'section_settings')->max('sort_order') ?: 0;
        $sortOrder = $request->filled('sort_order') && (int)$request->input('sort_order') > 0
            ? (int)$request->input('sort_order')
            : ($maxSort + 1);

        $contentType = $request->input('content_type', 'reel');
        $categoryName = $request->input('category_name') ?: ($request->input('category') ?: 'Studio ASMR');
        $title = $request->input('title');
        $tagline = $request->input('tagline') ?: ($request->input('tag_text') ?: ($request->input('subtitle') ?: ''));
        $description = $request->input('description') ?: ($request->input('subtitle') ?: '');
        $duration = $request->input('duration') ?: '00:48';
        $audioName = $request->input('audio_name') ?: 'Original Audio';
        $likes = (int)$request->input('likes', 0);
        $comments = (int)$request->input('comments', 0);
        $views = (int)$request->input('views', 0);
        $isFeatured = $request->has('is_featured') ? filter_var($request->input('is_featured'), FILTER_VALIDATE_BOOLEAN) : false;
        $isActive = $request->has('status') 
            ? filter_var($request->input('status'), FILTER_VALIDATE_BOOLEAN)
            : ($request->has('is_active') ? filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN) : true);

        // 1. Handle Video Upload / URL
        $videoUrl = $request->input('video_url');
        $mimeType = 'video/mp4';
        $fileSize = 0;
        $originalFileName = 'video_' . time() . '.mp4';

        if ($request->hasFile('video_file')) {
            $vFile = $request->file('video_file');
            if ($vFile->isValid()) {
                $vOriginal = $vFile->getClientOriginalName();
                $vExt = strtolower($vFile->getClientOriginalExtension() ?: 'mp4');
                $vUniqueName = 'reel_' . $sortOrder . '_' . time() . '_' . Str::random(4) . '.' . $vExt;
                
                $targetDir = public_path('videos/reels');
                if (!File::isDirectory($targetDir)) {
                    File::makeDirectory($targetDir, 0777, true, true);
                }

                $vFile->move($targetDir, $vUniqueName);
                @chmod($targetDir . DIRECTORY_SEPARATOR . $vUniqueName, 0664);
                $videoUrl = '/videos/reels/' . $vUniqueName;
                $mimeType = $vFile->getClientMimeType() ?: ('video/' . ($vExt === 'mov' ? 'quicktime' : $vExt));
                $fileSize = file_exists($targetDir . '/' . $vUniqueName) ? filesize($targetDir . '/' . $vUniqueName) : 0;
                $originalFileName = $vOriginal;
            }
        }

        if (empty($videoUrl) && $request->filled('video_url')) {
            $videoUrl = $request->input('video_url');
        }

        // 2. Handle Thumbnail Image Upload / Media Library
        $thumbnailPath = '/images/homepage/middleimg.png';
        $thumbDir = public_path('images/reels');
        if (!File::isDirectory($thumbDir)) {
            File::makeDirectory($thumbDir, 0755, true, true);
        }

        if ($request->hasFile('thumbnail_file')) {
            $tFile = $request->file('thumbnail_file');
            $tExt = strtolower($tFile->getClientOriginalExtension() ?: 'jpg');
            $tUniqueName = 'cover_' . $sortOrder . '_' . time() . '_' . Str::random(4) . '.' . $tExt;
            $tFile->move($thumbDir, $tUniqueName);
            $thumbnailPath = '/images/reels/' . $tUniqueName;
        } elseif ($request->filled('thumbnail_media_id') && is_numeric($request->input('thumbnail_media_id'))) {
            $chosen = Media::find($request->input('thumbnail_media_id'));
            if ($chosen) {
                $thumbnailPath = $chosen->file_path;
            }
        } elseif ($request->filled('thumbnail_url')) {
            $thumbnailPath = $request->input('thumbnail_url');
        }

        // 3. Handle Mobile Thumbnail
        $mobileThumbPath = null;
        if ($request->hasFile('mobile_thumbnail_file')) {
            $mFile = $request->file('mobile_thumbnail_file');
            $mExt = strtolower($mFile->getClientOriginalExtension() ?: 'jpg');
            $mUniqueName = 'mobile_cover_' . $sortOrder . '_' . time() . '_' . Str::random(4) . '.' . $mExt;
            $mFile->move($thumbDir, $mUniqueName);
            $mobileThumbPath = 'images/reels/' . $mUniqueName;
        }

        $slotName = 'reel_' . $sortOrder . '_' . time();

        $media = Media::create([
            'file_name' => $originalFileName,
            'file_path' => $thumbnailPath,
            'mobile_image_path' => $mobileThumbPath,
            'video_url' => $videoUrl,
            'disk' => 'local',
            'mime_type' => $mimeType,
            'file_type' => 'video',
            'content_type' => $contentType,
            'category_name' => $categoryName,
            'file_size' => $fileSize,
            'duration' => $duration,
            'audio_name' => $audioName,
            'likes_count' => $likes,
            'comments_count' => $comments,
            'views_count' => $views,
            'alt_text' => $title,
            'page' => 'homepage',
            'section' => 'blog_reels',
            'slot' => $slotName,
            'device' => 'all',
            'title' => $title,
            'subtitle' => $tagline,
            'description' => $description,
            'tag_text' => $tagline,
            'sort_order' => $sortOrder,
            'is_active' => $isActive,
            'is_featured' => $isFeatured,
            'uploaded_by' => $this->getAdminId(),
            'uploader_type' => 'admin',
        ]);

        $reelData = [
            'id' => $media->id,
            'title' => $media->title,
            'tagline' => $media->tag_text ?: $media->subtitle,
            'subtitle' => $media->subtitle,
            'description' => $media->description,
            'content_type' => $media->content_type ?: 'reel',
            'category' => $media->category_name ?: 'Studio ASMR',
            'category_name' => $media->category_name ?: 'Studio ASMR',
            'thumbnail' => $media->desktop_image_url ?: $media->url,
            'thumbnail_url' => $media->desktop_image_url ?: $media->url,
            'thumbnail_path' => $media->file_path,
            'mobile_thumbnail' => $media->mobile_image_url ?: ($media->desktop_image_url ?: $media->url),
            'mobile_thumbnail_path' => $media->mobile_image_path,
            'video_url' => $media->video_stream_url ?: $media->video_url,
            'video_path' => $media->video_url,
            'duration' => $media->duration ?: '00:48',
            'audio_name' => $media->audio_name ?: 'Original Sound',
            'likes' => (int)$media->likes_count,
            'likes_count' => (int)$media->likes_count,
            'comments' => (int)$media->comments_count,
            'comments_count' => (int)$media->comments_count,
            'views' => (int)$media->views_count,
            'views_count' => (int)$media->views_count,
            'is_featured' => (bool)$media->is_featured,
            'status' => (bool)$media->is_active,
            'is_active' => (bool)$media->is_active,
            'sort_order' => (int)$media->sort_order,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Video / Reel content created successfully!',
            'reel' => $reelData,
            'data' => $reelData
        ]);
    }

    /**
     * Get single Video / Reel for editing
     */
    public function getVideoReel($id): JsonResponse
    {
        $media = Media::where('page', 'homepage')
            ->where('section', 'blog_reels')
            ->where('id', $id)
            ->first();

        if (!$media) {
            $media = Media::find($id);
        }

        if (!$media) {
            return response()->json(['success' => false, 'message' => 'Video/Reel not found.'], 404);
        }

        $reelData = [
            'id' => $media->id,
            'title' => $media->title,
            'tagline' => $media->tag_text ?: $media->subtitle,
            'subtitle' => $media->subtitle,
            'description' => $media->description,
            'content_type' => $media->content_type ?: 'reel',
            'category' => $media->category_name ?: 'Studio ASMR',
            'category_name' => $media->category_name ?: 'Studio ASMR',
            'thumbnail' => $media->desktop_image_url ?: $media->url,
            'thumbnail_url' => $media->desktop_image_url ?: $media->url,
            'thumbnail_path' => $media->file_path,
            'mobile_thumbnail' => $media->mobile_image_url ?: ($media->desktop_image_url ?: $media->url),
            'mobile_thumbnail_path' => $media->mobile_image_path,
            'video_url' => $media->video_stream_url ?: $media->video_url,
            'video_path' => $media->video_url,
            'duration' => $media->duration ?: '00:48',
            'audio_name' => $media->audio_name ?: 'Original Audio',
            'likes' => (int)$media->likes_count,
            'likes_count' => (int)$media->likes_count,
            'comments' => (int)$media->comments_count,
            'comments_count' => (int)$media->comments_count,
            'views' => (int)$media->views_count,
            'views_count' => (int)$media->views_count,
            'is_featured' => (bool)$media->is_featured,
            'status' => (bool)$media->is_active,
            'is_active' => (bool)$media->is_active,
            'sort_order' => (int)$media->sort_order,
        ];

        return response()->json([
            'success' => true,
            'reel' => $reelData,
            'data' => $reelData
        ]);
    }

    /**
     * Update an existing Video / Reel
     */
    public function updateVideoReel(Request $request, $id): JsonResponse
    {
        $media = Media::where('page', 'homepage')
            ->where('section', 'blog_reels')
            ->where('id', $id)
            ->first();

        if (!$media) {
            $media = Media::find($id);
        }

        if (!$media) {
            return response()->json(['success' => false, 'message' => 'Video/Reel not found.'], 404);
        }

        // Pre-check for PHP file upload size error before standard validation
        if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] !== UPLOAD_ERR_OK && $_FILES['video_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $err = $_FILES['video_file']['error'];
            if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
                if ($request->filled('video_url')) {
                    unset($_FILES['video_file']);
                    $request->request->remove('video_file');
                } else {
                    $maxPhp = ini_get('upload_max_filesize') ?: '2M';
                    return response()->json([
                        'success' => false,
                        'message' => "The uploaded video exceeds server upload_max_filesize limit ({$maxPhp}). Please enter a direct video URL or upload a smaller file.",
                        'errors' => [
                            'video_file' => ["The uploaded video exceeds server limit ({$maxPhp}). Please enter a direct video URL or upload a smaller file."]
                        ]
                    ], 422);
                }
            }
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'content_type' => 'nullable|string|in:video,reel,blog_video',
            'category' => 'nullable|string|max:150',
            'category_name' => 'nullable|string|max:150',
            'tagline' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'nullable|string|max:50',
            'audio_name' => 'nullable|string|max:255',
            'likes' => 'nullable|integer|min:0',
            'comments' => 'nullable|integer|min:0',
            'views' => 'nullable|integer|min:0',
            'is_featured' => 'nullable',
            'status' => 'nullable',
            'is_active' => 'nullable',
            'sort_order' => 'nullable|integer|min:1',
            'video_file' => 'nullable|file|mimes:mp4,webm,mov,ogg,mkv,avi,qt,m4v,3gp,flv|max:204800',
            'video_url' => 'nullable|string|max:1000',
            'thumbnail_file' => 'nullable|file|image|max:20480',
            'thumbnail_media_id' => 'nullable',
            'thumbnail_url' => 'nullable|string|max:1000',
            'mobile_thumbnail_file' => 'nullable|file|image|max:20480',
        ]);

        // 1. Video Replacement
        if ($request->hasFile('video_file')) {
            $vFile = $request->file('video_file');
            if ($vFile->isValid()) {
                $vExt = strtolower($vFile->getClientOriginalExtension() ?: 'mp4');
                $vUniqueName = 'reel_' . ($media->sort_order ?: $media->id) . '_' . time() . '_' . Str::random(4) . '.' . $vExt;
                
                $targetDir = public_path('videos/reels');
                if (!File::isDirectory($targetDir)) {
                    File::makeDirectory($targetDir, 0777, true, true);
                }

                // Remove previous custom video if local
                $oldPath = ltrim($media->video_url ?? '', '/');
                if ($oldPath && str_starts_with($oldPath, 'videos/reels/') && file_exists(public_path($oldPath))) {
                    @unlink(public_path($oldPath));
                }

                $vFile->move($targetDir, $vUniqueName);
                @chmod($targetDir . DIRECTORY_SEPARATOR . $vUniqueName, 0664);

                $media->video_url = '/videos/reels/' . $vUniqueName;
                $media->mime_type = $vFile->getClientMimeType() ?: ('video/' . ($vExt === 'mov' ? 'quicktime' : $vExt));
                $media->file_size = file_exists($targetDir . '/' . $vUniqueName) ? filesize($targetDir . '/' . $vUniqueName) : 0;
                $media->file_name = $vFile->getClientOriginalName();
            }
        } elseif ($request->filled('video_url')) {
            $media->video_url = $request->input('video_url');
        }

        // 2. Thumbnail Replacement
        $thumbDir = public_path('images/reels');
        if (!File::isDirectory($thumbDir)) {
            File::makeDirectory($thumbDir, 0755, true, true);
        }

        if ($request->hasFile('thumbnail_file')) {
            $tFile = $request->file('thumbnail_file');
            $tExt = strtolower($tFile->getClientOriginalExtension() ?: 'jpg');
            $tUniqueName = 'cover_' . ($media->sort_order ?: $media->id) . '_' . time() . '_' . Str::random(4) . '.' . $tExt;
            
            $oldThumb = ltrim($media->file_path ?? '', '/');
            if ($oldThumb && str_starts_with($oldThumb, 'images/reels/') && file_exists(public_path($oldThumb))) {
                @unlink(public_path($oldThumb));
            }

            $tFile->move($thumbDir, $tUniqueName);
            $media->file_path = '/images/reels/' . $tUniqueName;
        } elseif ($request->filled('thumbnail_media_id') && is_numeric($request->input('thumbnail_media_id'))) {
            $chosen = Media::find($request->input('thumbnail_media_id'));
            if ($chosen) {
                $media->file_path = $chosen->file_path;
            }
        } elseif ($request->filled('thumbnail_url')) {
            $media->file_path = $request->input('thumbnail_url');
        }

        // 3. Mobile Thumbnail Replacement
        if ($request->hasFile('mobile_thumbnail_file')) {
            $mFile = $request->file('mobile_thumbnail_file');
            $mExt = strtolower($mFile->getClientOriginalExtension() ?: 'jpg');
            $mUniqueName = 'mobile_cover_' . ($media->sort_order ?: $media->id) . '_' . time() . '_' . Str::random(4) . '.' . $mExt;

            if ($media->mobile_image_path && str_starts_with($media->mobile_image_path, 'images/reels/') && file_exists(public_path($media->mobile_image_path))) {
                @unlink(public_path($media->mobile_image_path));
            }

            $mFile->move($thumbDir, $mUniqueName);
            $media->mobile_image_path = 'images/reels/' . $mUniqueName;
        }

        // Content Updates
        if ($request->has('title')) $media->title = $request->input('title');
        if ($request->has('content_type')) $media->content_type = $request->input('content_type');
        if ($request->has('category_name')) $media->category_name = $request->input('category_name');
        elseif ($request->has('category')) $media->category_name = $request->input('category');

        if ($request->has('tagline')) {
            $media->tag_text = $request->input('tagline');
            $media->subtitle = $request->input('tagline');
        } elseif ($request->has('subtitle')) {
            $media->subtitle = $request->input('subtitle');
            $media->tag_text = $request->input('subtitle');
        }

        if ($request->has('description')) $media->description = $request->input('description');
        if ($request->has('duration')) $media->duration = $request->input('duration');
        if ($request->has('audio_name')) $media->audio_name = $request->input('audio_name');
        if ($request->has('likes')) $media->likes_count = (int)$request->input('likes');
        if ($request->has('comments')) $media->comments_count = (int)$request->input('comments');
        if ($request->has('views')) $media->views_count = (int)$request->input('views');
        
        if ($request->has('is_featured')) {
            $media->is_featured = filter_var($request->input('is_featured'), FILTER_VALIDATE_BOOLEAN);
        }

        if ($request->has('status')) {
            $media->is_active = filter_var($request->input('status'), FILTER_VALIDATE_BOOLEAN);
        } elseif ($request->has('is_active')) {
            $media->is_active = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
        }

        if ($request->has('sort_order') && (int)$request->input('sort_order') > 0) {
            $media->sort_order = (int)$request->input('sort_order');
        }

        $media->save();

        $reelData = [
            'id' => $media->id,
            'title' => $media->title,
            'tagline' => $media->tag_text ?: $media->subtitle,
            'subtitle' => $media->subtitle,
            'description' => $media->description,
            'content_type' => $media->content_type ?: 'reel',
            'category' => $media->category_name ?: 'Studio ASMR',
            'category_name' => $media->category_name ?: 'Studio ASMR',
            'thumbnail' => $media->desktop_image_url ?: $media->url,
            'thumbnail_url' => $media->desktop_image_url ?: $media->url,
            'thumbnail_path' => $media->file_path,
            'mobile_thumbnail' => $media->mobile_image_url ?: ($media->desktop_image_url ?: $media->url),
            'mobile_thumbnail_path' => $media->mobile_image_path,
            'video_url' => $media->video_stream_url ?: $media->video_url,
            'video_path' => $media->video_url,
            'duration' => $media->duration ?: '00:48',
            'audio_name' => $media->audio_name ?: 'Original Audio',
            'likes' => (int)$media->likes_count,
            'likes_count' => (int)$media->likes_count,
            'comments' => (int)$media->comments_count,
            'comments_count' => (int)$media->comments_count,
            'views' => (int)$media->views_count,
            'views_count' => (int)$media->views_count,
            'is_featured' => (bool)$media->is_featured,
            'status' => (bool)$media->is_active,
            'is_active' => (bool)$media->is_active,
            'sort_order' => (int)$media->sort_order,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Video / Reel updated successfully!',
            'reel' => $reelData,
            'data' => $reelData
        ]);
    }

    /**
     * Delete a Video / Reel
     */
    public function deleteVideoReel($id): JsonResponse
    {
        $media = Media::where('page', 'homepage')
            ->where('section', 'blog_reels')
            ->where('id', $id)
            ->first();

        if (!$media) {
            $media = Media::find($id);
        }

        if (!$media) {
            return response()->json([
                'success' => false,
                'message' => 'Video/Reel not found.'
            ], 404);
        }

        // Delete physical video if stored in dedicated custom upload folder
        if ($media->video_url && str_starts_with($media->video_url, 'videos/reels/') && file_exists(public_path($media->video_url))) {
            $otherUsing = Media::where('video_url', $media->video_url)->where('id', '!=', $media->id)->exists();
            if (!$otherUsing) {
                @unlink(public_path($media->video_url));
            }
        }

        // Delete physical thumbnail if custom uploaded
        if ($media->file_path && str_starts_with($media->file_path, 'images/reels/') && file_exists(public_path($media->file_path))) {
            $otherUsingImg = Media::where('file_path', $media->file_path)->where('id', '!=', $media->id)->exists();
            if (!$otherUsingImg) {
                @unlink(public_path($media->file_path));
            }
        }

        $media->delete();

        return response()->json([
            'success' => true,
            'message' => 'Video / Reel deleted successfully!'
        ]);
    }

    /**
     * Toggle Video / Reel Active Status
     */
    public function toggleVideoReelStatus($id): JsonResponse
    {
        $media = Media::findOrFail($id);
        $media->is_active = !$media->is_active;
        $media->save();

        return response()->json([
            'success' => true,
            'message' => 'Video / Reel status changed to ' . ($media->is_active ? 'Active' : 'Inactive'),
            'is_active' => (bool)$media->is_active
        ]);
    }

    /**
     * Update Section Header Settings for Blog / Videos & Reels
     */
    public function updateBlogReelsSettings(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'tag_text' => 'nullable|string|max:150',
            'cta_text' => 'nullable|string|max:100',
            'cta_link' => 'nullable|string|max:255',
            'is_active' => 'nullable',
        ]);

        $settings = Media::where('page', 'homepage')
            ->where('section', 'blog_reels')
            ->where('slot', 'section_settings')
            ->first();

        if (!$settings) {
            $settings = new Media();
            $settings->page = 'homepage';
            $settings->section = 'blog_reels';
            $settings->slot = 'section_settings';
            $settings->file_name = 'blog_reels_settings.png';
            $settings->file_path = 'images/logo/Logo_1.png';
            $settings->disk = 'local';
            $settings->mime_type = 'image/png';
            $settings->file_type = 'image';
            $settings->uploaded_by = $this->getAdminId();
            $settings->uploader_type = 'admin';
        }

        $settings->title = $request->input('title', 'Behind the Stitches');
        $settings->subtitle = $request->input('subtitle', 'Watch our artisans hand-craft each creation, styling guides, and cozy studio ASMR unboxings.');
        $settings->description = $request->input('subtitle');
        $settings->tag_text = $request->input('tag_text', 'Studio Journal & Video Reels');
        $settings->cta_text = $request->input('cta_text', 'Follow @knotelleindia');
        $settings->cta_link = $request->input('cta_link', 'https://instagram.com/knotelleindia');
        $settings->is_active = $request->has('is_active') ? filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN) : true;
        $settings->save();

        return response()->json([
            'success' => true,
            'message' => 'Behind the Stitches section settings updated successfully!',
            'data' => [
                'title' => $settings->title,
                'subtitle' => $settings->subtitle,
                'tag_text' => $settings->tag_text,
                'cta_text' => $settings->cta_text,
                'cta_link' => $settings->cta_link,
                'is_active' => (bool)$settings->is_active,
            ]
        ]);
    }

    /**
     * Get Custom Crochet Banner Data for Admin Manager
     */
    public function getCustomCrochet(): JsonResponse
    {
        $m = Media::where('page', 'homepage')
            ->where('section', 'custom_crochet')
            ->orderBy('updated_at', 'desc')
            ->first();

        $meta = $m && $m->metadata ? $m->metadata : [];

        $data = [
            'id' => $m ? $m->id : null,
            'title' => $m && $m->title ? $m->title : 'Custom Crochet',
            'subtitle' => $m && $m->subtitle ? $m->subtitle : 'Just for You',
            'description' => $m && $m->description ? $m->description : "Your imagination, our yarn. Let's create something special together.",
            'cta_text' => $m && $m->cta_text ? $m->cta_text : 'Request Your Custom Order',
            'cta_link' => $m && $m->cta_link ? $m->cta_link : '/custom-order',
            'tag_text' => $m && $m->tag_text ? $m->tag_text : 'Turn Your Ideas Into Handmade Reality',
            'tag_active' => isset($meta['tag_active']) ? (bool)$meta['tag_active'] : true,
            'image_url' => $m ? ($m->desktop_image_url ?: $m->url) : asset('images/homepage/middleimg.png'),
            'desktop_image' => $m ? ($m->desktop_image_url ?: $m->url) : asset('images/homepage/middleimg.png'),
            'mobile_image' => $m ? ($m->mobile_image_url ?: '') : '',
            'alt_text' => $m && $m->alt_text ? $m->alt_text : 'Custom Crochet Banner',
            'is_active' => $m ? (bool)$m->is_active : true,
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Save / Update Custom Crochet Banner & Hanging Tag
     */
    public function saveCustomCrochet(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'required|string',
            'cta_text' => 'nullable|string|max:100',
            'cta_link' => 'nullable|string|max:255',
            'tag_text' => 'nullable|string|max:255',
            'tag_active' => 'nullable',
            'alt_text' => 'nullable|string|max:255',
            'is_active' => 'nullable',
            'image_file' => 'nullable|image|max:15360',
            'image_url' => 'nullable|string',
        ]);

        $media = Media::where('page', 'homepage')
            ->where('section', 'custom_crochet')
            ->first();

        if (!$media) {
            $media = new Media();
            $media->page = 'homepage';
            $media->section = 'custom_crochet';
            $media->slot = 'custom_crochet_visual';
            $media->file_name = 'middleimg.png';
            $media->file_path = 'images/homepage/middleimg.png';
            $media->disk = 'local';
            $media->mime_type = 'image/png';
            $media->file_type = 'image';
            $media->uploaded_by = $this->getAdminId();
            $media->uploader_type = 'admin';
        }

        // Handle uploaded image file
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
            $fileName = 'custom_crochet_' . time() . '_' . Str::random(4) . '.' . $ext;
            $targetDir = public_path('images/homepage');
            if (!File::isDirectory($targetDir)) {
                File::makeDirectory($targetDir, 0755, true, true);
            }
            $file->move($targetDir, $fileName);
            $media->file_name = $file->getClientOriginalName();
            $media->file_path = 'images/homepage/' . $fileName;
            $media->mime_type = 'image/' . ($ext === 'png' ? 'png' : ($ext === 'webp' ? 'webp' : 'jpeg'));
            $media->file_size = filesize($targetDir . DIRECTORY_SEPARATOR . $fileName);
        } elseif ($request->filled('image_url')) {
            $media->file_path = $request->input('image_url');
        }

        $media->slot = 'custom_crochet_visual';
        $media->title = $request->input('title');
        $media->subtitle = $request->input('subtitle');
        $media->description = $request->input('description');
        $media->cta_text = $request->input('cta_text');
        $media->cta_link = $request->input('cta_link');
        $media->tag_text = $request->input('tag_text', 'Turn Your Ideas Into Handmade Reality');
        $media->alt_text = $request->input('alt_text', 'Custom Crochet Banner');
        $media->is_active = $request->has('is_active') ? filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN) : true;

        $meta = $media->metadata ?: [];
        $meta['tag_active'] = $request->has('tag_active') ? filter_var($request->input('tag_active'), FILTER_VALIDATE_BOOLEAN) : true;
        $media->metadata = $meta;

        $media->save();

        // Ensure other duplicate non-reel rows in section 'custom_crochet' mirror the active state or get cleaned up
        Media::where('page', 'homepage')
            ->where('section', 'custom_crochet')
            ->where('id', '!=', $media->id)
            ->where('content_type', '!=', 'reel')
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Custom Crochet banner and hanging tag saved successfully!',
            'data' => $media,
        ]);
    }

    /**
     * Get Brand Story Section Data for Admin Media Manager
     */
    public function getBrandStory(): JsonResponse
    {
        $m = Media::where('page', 'homepage')
            ->where('section', 'brand_story')
            ->orderBy('updated_at', 'desc')
            ->first();

        $meta = $m && $m->metadata ? $m->metadata : [];
        $defaultFeatures = [
            ['title' => 'Handmade with Love', 'icon' => 'heart'],
            ['title' => 'Premium Yarn Quality', 'icon' => 'sparkles'],
            ['title' => '100% Pure Natural Cotton', 'icon' => 'leaf'],
            ['title' => 'Happiness Guaranteed', 'icon' => 'smile'],
        ];

        $data = [
            'id' => $m ? $m->id : null,
            'page' => 'homepage',
            'section' => 'brand_story',
            'slot' => 'brand_story_desktop',
            'badge' => $m && $m->tag_text ? $m->tag_text : 'KNOTELLE Artisanal Crochet Craftsmanship',
            'title' => $m && $m->title ? $m->title : 'Every Stitch',
            'subtitle' => $m && $m->subtitle ? $m->subtitle : 'Has a Story',
            'description' => $m && $m->description ? $m->description : 'More than just crochet, we create memories, happiness and a little bit of magic.',
            'cta_text' => $m && $m->cta_text ? $m->cta_text : 'Read Our Story',
            'cta_link' => $m && $m->cta_link ? $m->cta_link : '/about',
            'features' => !empty($meta['features']) ? $meta['features'] : $defaultFeatures,
            'image_url' => $m ? ($m->desktop_image_url ?: $m->url) : asset('images/homepage/middleimg.png'),
            'desktop_image' => $m ? ($m->desktop_image_url ?: $m->url) : asset('images/homepage/middleimg.png'),
            'is_active' => $m ? (bool)$m->is_active : true,
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Save / Update Brand Story Section Content, Features, and Background
     */
    public function saveBrandStory(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'badge' => 'nullable|string|max:255',
            'tag_text' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'cta_text' => 'nullable|string|max:100',
            'cta_link' => 'nullable|string|max:255',
            'is_active' => 'nullable',
            'image_file' => 'nullable|image|max:15360',
            'image_url' => 'nullable|string',
        ]);

        $media = Media::where('page', 'homepage')
            ->where('section', 'brand_story')
            ->first();

        if (!$media) {
            $media = new Media();
            $media->page = 'homepage';
            $media->section = 'brand_story';
            $media->slot = 'brand_story_desktop';
            $media->file_name = 'middleimg.png';
            $media->file_path = 'images/homepage/middleimg.png';
            $media->disk = 'local';
            $media->mime_type = 'image/png';
            $media->file_type = 'image';
            $media->uploaded_by = $this->getAdminId();
            $media->uploader_type = 'admin';
        }

        // Handle uploaded background image file
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
            $fileName = 'brand_story_' . time() . '_' . Str::random(4) . '.' . $ext;
            $targetDir = public_path('images/homepage');
            if (!File::isDirectory($targetDir)) {
                File::makeDirectory($targetDir, 0755, true, true);
            }
            $file->move($targetDir, $fileName);
            $media->file_name = $file->getClientOriginalName();
            $media->file_path = 'images/homepage/' . $fileName;
            $media->mime_type = 'image/' . ($ext === 'png' ? 'png' : ($ext === 'webp' ? 'webp' : 'jpeg'));
            $media->file_size = filesize($targetDir . DIRECTORY_SEPARATOR . $fileName);
        } elseif ($request->filled('image_url')) {
            $media->file_path = $request->input('image_url');
        }

        $media->slot = 'brand_story_desktop';
        $media->title = $request->input('title', 'Every Stitch');
        $media->subtitle = $request->input('subtitle', 'Has a Story');
        $media->tag_text = $request->input('badge', $request->input('tag_text', 'KNOTELLE Artisanal Crochet Craftsmanship'));
        $media->description = $request->input('description', 'More than just crochet, we create memories, happiness and a little bit of magic.');
        $media->cta_text = $request->input('cta_text', 'Read Our Story');
        $media->cta_link = $request->input('cta_link', '/about');
        $media->is_active = $request->has('is_active') ? filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN) : true;

        $meta = $media->metadata ?: [];

        // Save feature titles
        if ($request->has('feature_1_title') || $request->has('features')) {
            $f1 = $request->input('feature_1_title', 'Handmade with Love');
            $f2 = $request->input('feature_2_title', 'Premium Yarn Quality');
            $f3 = $request->input('feature_3_title', '100% Pure Natural Cotton');
            $f4 = $request->input('feature_4_title', 'Happiness Guaranteed');
            $meta['features'] = [
                ['title' => $f1, 'icon' => 'heart'],
                ['title' => $f2, 'icon' => 'sparkles'],
                ['title' => $f3, 'icon' => 'leaf'],
                ['title' => $f4, 'icon' => 'smile'],
            ];
        }

        $media->metadata = $meta;
        $media->save();

        return response()->json([
            'success' => true,
            'message' => 'Brand Story section updated successfully!',
            'data' => $media,
        ]);
    }

    /**
     * Save / Update About Story Content & Visuals
     */
    public function saveAboutStory(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'tag_text' => 'nullable|string|max:150',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'required|string',
            'paragraph_2' => 'nullable|string',
            'floating_badge_title' => 'nullable|string|max:150',
            'floating_badge_subtitle' => 'nullable|string|max:255',
            'floating_badge_icon' => 'nullable|string|max:100',
            'floating_badge_active' => 'nullable',
            'cta_text' => 'nullable|string|max:100',
            'cta_link' => 'nullable|string|max:255',
            'cta_visible' => 'nullable',
            'alt_text' => 'nullable|string|max:255',
            'is_active' => 'nullable',
            'image' => 'nullable|image|max:15360',
            'mobile_image' => 'nullable|image|max:15360',
            'desktop_image_url' => 'nullable|string',
            'mobile_image_url' => 'nullable|string',
        ]);

        $media = Media::where('page', 'about')
            ->where('section', 'about_story')
            ->first();

        if (!$media) {
            $media = new Media();
            $media->page = 'about';
            $media->section = 'about_story';
            $media->slot = 'story_hero';
            $media->file_name = 'about_story_hero.jpg';
            $media->file_path = 'https://images.unsplash.com/photo-1544816155-12df9643f363?q=80&w=1000&auto=format&fit=crop';
            $media->disk = 'local';
            $media->mime_type = 'image/jpeg';
            $media->file_type = 'image';
            $media->uploaded_by = $this->getAdminId();
            $media->uploader_type = 'admin';
        }

        // Handle desktop image upload
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $fileName = 'about_story_' . time() . '_' . Str::random(4) . '.' . $ext;
            $targetDir = public_path('images/about');
            if (!File::isDirectory($targetDir)) {
                File::makeDirectory($targetDir, 0755, true, true);
            }
            $file->move($targetDir, $fileName);
            $media->file_name = $file->getClientOriginalName();
            $media->file_path = 'images/about/' . $fileName;
            $media->mime_type = 'image/' . ($ext === 'png' ? 'png' : ($ext === 'webp' ? 'webp' : 'jpeg'));
            $media->file_size = filesize($targetDir . DIRECTORY_SEPARATOR . $fileName);
        } elseif ($request->filled('desktop_image_url')) {
            $media->file_path = $request->input('desktop_image_url');
        }

        // Handle mobile image upload
        if ($request->hasFile('mobile_image')) {
            $mFile = $request->file('mobile_image');
            $mExt = strtolower($mFile->getClientOriginalExtension() ?: 'jpg');
            $mFileName = 'about_story_mobile_' . time() . '_' . Str::random(4) . '.' . $mExt;
            $targetDir = public_path('images/about');
            if (!File::isDirectory($targetDir)) {
                File::makeDirectory($targetDir, 0755, true, true);
            }
            $mFile->move($targetDir, $mFileName);
            $media->mobile_image_path = 'images/about/' . $mFileName;
        } elseif ($request->filled('mobile_image_url')) {
            $media->mobile_image_path = $request->input('mobile_image_url');
        }

        $meta = $media->metadata ?: [];
        $meta['paragraph_2'] = $request->input('paragraph_2', '');
        $meta['floating_badge_title'] = $request->input('floating_badge_title', '100% Handcrafted');
        $meta['floating_badge_subtitle'] = $request->input('floating_badge_subtitle', 'Never mass machine produced');
        $meta['floating_badge_icon'] = $request->input('floating_badge_icon', 'Heart');
        $meta['floating_badge_active'] = $request->has('floating_badge_active') ? filter_var($request->input('floating_badge_active'), FILTER_VALIDATE_BOOLEAN) : true;
        $meta['cta_visible'] = $request->has('cta_visible') ? filter_var($request->input('cta_visible'), FILTER_VALIDATE_BOOLEAN) : true;

        $media->title = $request->input('title', 'Every Loop Tells a Story');
        $media->tag_text = $request->input('tag_text', 'The KNOTELLE Story');
        $media->subtitle = $request->input('subtitle', '');
        $media->description = $request->input('description');
        $media->cta_text = $request->input('cta_text', 'Request a Custom Creation');
        $media->cta_link = $request->input('cta_link', '/custom-order');
        $media->alt_text = $request->input('alt_text', 'Artisan stitching crochet with wooden hook');
        $media->is_active = $request->has('is_active') ? filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN) : true;
        $media->metadata = $meta;
        $media->save();

        return response()->json([
            'success' => true,
            'message' => 'About Story updated successfully!',
            'data' => [
                'id' => $media->id,
                'title' => $media->title,
                'tag_text' => $media->tag_text,
                'subtitle' => $media->subtitle,
                'description' => $media->description,
                'paragraph_2' => $meta['paragraph_2'],
                'desktop_image' => $media->desktop_image_url ?: $media->url,
                'mobile_image' => $media->mobile_image_url ?: '',
                'alt_text' => $media->alt_text,
                'floating_badge_title' => $meta['floating_badge_title'],
                'floating_badge_subtitle' => $meta['floating_badge_subtitle'],
                'floating_badge_icon' => $meta['floating_badge_icon'],
                'floating_badge_active' => $meta['floating_badge_active'],
                'cta_text' => $media->cta_text,
                'cta_link' => $media->cta_link,
                'cta_visible' => $meta['cta_visible'],
                'is_active' => (bool)$media->is_active,
            ]
        ]);
    }

    /**
     * Get About Story Data
     */
    public function getAboutStory(): JsonResponse
    {
        $media = Media::where('page', 'about')
            ->where('section', 'about_story')
            ->first();

        $meta = $media && $media->metadata ? $media->metadata : [];

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $media ? $media->id : null,
                'title' => $media && $media->title ? $media->title : 'Every Loop Tells a Story',
                'tag_text' => $media && $media->tag_text ? $media->tag_text : 'The KNOTELLE Story',
                'subtitle' => $media && $media->subtitle ? $media->subtitle : '',
                'description' => $media && $media->description ? $media->description : 'In a world flooded with disposable factory goods, KNOTELLE was born from a yearning for slow-made warmth. What began as a tiny home workshop in Bengaluru has grown into a vibrant collective of women artisans who share a deep love for yarn, color harmony, and delicate floral silhouettes.',
                'paragraph_2' => $meta['paragraph_2'] ?? 'When you order a bouquet of crochet roses, a customized bunny keychain, or a granny square tote, you are not simply purchasing an object. You are welcoming hours of human patience, intention, and joy into your home.',
                'desktop_image' => $media ? ($media->desktop_image_url ?: $media->url) : 'https://images.unsplash.com/photo-1544816155-12df9643f363?q=80&w=1000&auto=format&fit=crop',
                'mobile_image' => $media ? ($media->mobile_image_url ?: '') : '',
                'alt_text' => $media && $media->alt_text ? $media->alt_text : 'Artisan stitching crochet with wooden hook',
                'floating_badge_title' => $meta['floating_badge_title'] ?? '100% Handcrafted',
                'floating_badge_subtitle' => $meta['floating_badge_subtitle'] ?? 'Never mass machine produced',
                'floating_badge_icon' => $meta['floating_badge_icon'] ?? 'Heart',
                'floating_badge_active' => isset($meta['floating_badge_active']) ? (bool)$meta['floating_badge_active'] : true,
                'cta_text' => $media && $media->cta_text ? $media->cta_text : 'Request a Custom Creation',
                'cta_link' => $media && $media->cta_link ? $media->cta_link : '/custom-order',
                'cta_visible' => isset($meta['cta_visible']) ? (bool)$meta['cta_visible'] : true,
                'is_active' => $media ? (bool)$media->is_active : true,
            ]
        ]);
    }

    /**
     * Save Craft Pillars Section Header
     */
    public function saveCraftPillarsHeader(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'tag_text' => 'nullable|string|max:150',
            'is_active' => 'nullable',
        ]);

        $settings = Media::where('page', 'about')
            ->where('section', 'craft_pillars')
            ->where('slot', 'section_settings')
            ->first();

        if (!$settings) {
            $settings = new Media();
            $settings->page = 'about';
            $settings->section = 'craft_pillars';
            $settings->slot = 'section_settings';
            $settings->file_name = 'craft_pillars_settings.png';
            $settings->file_path = 'images/logo/Logo_1.png';
            $settings->disk = 'local';
            $settings->mime_type = 'image/png';
            $settings->file_type = 'image';
            $settings->uploaded_by = $this->getAdminId();
            $settings->uploader_type = 'admin';
        }

        $settings->title = $request->input('title', 'Our Craft Pillars');
        $settings->subtitle = $request->input('subtitle', 'Guiding principles behind every stitch we make.');
        $settings->description = $request->input('subtitle');
        $settings->tag_text = $request->input('tag_text', 'Artisan Standards');
        $settings->is_active = $request->has('is_active') ? filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN) : true;
        $settings->save();

        return response()->json([
            'success' => true,
            'message' => 'Craft Pillars section header updated successfully!',
            'data' => [
                'title' => $settings->title,
                'subtitle' => $settings->subtitle,
                'tag_text' => $settings->tag_text,
                'is_active' => (bool)$settings->is_active,
            ]
        ]);
    }

    /**
     * Add a new Craft Pillar item
     */
    public function addCraftPillar(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'icon_name' => 'nullable|string|max:100',
            'icon_type' => 'nullable|string|in:preset',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable',
        ]);

        $maxSort = Media::where('page', 'about')
            ->where('section', 'craft_pillars')
            ->where('slot', '!=', 'section_settings')
            ->max('sort_order') ?: 0;

        $iconName = $request->input('icon_name', 'Leaf');
        $filePath = 'icon_' . Str::slug($iconName);

        $pillar = Media::create([
            'page' => 'about',
            'section' => 'craft_pillars',
            'slot' => 'pillar_' . Str::random(8),
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'tag_text' => $iconName,
            'file_name' => 'pillar_icon.png',
            'file_path' => $filePath,
            'disk' => 'local',
            'mime_type' => 'image/png',
            'file_type' => 'image',
            'sort_order' => $request->filled('sort_order') ? (int)$request->input('sort_order') : ($maxSort + 1),
            'is_active' => $request->has('is_active') ? filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN) : true,
            'uploaded_by' => $this->getAdminId(),
            'uploader_type' => 'admin',
            'metadata' => [
                'icon_name' => $iconName,
                'icon_type' => 'preset',
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Craft Pillar added successfully!',
            'data' => [
                'id' => $pillar->id,
                'title' => $pillar->title,
                'description' => $pillar->description,
                'icon' => $iconName,
                'icon_name' => $iconName,
                'icon_type' => 'preset',
                'sort_order' => (int)$pillar->sort_order,
                'is_active' => (bool)$pillar->is_active,
            ]
        ]);
    }

    /**
     * Get a single Craft Pillar item
     */
    public function getCraftPillar($id): JsonResponse
    {
        $pillar = Media::where('page', 'about')
            ->where('section', 'craft_pillars')
            ->where('id', $id)
            ->firstOrFail();

        $meta = $pillar->metadata ?: [];
        $iconName = $pillar->tag_text ?: ($meta['icon_name'] ?? 'Leaf');

        $payload = [
            'id' => $pillar->id,
            'title' => $pillar->title,
            'description' => $pillar->description,
            'icon' => $iconName,
            'icon_name' => $iconName,
            'icon_type' => 'preset',
            'sort_order' => (int)$pillar->sort_order,
            'is_active' => (bool)$pillar->is_active,
        ];

        return response()->json([
            'success' => true,
            'data' => $payload,
            'pillar' => $payload,
        ]);
    }

    /**
     * Update a Craft Pillar item
     */
    public function updateCraftPillar(Request $request, $id): JsonResponse
    {
        $pillar = Media::where('page', 'about')
            ->where('section', 'craft_pillars')
            ->where('id', $id)
            ->firstOrFail();

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'icon_name' => 'nullable|string|max:100',
            'icon_type' => 'nullable|string|in:preset',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable',
        ]);

        $meta = $pillar->metadata ?: [];
        $iconName = $request->input('icon_name', $pillar->tag_text ?: ($meta['icon_name'] ?? 'Leaf'));

        $meta['icon_name'] = $iconName;
        $meta['icon_type'] = 'preset';

        $pillar->title = $request->input('title');
        $pillar->description = $request->input('description');
        $pillar->tag_text = $iconName;
        $pillar->metadata = $meta;
        if ($request->filled('sort_order')) {
            $pillar->sort_order = (int)$request->input('sort_order');
        }
        if ($request->has('is_active')) {
            $pillar->is_active = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
        }
        $pillar->save();

        return response()->json([
            'success' => true,
            'message' => 'Craft Pillar updated successfully!',
            'data' => [
                'id' => $pillar->id,
                'title' => $pillar->title,
                'description' => $pillar->description,
                'icon' => $iconName,
                'icon_name' => $iconName,
                'icon_type' => 'preset',
                'sort_order' => (int)$pillar->sort_order,
                'is_active' => (bool)$pillar->is_active,
            ]
        ]);
    }

    /**
     * Delete a Craft Pillar item
     */
    public function deleteCraftPillar($id): JsonResponse
    {
        $pillar = Media::where('page', 'about')
            ->where('section', 'craft_pillars')
            ->where('id', $id)
            ->firstOrFail();

        if (str_starts_with($pillar->file_path, 'images/about/pillars/') && file_exists(public_path($pillar->file_path))) {
            @unlink(public_path($pillar->file_path));
        }

        $pillar->delete();

        return response()->json([
            'success' => true,
            'message' => 'Craft Pillar deleted successfully!'
        ]);
    }

    /**
     * Toggle Craft Pillar status
     */
    public function toggleCraftPillar($id): JsonResponse
    {
        $pillar = Media::where('page', 'about')
            ->where('section', 'craft_pillars')
            ->where('id', $id)
            ->firstOrFail();

        $pillar->is_active = !$pillar->is_active;
        $pillar->save();

        return response()->json([
            'success' => true,
            'message' => 'Craft Pillar status changed to ' . ($pillar->is_active ? 'Active' : 'Inactive'),
            'is_active' => (bool)$pillar->is_active,
        ]);
    }

    /**
     * Reorder Craft Pillars
     */
    public function reorderCraftPillars(Request $request): JsonResponse
    {
        $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|integer',
            'orders.*.sort_order' => 'required|integer',
        ]);

        foreach ($request->input('orders') as $item) {
            Media::where('page', 'about')
                ->where('section', 'craft_pillars')
                ->where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Craft Pillars reordered successfully!'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CONTACT PAGE ADMIN MANAGEMENT HANDLERS
    |--------------------------------------------------------------------------
    */

    /**
     * Save Contact Intro / Hero settings and artwork
     */
    public function saveContactIntro(Request $request): JsonResponse
    {
        $rules = [
            'badge' => 'nullable|string|max:255',
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'alt_text' => 'nullable|string|max:255',
            'cta_text' => 'nullable|string|max:100',
            'cta_link' => 'nullable|string|max:255',
            'image_url' => 'nullable|string|max:1000',
            'is_active' => 'nullable',
        ];

        if ($request->hasFile('image_file')) {
            $rules['image_file'] = 'file|mimes:jpeg,png,jpg,webp,svg|max:10240';
        }

        $request->validate($rules);

        $media = Media::where('page', 'contact')
            ->where('section', 'contact_intro')
            ->where('slot', 'intro_banner')
            ->first();

        $filePath = $media ? $media->file_path : 'https://images.unsplash.com/photo-1544816155-12df9643f363?q=80&w=1200&auto=format&fit=crop';
        $fileName = $media ? $media->file_name : 'contact_hero.jpg';
        $mimeType = $media ? $media->mime_type : 'image/jpeg';
        $fileSize = $media ? $media->file_size : 0;

        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $uniqueName = 'contact_hero_' . time() . '_' . Str::random(4) . '.' . $ext;
            $targetDir = public_path('images/contact');
            if (!File::isDirectory($targetDir)) {
                File::makeDirectory($targetDir, 0755, true, true);
            }
            $file->move($targetDir, $uniqueName);
            $filePath = 'images/contact/' . $uniqueName;
            $fileName = $file->getClientOriginalName();
            $mimeType = 'image/' . ($ext === 'png' ? 'png' : ($ext === 'webp' ? 'webp' : 'jpeg'));
            $fileSize = filesize($targetDir . DIRECTORY_SEPARATOR . $uniqueName);
        } elseif ($request->filled('image_url')) {
            $filePath = $request->input('image_url');
            $fileName = basename($filePath);
        }

        $isActive = $request->has('is_active') ? filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN) : true;

        $intro = Media::updateOrCreate(
            [
                'page' => 'contact',
                'section' => 'contact_intro',
                'slot' => 'intro_banner',
            ],
            [
                'title' => $request->input('title'),
                'tag_text' => $request->input('badge') ?: "Let's Connect",
                'subtitle' => $request->input('subtitle'),
                'description' => $request->input('description'),
                'alt_text' => $request->input('alt_text') ?: $request->input('title'),
                'cta_text' => $request->input('cta_text') ?: 'Send Us a Message',
                'cta_link' => $request->input('cta_link') ?: '#contact-form',
                'file_name' => $fileName,
                'file_path' => $filePath,
                'disk' => 'local',
                'mime_type' => $mimeType,
                'file_type' => 'image',
                'file_size' => $fileSize,
                'is_active' => $isActive,
                'sort_order' => 1,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Contact Intro updated successfully!',
            'data' => [
                'id' => $intro->id,
                'badge' => $intro->tag_text,
                'title' => $intro->title,
                'subtitle' => $intro->subtitle,
                'description' => $intro->description,
                'image' => $intro->desktop_image_url ?: $intro->url,
                'alt_text' => $intro->alt_text,
                'cta_text' => $intro->cta_text,
                'cta_link' => $intro->cta_link,
                'is_active' => (bool)$intro->is_active,
            ]
        ]);
    }

    /**
     * Get Contact Intro data
     */
    public function getContactIntro(): JsonResponse
    {
        $intro = Media::where('page', 'contact')
            ->where('section', 'contact_intro')
            ->where('slot', 'intro_banner')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $intro ? $intro->id : null,
                'badge' => $intro && $intro->tag_text ? $intro->tag_text : "Let's Connect",
                'title' => $intro && $intro->title ? $intro->title : "Let's Connect",
                'subtitle' => $intro && $intro->subtitle ? $intro->subtitle : '',
                'description' => $intro && $intro->description ? $intro->description : '',
                'image' => $intro ? ($intro->desktop_image_url ?: $intro->url) : '',
                'alt_text' => $intro ? $intro->alt_text : '',
                'cta_text' => $intro && $intro->cta_text ? $intro->cta_text : 'Send Us a Message',
                'cta_link' => $intro && $intro->cta_link ? $intro->cta_link : '#contact-form',
                'is_active' => $intro ? (bool)$intro->is_active : true,
            ]
        ]);
    }

    /**
     * Save Contact Information Section Header & Custom Order Box
     */
    public function saveContactInfoHeader(Request $request): JsonResponse
    {
        $request->validate([
            'badge' => 'nullable|string|max:255',
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'custom_order_box_title' => 'nullable|string|max:255',
            'custom_order_box_text' => 'nullable|string|max:1000',
            'custom_order_box_link' => 'nullable|string|max:255',
            'custom_order_box_active' => 'nullable',
            'is_active' => 'nullable',
        ]);

        $settings = Media::where('page', 'contact')
            ->where('section', 'contact_info')
            ->where('slot', 'section_settings')
            ->first();

        $meta = $settings && $settings->metadata ? $settings->metadata : [];
        $meta['custom_order_box_title'] = $request->input('custom_order_box_title', 'Looking for Custom Orders?');
        $meta['custom_order_box_text'] = $request->input('custom_order_box_text', 'Have a specific design, color palette, or bouquet arrangement in mind? Request a bespoke piece directly.');
        $meta['custom_order_box_link'] = $request->input('custom_order_box_link', '/custom-order');
        if ($request->has('custom_order_box_active')) {
            $meta['custom_order_box_active'] = filter_var($request->input('custom_order_box_active'), FILTER_VALIDATE_BOOLEAN);
        }

        $isActive = $request->has('is_active') ? filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN) : true;

        Media::updateOrCreate(
            [
                'page' => 'contact',
                'section' => 'contact_info',
                'slot' => 'section_settings',
            ],
            [
                'file_name' => 'contact_info_settings.json',
                'file_path' => 'media/contact/contact_info_settings.json',
                'disk' => 'local',
                'mime_type' => 'application/json',
                'file_type' => 'document',
                'title' => $request->input('title'),
                'tag_text' => $request->input('badge') ?: 'Atelier Studio',
                'subtitle' => $request->input('subtitle'),
                'sort_order' => 0,
                'is_active' => $isActive,
                'metadata' => $meta,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Contact Information Header updated successfully!'
        ]);
    }

    /**
     * Get Contact Information Section Header & Custom Order Box
     */
    public function getContactInfoHeader(): JsonResponse
    {
        $settings = Media::where('page', 'contact')
            ->where('section', 'contact_info')
            ->where('slot', 'section_settings')
            ->first();

        $meta = $settings && $settings->metadata ? $settings->metadata : [];

        return response()->json([
            'success' => true,
            'data' => [
                'badge' => $settings && $settings->tag_text ? $settings->tag_text : 'Atelier Studio',
                'title' => $settings && $settings->title ? $settings->title : 'KNOTELLE Studio',
                'subtitle' => $settings && $settings->subtitle ? $settings->subtitle : 'Handmade with love in Bengaluru, India',
                'custom_order_box_title' => $meta['custom_order_box_title'] ?? 'Looking for Custom Orders?',
                'custom_order_box_text' => $meta['custom_order_box_text'] ?? 'Have a specific design, color palette, or bouquet arrangement in mind? Request a bespoke piece directly.',
                'custom_order_box_link' => $meta['custom_order_box_link'] ?? '/custom-order',
                'custom_order_box_active' => isset($meta['custom_order_box_active']) ? (bool)$meta['custom_order_box_active'] : true,
                'is_active' => $settings ? (bool)$settings->is_active : true,
            ]
        ]);
    }

    /**
     * Add a new Contact Detail item
     */
    public function addContactInfoItem(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'value' => 'required|string',
            'icon' => 'nullable|string|max:100',
            'address_line_2' => 'nullable|string|max:255',
            'link' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable',
        ]);

        $maxSort = Media::where('page', 'contact')
            ->where('section', 'contact_info')
            ->where('slot', '!=', 'section_settings')
            ->max('sort_order') ?: 0;

        $iconName = $request->input('icon') ?: 'MapPin';

        $item = Media::create([
            'page' => 'contact',
            'section' => 'contact_info',
            'slot' => 'info_' . Str::random(8),
            'title' => $request->input('title'),
            'description' => $request->input('value'),
            'tag_text' => $iconName,
            'cta_link' => $request->input('link'),
            'file_name' => 'info_item.json',
            'file_path' => 'icon_' . strtolower($iconName),
            'disk' => 'local',
            'mime_type' => 'application/json',
            'file_type' => 'document',
            'sort_order' => $request->filled('sort_order') ? (int)$request->input('sort_order') : ($maxSort + 1),
            'is_active' => $request->has('is_active') ? filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN) : true,
            'metadata' => [
                'icon_name' => $iconName,
                'address_line_2' => $request->input('address_line_2', ''),
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contact detail added successfully!',
            'data' => [
                'id' => $item->id,
                'title' => $item->title,
                'value' => $item->description,
                'icon' => $iconName,
                'address_line_2' => $request->input('address_line_2', ''),
                'link' => $item->cta_link,
                'sort_order' => (int)$item->sort_order,
                'is_active' => (bool)$item->is_active,
            ]
        ]);
    }

    /**
     * Get a single Contact Detail item
     */
    public function getContactInfoItem($id): JsonResponse
    {
        $item = Media::where('page', 'contact')
            ->where('section', 'contact_info')
            ->where('id', $id)
            ->firstOrFail();

        $meta = $item->metadata ?: [];

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $item->id,
                'title' => $item->title,
                'value' => $item->description,
                'icon' => $item->tag_text ?: ($meta['icon_name'] ?? 'MapPin'),
                'address_line_2' => $meta['address_line_2'] ?? '',
                'link' => $item->cta_link ?: '',
                'sort_order' => (int)$item->sort_order,
                'is_active' => (bool)$item->is_active,
            ]
        ]);
    }

    /**
     * Update a Contact Detail item
     */
    public function updateContactInfoItem(Request $request, $id): JsonResponse
    {
        $item = Media::where('page', 'contact')
            ->where('section', 'contact_info')
            ->where('id', $id)
            ->firstOrFail();

        $request->validate([
            'title' => 'required|string|max:255',
            'value' => 'required|string',
            'icon' => 'nullable|string|max:100',
            'address_line_2' => 'nullable|string|max:255',
            'link' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable',
        ]);

        $meta = $item->metadata ?: [];
        $iconName = $request->input('icon', $item->tag_text ?: 'MapPin');
        $meta['icon_name'] = $iconName;
        $meta['address_line_2'] = $request->input('address_line_2', $meta['address_line_2'] ?? '');

        $item->title = $request->input('title');
        $item->description = $request->input('value');
        $item->tag_text = $iconName;
        $item->cta_link = $request->input('link');
        $item->metadata = $meta;

        if ($request->filled('sort_order')) {
            $item->sort_order = (int)$request->input('sort_order');
        }
        if ($request->has('is_active')) {
            $item->is_active = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
        }
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Contact detail updated successfully!',
            'data' => [
                'id' => $item->id,
                'title' => $item->title,
                'value' => $item->description,
                'icon' => $iconName,
                'address_line_2' => $meta['address_line_2'],
                'link' => $item->cta_link,
                'sort_order' => (int)$item->sort_order,
                'is_active' => (bool)$item->is_active,
            ]
        ]);
    }

    /**
     * Delete a Contact Detail item
     */
    public function deleteContactInfoItem($id): JsonResponse
    {
        $item = Media::where('page', 'contact')
            ->where('section', 'contact_info')
            ->where('id', $id)
            ->firstOrFail();

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contact detail deleted successfully!'
        ]);
    }

    /**
     * Toggle Contact Detail item status
     */
    public function toggleContactInfoItem($id): JsonResponse
    {
        $item = Media::where('page', 'contact')
            ->where('section', 'contact_info')
            ->where('id', $id)
            ->firstOrFail();

        $item->is_active = !$item->is_active;
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Contact detail status changed to ' . ($item->is_active ? 'Active' : 'Inactive'),
            'is_active' => (bool)$item->is_active,
        ]);
    }

    /**
     * Reorder Contact Detail items
     */
    public function reorderContactInfoItems(Request $request): JsonResponse
    {
        $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|integer',
            'orders.*.sort_order' => 'required|integer',
        ]);

        foreach ($request->input('orders') as $item) {
            Media::where('page', 'contact')
                ->where('section', 'contact_info')
                ->where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Contact details reordered successfully!'
        ]);
    }

    /**
     * Save Send Us a Message (Contact Form) Settings
     */
    public function saveContactFormSettings(Request $request): JsonResponse
    {
        $request->validate([
            'badge' => 'nullable|string|max:255',
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'cta_text' => 'nullable|string|max:100',
            'success_title' => 'nullable|string|max:255',
            'success_message' => 'nullable|string|max:1000',
            'error_message' => 'nullable|string|max:1000',
            'fields' => 'nullable|array',
            'is_active' => 'nullable',
        ]);

        $settings = Media::where('page', 'contact')
            ->where('section', 'contact_form')
            ->where('slot', 'form_settings')
            ->first();

        $meta = $settings && $settings->metadata ? $settings->metadata : [];
        $meta['success_title'] = $request->input('success_title', 'Message Sent!');
        $meta['success_message'] = $request->input('success_message', 'Thank you! Your message has been sent successfully. We will get back to you shortly.');
        $meta['error_message'] = $request->input('error_message', 'Something went wrong while sending your message. Please check the form and try again.');

        if ($request->has('fields')) {
            $meta['fields'] = $request->input('fields');
        }

        $isActive = $request->has('is_active') ? filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN) : true;

        Media::updateOrCreate(
            [
                'page' => 'contact',
                'section' => 'contact_form',
                'slot' => 'form_settings',
            ],
            [
                'file_name' => 'contact_form_settings.json',
                'file_path' => 'media/contact/contact_form_settings.json',
                'disk' => 'local',
                'mime_type' => 'application/json',
                'file_type' => 'document',
                'title' => $request->input('title'),
                'tag_text' => $request->input('badge') ?: 'Get In Touch',
                'subtitle' => $request->input('subtitle'),
                'cta_text' => $request->input('cta_text') ?: 'Send Message',
                'sort_order' => 0,
                'is_active' => $isActive,
                'metadata' => $meta,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Contact Form settings updated successfully!'
        ]);
    }

    /**
     * Get Contact Form settings
     */
    public function getContactFormSettings(): JsonResponse
    {
        $settings = Media::where('page', 'contact')
            ->where('section', 'contact_form')
            ->where('slot', 'form_settings')
            ->first();

        $meta = $settings && $settings->metadata ? $settings->metadata : [];
        $defaultFields = [
            ['key' => 'name', 'label' => 'Your Name', 'placeholder' => 'Enter your full name', 'required' => true, 'is_active' => true],
            ['key' => 'email', 'label' => 'Email Address', 'placeholder' => 'Enter your email address', 'required' => true, 'is_active' => true],
            ['key' => 'phone', 'label' => 'Phone Number', 'placeholder' => 'Enter your 10-digit phone number (optional)', 'required' => false, 'is_active' => true],
            ['key' => 'subject', 'label' => 'Subject', 'placeholder' => 'What is this regarding?', 'required' => false, 'is_active' => true],
            ['key' => 'message', 'label' => 'Your Message', 'placeholder' => 'Tell us how we can help you...', 'required' => true, 'is_active' => true],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'badge' => $settings && $settings->tag_text ? $settings->tag_text : 'Get In Touch',
                'title' => $settings && $settings->title ? $settings->title : 'Send Us a Message',
                'subtitle' => $settings && $settings->subtitle ? $settings->subtitle : 'Fill in your details and our team will get back to you promptly.',
                'cta_text' => $settings && $settings->cta_text ? $settings->cta_text : 'Send Message',
                'success_title' => $meta['success_title'] ?? 'Message Sent!',
                'success_message' => $meta['success_message'] ?? 'Thank you! Your message has been sent successfully. We will get back to you shortly.',
                'error_message' => $meta['error_message'] ?? 'Something went wrong while sending your message. Please check the form and try again.',
                'fields' => !empty($meta['fields']) ? $meta['fields'] : $defaultFields,
                'is_active' => $settings ? (bool)$settings->is_active : true,
            ]
        ]);
    }

    /**
     * Save FAQs Section Header
     */
    public function saveContactFaqsHeader(Request $request): JsonResponse
    {
        $request->validate([
            'badge' => 'nullable|string|max:255',
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'is_active' => 'nullable',
        ]);

        $isActive = $request->has('is_active') ? filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN) : true;

        Media::updateOrCreate(
            [
                'page' => 'contact',
                'section' => 'contact_faqs',
                'slot' => 'section_settings',
            ],
            [
                'file_name' => 'contact_faqs_settings.json',
                'file_path' => 'media/contact/contact_faqs_settings.json',
                'disk' => 'local',
                'mime_type' => 'application/json',
                'file_type' => 'document',
                'title' => $request->input('title'),
                'tag_text' => $request->input('badge') ?: 'Help & Support',
                'subtitle' => $request->input('subtitle'),
                'sort_order' => 0,
                'is_active' => $isActive,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'FAQ Section Header updated successfully!'
        ]);
    }

    /**
     * Get Contact FAQs Section Header
     */
    public function getContactFaqsHeader(): JsonResponse
    {
        $settings = Media::where('page', 'contact')
            ->where('section', 'contact_faqs')
            ->where('slot', 'section_settings')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'badge' => $settings && $settings->tag_text ? $settings->tag_text : 'Help & Support',
                'title' => $settings && $settings->title ? $settings->title : 'Frequently Asked Questions',
                'subtitle' => $settings && $settings->subtitle ? $settings->subtitle : 'Quick answers about our handmade creations, custom orders, and delivery.',
                'is_active' => $settings ? (bool)$settings->is_active : true,
            ]
        ]);
    }

    /**
     * Add a new FAQ item
     */
    public function addContactFaq(Request $request): JsonResponse
    {
        $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable',
        ]);

        $maxSort = Media::where('page', 'contact')
            ->where('section', 'contact_faqs')
            ->where('slot', '!=', 'section_settings')
            ->max('sort_order') ?: 0;

        $faq = Media::create([
            'page' => 'contact',
            'section' => 'contact_faqs',
            'slot' => 'faq_' . Str::random(8),
            'title' => $request->input('question'),
            'description' => $request->input('answer'),
            'file_name' => 'faq_item.json',
            'file_path' => 'faq_item',
            'disk' => 'local',
            'mime_type' => 'application/json',
            'file_type' => 'document',
            'sort_order' => $request->filled('sort_order') ? (int)$request->input('sort_order') : ($maxSort + 1),
            'is_active' => $request->has('is_active') ? filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN) : true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'FAQ added successfully!',
            'data' => [
                'id' => $faq->id,
                'question' => $faq->title,
                'answer' => $faq->description,
                'sort_order' => (int)$faq->sort_order,
                'is_active' => (bool)$faq->is_active,
            ]
        ]);
    }

    /**
     * Get a single FAQ item
     */
    public function getContactFaq($id): JsonResponse
    {
        $faq = Media::where('page', 'contact')
            ->where('section', 'contact_faqs')
            ->where('id', $id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $faq->id,
                'question' => $faq->title,
                'answer' => $faq->description,
                'sort_order' => (int)$faq->sort_order,
                'is_active' => (bool)$faq->is_active,
            ]
        ]);
    }

    /**
     * Update an FAQ item
     */
    public function updateContactFaq(Request $request, $id): JsonResponse
    {
        $faq = Media::where('page', 'contact')
            ->where('section', 'contact_faqs')
            ->where('id', $id)
            ->firstOrFail();

        $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable',
        ]);

        $faq->title = $request->input('question');
        $faq->description = $request->input('answer');

        if ($request->filled('sort_order')) {
            $faq->sort_order = (int)$request->input('sort_order');
        }
        if ($request->has('is_active')) {
            $faq->is_active = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
        }
        $faq->save();

        return response()->json([
            'success' => true,
            'message' => 'FAQ updated successfully!',
            'data' => [
                'id' => $faq->id,
                'question' => $faq->title,
                'answer' => $faq->description,
                'sort_order' => (int)$faq->sort_order,
                'is_active' => (bool)$faq->is_active,
            ]
        ]);
    }

    /**
     * Delete an FAQ item
     */
    public function deleteContactFaq($id): JsonResponse
    {
        $faq = Media::where('page', 'contact')
            ->where('section', 'contact_faqs')
            ->where('id', $id)
            ->firstOrFail();

        $faq->delete();

        return response()->json([
            'success' => true,
            'message' => 'FAQ deleted successfully!'
        ]);
    }

    /**
     * Toggle FAQ status
     */
    public function toggleContactFaq($id): JsonResponse
    {
        $faq = Media::where('page', 'contact')
            ->where('section', 'contact_faqs')
            ->where('id', $id)
            ->firstOrFail();

        $faq->is_active = !$faq->is_active;
        $faq->save();

        return response()->json([
            'success' => true,
            'message' => 'FAQ status changed to ' . ($faq->is_active ? 'Active' : 'Inactive'),
            'is_active' => (bool)$faq->is_active,
        ]);
    }

    /**
     * Reorder FAQs
     */
    public function reorderContactFaqs(Request $request): JsonResponse
    {
        $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|integer',
            'orders.*.sort_order' => 'required|integer',
        ]);

        foreach ($request->input('orders') as $item) {
            Media::where('page', 'contact')
                ->where('section', 'contact_faqs')
                ->where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'FAQs reordered successfully!'
        ]);
    }

    /**
     * Get Dynamic Footer Settings & Links
     */
    public function getFooterSettings(): JsonResponse
    {
        $media = Media::where('section', 'footer')->where('slot', 'section_settings')->first();
        if (!$media) {
            $media = Media::where('section', 'footer')->first();
        }

        $meta = $media && $media->metadata ? $media->metadata : [];

        $bgMedia = Media::where('section', 'footer')
            ->where('slot', 'footer_bg')
            ->where('is_active', true)
            ->where('file_path', 'not like', '%.json%')
            ->orderByDesc('updated_at')
            ->first();
        $bgUrl = null;
        if ($bgMedia) {
            $candidate = $bgMedia->desktop_image_url ?: $bgMedia->url;
            if ($candidate && !str_ends_with(strtolower(parse_url($candidate, PHP_URL_PATH) ?? ''), '.json')) {
                $bgUrl = $candidate;
            }
        }
        if (!$bgUrl && !empty($meta['bg_image']) && !str_ends_with(strtolower(parse_url($meta['bg_image'], PHP_URL_PATH) ?? ''), '.json')) {
            $bgUrl = $meta['bg_image'];
        }
        if (!$bgUrl && !empty($meta['bg_image_url']) && !str_ends_with(strtolower(parse_url($meta['bg_image_url'], PHP_URL_PATH) ?? ''), '.json')) {
            $bgUrl = $meta['bg_image_url'];
        }
        if (!$bgUrl) {
            $bgUrl = asset('images/categories/footer.png');
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $media ? $media->id : null,
                'title' => $media && $media->title ? $media->title : 'KNOTELLE',
                'subtitle' => $media && $media->subtitle ? $media->subtitle : 'Made with ♡ for a kinder, cozier world.',
                'tagline' => $media && $media->subtitle ? $media->subtitle : 'Made with ♡ for a kinder, cozier world.',
                'copyright_text' => $meta['copyright_text'] ?? '© ' . date('Y') . ' Knotelle. All rights reserved.',
                'heart_tagline' => $meta['heart_tagline'] ?? 'Made with ♡ for a kinder, cozier world.',
                'bg_image_url' => $bgUrl,
                'instagram_url' => $meta['instagram_url'] ?? (Setting::where('key', 'social_instagram')->value('value') ?: 'https://instagram.com/knotelleindia'),
                'instagram_active' => isset($meta['instagram_active']) ? (bool)$meta['instagram_active'] : true,
                'facebook_url' => $meta['facebook_url'] ?? (Setting::where('key', 'social_facebook')->value('value') ?: 'https://facebook.com/knotelleindia'),
                'facebook_active' => isset($meta['facebook_active']) ? (bool)$meta['facebook_active'] : true,
                'pinterest_url' => $meta['pinterest_url'] ?? 'https://pinterest.com/knotelleindia',
                'pinterest_active' => isset($meta['pinterest_active']) ? (bool)$meta['pinterest_active'] : true,
                'youtube_url' => $meta['youtube_url'] ?? 'https://youtube.com/@knotelleindia',
                'youtube_active' => isset($meta['youtube_active']) ? (bool)$meta['youtube_active'] : true,
                'twitter_url' => $meta['twitter_url'] ?? (Setting::where('key', 'social_twitter')->value('value') ?: ''),
                'twitter_active' => isset($meta['twitter_active']) ? (bool)$meta['twitter_active'] : false,
                'linkedin_url' => $meta['linkedin_url'] ?? (Setting::where('key', 'social_linkedin')->value('value') ?: ''),
                'linkedin_active' => isset($meta['linkedin_active']) ? (bool)$meta['linkedin_active'] : false,
                'col1_title' => $meta['col1_title'] ?? 'Quick Links',
                'col1_links' => $meta['col1_links'] ?? [
                    ['label' => 'Home', 'url' => '/', 'is_active' => true],
                    ['label' => 'Shop', 'url' => '/shop', 'is_active' => true],
                    ['label' => 'Custom Order', 'url' => '/custom-order', 'is_active' => true],
                    ['label' => 'About', 'url' => '/about', 'is_active' => true],
                    ['label' => 'Contact', 'url' => '/contact', 'is_active' => true],
                ],
                'col2_title' => $meta['col2_title'] ?? 'Help',
                'col2_links' => $meta['col2_links'] ?? [
                    ['label' => 'Shipping Policy', 'url' => '/contact', 'is_active' => true],
                    ['label' => 'Return & Refund', 'url' => '/contact', 'is_active' => true],
                    ['label' => 'FAQ', 'url' => '/contact', 'is_active' => true],
                    ['label' => 'Track Order', 'url' => '/account/orders', 'is_active' => true],
                ],
                'col3_title' => $meta['col3_title'] ?? 'Contact',
                'contact_phone' => $meta['contact_phone'] ?? (Setting::where('key', 'store_phone')->value('value') ?: '+91 97730 39243'),
                'contact_phone_link' => $meta['contact_phone_link'] ?? 'tel:+919773039243',
                'contact_email' => $meta['contact_email'] ?? (Setting::where('key', 'store_email')->value('value') ?: 'support@knotelle.in'),
                'contact_email_link' => $meta['contact_email_link'] ?? 'mailto:support@knotelle.in',
                'contact_address' => $meta['contact_address'] ?? 'India',
                'contact_address_link' => $meta['contact_address_link'] ?? '',
                'is_active' => $media ? (bool)$media->is_active : true,
            ]
        ]);
    }

    /**
     * Save Dynamic Footer Settings
     */
    public function saveFooterSettings(Request $request): JsonResponse
    {
        $rules = [
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'copyright_text' => 'nullable|string|max:255',
            'heart_tagline' => 'nullable|string|max:255',
            'instagram_url' => 'nullable|string|max:500',
            'facebook_url' => 'nullable|string|max:500',
            'pinterest_url' => 'nullable|string|max:500',
            'youtube_url' => 'nullable|string|max:500',
            'twitter_url' => 'nullable|string|max:500',
            'linkedin_url' => 'nullable|string|max:500',
            'col1_title' => 'nullable|string|max:100',
            'col2_title' => 'nullable|string|max:100',
            'col3_title' => 'nullable|string|max:100',
            'contact_phone' => 'nullable|string|max:100',
            'contact_phone_link' => 'nullable|string|max:255',
            'contact_email' => 'nullable|string|max:255',
            'contact_email_link' => 'nullable|string|max:255',
            'contact_address' => 'nullable|string|max:255',
            'bg_image_url' => 'nullable|string',
        ];

        if ($request->hasFile('bg_image_file')) {
            $rules['bg_image_file'] = 'file|mimes:jpeg,png,jpg,webp|max:10240';
        }

        $request->validate($rules);

        $media = Media::where('section', 'footer')->where('slot', 'section_settings')->first();
        if (!$media) {
            $media = new Media();
            $media->page = 'homepage';
            $media->section = 'footer';
            $media->slot = 'section_settings';
            $media->file_name = 'footer_settings.json';
            $media->file_path = 'media/footer/footer_settings.json';
            $media->disk = 'local';
            $media->mime_type = 'application/json';
            $media->file_type = 'document';
            $media->uploaded_by = $this->getAdminId();
            $media->uploader_type = 'admin';
        }

        $meta = $media->metadata ?: [];
        $meta['copyright_text'] = $request->input('copyright_text', '© ' . date('Y') . ' Knotelle. All rights reserved.');
        $meta['heart_tagline'] = $request->input('heart_tagline', 'Made with ♡ for a kinder, cozier world.');
        $meta['instagram_url'] = $request->input('instagram_url', 'https://instagram.com/knotelleindia');
        $meta['instagram_active'] = $request->has('instagram_active') ? filter_var($request->input('instagram_active'), FILTER_VALIDATE_BOOLEAN) : true;
        $meta['facebook_url'] = $request->input('facebook_url', 'https://facebook.com/knotelleindia');
        $meta['facebook_active'] = $request->has('facebook_active') ? filter_var($request->input('facebook_active'), FILTER_VALIDATE_BOOLEAN) : true;
        $meta['pinterest_url'] = $request->input('pinterest_url', 'https://pinterest.com/knotelleindia');
        $meta['pinterest_active'] = $request->has('pinterest_active') ? filter_var($request->input('pinterest_active'), FILTER_VALIDATE_BOOLEAN) : true;
        $meta['youtube_url'] = $request->input('youtube_url', 'https://youtube.com/@knotelleindia');
        $meta['youtube_active'] = $request->has('youtube_active') ? filter_var($request->input('youtube_active'), FILTER_VALIDATE_BOOLEAN) : true;
        $meta['twitter_url'] = $request->input('twitter_url', '');
        $meta['twitter_active'] = $request->has('twitter_active') ? filter_var($request->input('twitter_active'), FILTER_VALIDATE_BOOLEAN) : false;
        $meta['linkedin_url'] = $request->input('linkedin_url', '');
        $meta['linkedin_active'] = $request->has('linkedin_active') ? filter_var($request->input('linkedin_active'), FILTER_VALIDATE_BOOLEAN) : false;

        $meta['col1_title'] = $request->input('col1_title', 'Quick Links');
        if ($request->has('col1_links')) {
            $meta['col1_links'] = is_array($request->input('col1_links')) ? $request->input('col1_links') : json_decode($request->input('col1_links'), true);
        }

        $meta['col2_title'] = $request->input('col2_title', 'Help');
        if ($request->has('col2_links')) {
            $meta['col2_links'] = is_array($request->input('col2_links')) ? $request->input('col2_links') : json_decode($request->input('col2_links'), true);
        }

        $meta['col3_title'] = $request->input('col3_title', 'Contact');
        $meta['contact_phone'] = $request->input('contact_phone', '+91 97730 39243');
        $meta['contact_phone_link'] = $request->input('contact_phone_link', 'tel:+919773039243');
        $meta['contact_email'] = $request->input('contact_email', 'support@knotelle.in');
        $meta['contact_email_link'] = $request->input('contact_email_link', 'mailto:support@knotelle.in');
        $meta['contact_address'] = $request->input('contact_address', 'India');
        $meta['contact_address_link'] = $request->input('contact_address_link', '');

        // Two-way sync with store settings so both settings page and media manager stay aligned
        if ($request->filled('contact_phone')) {
            Setting::updateOrCreate(['key' => 'store_phone'], ['value' => $request->input('contact_phone'), 'group' => 'general']);
        }
        if ($request->filled('contact_email')) {
            Setting::updateOrCreate(['key' => 'store_email'], ['value' => $request->input('contact_email'), 'group' => 'general']);
        }
        if ($request->filled('contact_address')) {
            Setting::updateOrCreate(['key' => 'store_address'], ['value' => $request->input('contact_address'), 'group' => 'general']);
        }
        if ($request->filled('instagram_url')) {
            Setting::updateOrCreate(['key' => 'social_instagram'], ['value' => $request->input('instagram_url'), 'group' => 'social']);
        }
        if ($request->filled('facebook_url')) {
            Setting::updateOrCreate(['key' => 'social_facebook'], ['value' => $request->input('facebook_url'), 'group' => 'social']);
        }
        if ($request->filled('twitter_url')) {
            Setting::updateOrCreate(['key' => 'social_twitter'], ['value' => $request->input('twitter_url'), 'group' => 'social']);
        }
        if ($request->filled('linkedin_url')) {
            Setting::updateOrCreate(['key' => 'social_linkedin'], ['value' => $request->input('linkedin_url'), 'group' => 'social']);
        }

        // Handle background image upload
        if ($request->hasFile('bg_image_file')) {
            $file = $request->file('bg_image_file');
            $uploadDir = public_path('images/footer');
            if (!file_exists($uploadDir)) {
                File::makeDirectory($uploadDir, 0775, true, true);
            }
            $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
            $uniqueName = 'footer_bg_' . time() . '.' . $ext;
            $file->move($uploadDir, $uniqueName);
            @chmod($uploadDir . '/' . $uniqueName, 0664);
            $bgPath = 'images/footer/' . $uniqueName;
            $fullBgUrl = asset($bgPath);

            $meta['bg_image'] = $fullBgUrl;
            $meta['desktop_image'] = $fullBgUrl;
            $meta['bg_image_url'] = $fullBgUrl;

            // Deactivate existing footer_bg records
            Media::where('section', 'footer')->where('slot', 'footer_bg')->update(['is_active' => false]);

            // Update or create primary footer_bg media slot
            $bgRecord = Media::where('section', 'footer')->where('slot', 'footer_bg')->first();
            if (!$bgRecord) {
                $bgRecord = new Media();
                $bgRecord->page = 'homepage';
                $bgRecord->section = 'footer';
                $bgRecord->slot = 'footer_bg';
            }
            $bgRecord->file_name = $uniqueName;
            $bgRecord->file_path = $bgPath;
            $bgRecord->disk = 'local';
            $bgRecord->mime_type = 'image/' . $ext;
            $bgRecord->file_type = 'image';
            $bgRecord->title = 'Footer Panoramic Background';
            $bgRecord->sort_order = 1;
            $bgRecord->is_active = true;
            $bgRecord->uploaded_by = $this->getAdminId();
            $bgRecord->uploader_type = 'admin';
            $bgRecord->touch();
            $bgRecord->save();

            Media::where('section', 'footer')
                ->where('slot', 'footer_bg')
                ->where('id', '!=', $bgRecord->id)
                ->update(['is_active' => false]);

        } elseif ($request->filled('bg_image_url')) {
            $url = $request->input('bg_image_url');
            if ($url && !str_ends_with(strtolower(parse_url($url, PHP_URL_PATH) ?? ''), '.json')) {
                $meta['bg_image'] = $url;
                $meta['desktop_image'] = $url;
                $meta['bg_image_url'] = $url;

                Media::where('section', 'footer')->where('slot', 'footer_bg')->update(['is_active' => false]);

                $bgRecord = Media::where('section', 'footer')->where('slot', 'footer_bg')->first();
                if (!$bgRecord) {
                    $bgRecord = new Media();
                    $bgRecord->page = 'homepage';
                    $bgRecord->section = 'footer';
                    $bgRecord->slot = 'footer_bg';
                }
                $bgRecord->file_name = basename($url);
                $bgRecord->file_path = $url;
                $bgRecord->disk = 'local';
                $bgRecord->mime_type = 'image/png';
                $bgRecord->file_type = 'image';
                $bgRecord->title = 'Footer Panoramic Background';
                $bgRecord->sort_order = 1;
                $bgRecord->is_active = true;
                $bgRecord->uploaded_by = $this->getAdminId();
                $bgRecord->uploader_type = 'admin';
                $bgRecord->touch();
                $bgRecord->save();

                Media::where('section', 'footer')
                    ->where('slot', 'footer_bg')
                    ->where('id', '!=', $bgRecord->id)
                    ->update(['is_active' => false]);
            }
        }

        $media->title = $request->input('title', 'KNOTELLE');
        $media->subtitle = $request->input('subtitle', 'Made with ♡ for a kinder, cozier world.');
        $media->metadata = $meta;
        $media->is_active = $request->has('is_active') ? filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN) : true;
        $media->save();

        return response()->json([
            'success' => true,
            'message' => 'Footer settings updated successfully!',
            'data' => $media,
        ]);
    }

    /**
     * Get Dynamic Navbar Settings
     */
    public function getNavbarSettings(): JsonResponse
    {
        $media = Media::where('page', 'global')->where('section', 'navbar')->first();
        $meta = $media && $media->metadata ? $media->metadata : [];
        $logoMedia = Media::where('page', 'global')->where('section', 'global_assets')->where('slot', 'main_logo')->first();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $media ? $media->id : null,
                'logo_url' => $logoMedia ? $logoMedia->url : asset('images/logo/Logo_1.png'),
                'announcement_text' => $meta['announcement_text'] ?? '✨ Free Pan-India Delivery on all Orders above ₹999',
                'announcement_link' => $meta['announcement_link'] ?? '/shop',
                'announcement_active' => isset($meta['announcement_active']) ? (bool)$meta['announcement_active'] : false,
                'nav_links' => $meta['nav_links'] ?? [
                    ['name' => 'Home', 'href' => '/', 'is_highlighted' => false, 'is_active' => true],
                    ['name' => 'Shop', 'href' => '/shop', 'is_highlighted' => false, 'is_active' => true],
                    ['name' => 'Custom Order', 'href' => '/custom-order', 'is_highlighted' => true, 'is_active' => true],
                    ['name' => 'About', 'href' => '/about', 'is_highlighted' => false, 'is_active' => true],
                    ['name' => 'Contact', 'href' => '/contact', 'is_highlighted' => false, 'is_active' => true],
                ],
                'show_search' => isset($meta['show_search']) ? (bool)$meta['show_search'] : true,
                'show_wishlist' => isset($meta['show_wishlist']) ? (bool)$meta['show_wishlist'] : true,
                'show_account' => isset($meta['show_account']) ? (bool)$meta['show_account'] : true,
                'show_cart' => isset($meta['show_cart']) ? (bool)$meta['show_cart'] : true,
                'is_active' => $media ? (bool)$media->is_active : true,
            ]
        ]);
    }

    /**
     * Save Dynamic Navbar Settings
     */
    public function saveNavbarSettings(Request $request): JsonResponse
    {
        $request->validate([
            'announcement_text' => 'nullable|string|max:255',
            'announcement_link' => 'nullable|string|max:255',
            'announcement_active' => 'nullable',
            'nav_links' => 'nullable',
            'show_search' => 'nullable',
            'show_wishlist' => 'nullable',
            'show_account' => 'nullable',
            'show_cart' => 'nullable',
        ]);

        $media = Media::where('page', 'global')->where('section', 'navbar')->first();
        if (!$media) {
            $media = new Media();
            $media->page = 'global';
            $media->section = 'navbar';
            $media->slot = 'navbar_settings';
            $media->file_name = 'navbar_settings.json';
            $media->file_path = 'media/global/navbar_settings.json';
            $media->disk = 'local';
            $media->mime_type = 'application/json';
            $media->file_type = 'document';
            $media->uploaded_by = $this->getAdminId();
            $media->uploader_type = 'admin';
        }

        $meta = $media->metadata ?: [];
        $meta['announcement_text'] = $request->input('announcement_text', '✨ Free Pan-India Delivery on all Orders above ₹999');
        $meta['announcement_link'] = $request->input('announcement_link', '/shop');
        $meta['announcement_active'] = $request->has('announcement_active') ? filter_var($request->input('announcement_active'), FILTER_VALIDATE_BOOLEAN) : false;

        if ($request->has('nav_links')) {
            $meta['nav_links'] = is_array($request->input('nav_links')) ? $request->input('nav_links') : json_decode($request->input('nav_links'), true);
        }

        $meta['show_search'] = $request->has('show_search') ? filter_var($request->input('show_search'), FILTER_VALIDATE_BOOLEAN) : true;
        $meta['show_wishlist'] = $request->has('show_wishlist') ? filter_var($request->input('show_wishlist'), FILTER_VALIDATE_BOOLEAN) : true;
        $meta['show_account'] = $request->has('show_account') ? filter_var($request->input('show_account'), FILTER_VALIDATE_BOOLEAN) : true;
        $meta['show_cart'] = $request->has('show_cart') ? filter_var($request->input('show_cart'), FILTER_VALIDATE_BOOLEAN) : true;

        $media->metadata = $meta;
        $media->is_active = $request->has('is_active') ? filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN) : true;
        $media->save();

        return response()->json([
            'success' => true,
            'message' => 'Navbar settings updated successfully!',
            'data' => $media,
        ]);
    }

    /**
     * Add a Custom Order Item / Category
     */
    public function addCustomOrderItem(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'subtitle' => 'nullable|string|max:150',
            'sort_order' => 'nullable|integer',
        ]);

        $maxSort = Media::where('page', 'custom_order')->where('section', 'custom_order_items')->max('sort_order') ?: 0;

        $icon = $request->input('icon', $request->input('tag_text', 'flower'));

        $item = Media::create([
            'page' => 'custom_order',
            'section' => 'custom_order_items',
            'slot' => 'item_' . \Illuminate\Support\Str::slug($request->title) . '_' . time(),
            'title' => $request->title,
            'subtitle' => $request->subtitle ?: 'Custom pattern',
            'tag_text' => $icon,
            'sort_order' => $request->filled('sort_order') ? (int)$request->sort_order : ($maxSort + 1),
            'is_active' => true,
            'uploaded_by' => $this->getAdminId(),
            'uploader_type' => 'admin',
            'file_name' => 'custom_order.png',
            'file_path' => 'images/categories/categories_bg.png',
            'mime_type' => 'image/png',
            'disk' => 'local'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Custom order item created successfully!',
            'item' => [
                'id' => $item->id,
                'name' => $item->title,
                'title' => $item->title,
                'subtitle' => $item->subtitle,
                'icon' => $item->tag_text ?: 'flower',
                'tag_text' => $item->tag_text ?: 'flower',
                'sort_order' => (int)$item->sort_order,
                'is_active' => (bool)$item->is_active,
            ],
            'data' => $item
        ]);
    }

    /**
     * Get single Custom Order Item
     */
    public function getCustomOrderItem($id): JsonResponse
    {
        $item = Media::where('page', 'custom_order')
            ->where('section', 'custom_order_items')
            ->where('id', $id)
            ->first();

        if (!$item) {
            $item = Media::find($id);
        }

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Custom order item not found.'], 404);
        }

        $itemData = [
            'id' => $item->id,
            'name' => $item->title,
            'title' => $item->title,
            'subtitle' => $item->subtitle ?: 'Custom pattern',
            'icon' => $item->tag_text ?: 'flower',
            'tag_text' => $item->tag_text ?: 'flower',
            'sort_order' => (int)$item->sort_order,
            'is_active' => (bool)$item->is_active,
        ];

        return response()->json([
            'success' => true,
            'item' => $itemData,
            'data' => $itemData
        ]);
    }

    /**
     * Update an existing Custom Order Item
     */
    public function updateCustomOrderItem(Request $request, $id): JsonResponse
    {
        $item = Media::where('page', 'custom_order')
            ->where('section', 'custom_order_items')
            ->where('id', $id)
            ->first();

        if (!$item) {
            $item = Media::find($id);
        }

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Custom order item not found.'], 404);
        }

        $request->validate([
            'title' => 'required|string|max:100',
            'subtitle' => 'nullable|string|max:150',
            'icon' => 'nullable|string|max:50',
            'tag_text' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer',
        ]);

        $item->title = $request->title;
        if ($request->has('subtitle')) {
            $item->subtitle = $request->subtitle;
        }
        if ($request->filled('icon') || $request->filled('tag_text')) {
            $item->tag_text = $request->input('icon', $request->input('tag_text'));
        }
        if ($request->filled('sort_order')) {
            $item->sort_order = (int)$request->sort_order;
        }
        if ($request->has('is_active')) {
            $item->is_active = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
        }
        $item->save();

        $itemData = [
            'id' => $item->id,
            'name' => $item->title,
            'title' => $item->title,
            'subtitle' => $item->subtitle,
            'icon' => $item->tag_text ?: 'flower',
            'tag_text' => $item->tag_text ?: 'flower',
            'sort_order' => (int)$item->sort_order,
            'is_active' => (bool)$item->is_active,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Custom order item updated successfully!',
            'item' => $itemData,
            'data' => $itemData
        ]);
    }

    /**
     * Delete a Custom Order Item
     */
    public function deleteCustomOrderItem($id): JsonResponse
    {
        $item = Media::where('page', 'custom_order')
            ->where('section', 'custom_order_items')
            ->where('id', $id)
            ->first();

        if (!$item) {
            $item = Media::find($id);
        }

        if ($item) {
            $item->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Custom order item deleted successfully!'
        ]);
    }

    /**
     * Toggle status for Custom Order Item
     */
    public function toggleCustomOrderItem($id): JsonResponse
    {
        $item = Media::where('page', 'custom_order')
            ->where('section', 'custom_order_items')
            ->where('id', $id)
            ->first();

        if (!$item) {
            $item = Media::find($id);
        }

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Custom order item not found.'], 404);
        }

        $item->is_active = !$item->is_active;
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'is_active' => (bool)$item->is_active,
        ]);
    }
}


