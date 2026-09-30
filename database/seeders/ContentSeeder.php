<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\Faq;
use App\Models\Inquiry;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedBlogCategories();
        $this->seedBlogTags();
        $this->seedBlogs();
        $this->seedTestimonials();
        $this->seedFaqs();
        $this->seedInquiries();
    }

    private function seedBlogCategories(): void
    {
        $categories = [
            ['name' => 'Market Updates', 'slug' => 'market-updates'],
            ['name' => 'Investment Tips', 'slug' => 'investment-tips'],
            ['name' => 'Buying Guide', 'slug' => 'buying-guide'],
            ['name' => 'Home Loan', 'slug' => 'home-loan'],
            ['name' => 'Lifestyle', 'slug' => 'lifestyle'],
            ['name' => 'Property News', 'slug' => 'property-news'],
        ];

        foreach ($categories as $cat) {
            BlogCategory::firstOrCreate(['slug' => $cat['slug']], $cat);
        }
    }

    private function seedBlogTags(): void
    {
        $tags = [
            ['name' => 'Investment', 'slug' => 'investment'],
            ['name' => 'Property Tips', 'slug' => 'property-tips'],
            ['name' => 'Lahore', 'slug' => 'lahore'],
            ['name' => 'Karachi', 'slug' => 'karachi'],
            ['name' => 'Plots', 'slug' => 'plots'],
            ['name' => 'Home Loan', 'slug' => 'home-loan'],
            ['name' => 'Market Trends', 'slug' => 'market-trends'],
            ['name' => 'Real Estate', 'slug' => 'real-estate'],
        ];

        foreach ($tags as $tag) {
            BlogTag::firstOrCreate(['slug' => $tag['slug']], $tag);
        }
    }

    private function seedBlogs(): void
    {
        $author = User::first();

        if (! $author) {
            return;
        }

        $marketUpdates = BlogCategory::where('slug', 'market-updates')->value('id');
        $buyingGuide = BlogCategory::where('slug', 'buying-guide')->value('id');
        $investmentTips = BlogCategory::where('slug', 'investment-tips')->value('id');
        $propertyNews = BlogCategory::where('slug', 'property-news')->value('id');
        $homeLoan = BlogCategory::where('slug', 'home-loan')->value('id');
        $lifestyle = BlogCategory::where('slug', 'lifestyle')->value('id');

        $blogs = [
            [
                'title' => 'Real Estate Market Trends in Pakistan 2026: What to Expect?',
                'slug' => 'real-estate-market-trends-in-pakistan-2026',
                'excerpt' => 'An in-depth look at the current real estate market trends and future predictions for major cities.',
                'content' => "<h2>Understanding the Current Market</h2>\n<p>Pakistan's real estate sector remains one of the most important parts of the economy. Major cities such as Lahore, Karachi and Islamabad continue to attract both local and overseas investors.</p>\n<h2>What Buyers Should Look For</h2>\n<p>Location remains one of the most important factors when evaluating a property. Buyers should consider accessibility, nearby facilities, development quality and the long-term potential of the surrounding area.</p>\n<h2>Investment Opportunities</h2>\n<p>For investors, the best opportunities often come from understanding the difference between short-term market movements and long-term development potential.</p>",
                'blog_category_id' => $marketUpdates,
                'read_time' => 5,
                'status' => 'published',
                'featured' => true,
                'published_at' => '2026-05-15',
            ],
            [
                'title' => 'A Complete Guide to Buying Property in Pakistan',
                'slug' => 'complete-guide-to-buying-property-in-pakistan',
                'excerpt' => 'Step-by-step guide for first-time buyers to make a safe and smart property investment.',
                'content' => "<h2>Step 1: Set Your Budget</h2>\n<p>Before searching for properties, determine how much you can afford including registration, transfer and agent fees.</p>\n<h2>Step 2: Choose the Right Location</h2>\n<p>Research areas with good infrastructure, schools, hospitals and transport links.</p>\n<h2>Step 3: Verify Documents</h2>\n<p>Always verify the title deed, NOC and approved building plan before making payment.</p>",
                'blog_category_id' => $buyingGuide,
                'read_time' => 6,
                'status' => 'published',
                'featured' => true,
                'published_at' => '2026-05-12',
            ],
            [
                'title' => 'Best Areas to Invest in Lahore Right Now',
                'slug' => 'best-areas-to-invest-in-lahore-right-now',
                'excerpt' => 'Explore top investment hotspots in Lahore with high ROI and future growth potential.',
                'content' => "<h2>DHA Lahore</h2>\n<p>DHA remains the gold standard for residential investment in Lahore with consistent appreciation.</p>\n<h2>Bahria Town Lahore</h2>\n<p>Bahria Town offers a range of plot sizes and built properties with excellent amenities.</p>\n<h2>Park View City</h2>\n<p>One of the fastest growing societies near DHA, offering competitive prices with modern infrastructure.</p>",
                'blog_category_id' => $investmentTips,
                'read_time' => 4,
                'status' => 'published',
                'featured' => false,
                'published_at' => '2026-05-10',
            ],
            [
                'title' => 'New Metro City Lahore – A Game Changer for Real Estate',
                'slug' => 'new-metro-city-lahore-a-game-changer-for-real-estate',
                'excerpt' => 'How New Metro City Lahore is transforming the real estate landscape with world-class living.',
                'content' => "<h2>Location and Accessibility</h2>\n<p>Situated on the main GT Road near Sarai Alamgir, New Metro City offers strategic location advantages.</p>\n<h2>Master Plan</h2>\n<p>The society features a comprehensive master plan with residential, commercial and recreational zones.</p>",
                'blog_category_id' => $propertyNews,
                'read_time' => 4,
                'status' => 'published',
                'featured' => false,
                'published_at' => '2026-05-08',
            ],
            [
                'title' => 'Home Loan in Pakistan: Everything You Need to Know',
                'slug' => 'home-loan-in-pakistan-everything-you-need-to-know',
                'excerpt' => 'Complete guide to home loans, interest rates, eligibility and application process in Pakistan.',
                'content' => "<h2>Types of Home Loans</h2>\n<p>Pakistani banks offer conventional and Islamic home financing options with varying terms.</p>\n<h2>Eligibility Criteria</h2>\n<p>Most banks require a minimum monthly income, age between 22-60, and a clean credit history.</p>\n<h2>Application Process</h2>\n<p>Apply with your CNIC, salary slips, bank statements and property documents.</p>",
                'blog_category_id' => $homeLoan,
                'read_time' => 6,
                'status' => 'published',
                'featured' => false,
                'published_at' => '2026-05-05',
            ],
            [
                'title' => '5 Home Interior Trends That Add Value to Your Property',
                'slug' => '5-home-interior-trends-that-add-value-to-your-property',
                'excerpt' => 'Simple and modern interior trends that can increase the value of your home.',
                'content' => "<h2>Open Floor Plans</h2>\n<p>Open layouts make spaces feel larger and more inviting — a key selling point for modern buyers.</p>\n<h2>Smart Home Features</h2>\n<p>Automated lighting, security cameras and smart thermostats are increasingly expected by buyers.</p>",
                'blog_category_id' => $lifestyle,
                'read_time' => 5,
                'status' => 'published',
                'featured' => false,
                'published_at' => '2026-05-03',
            ],
            [
                'title' => 'How to Verify Property Documents in Pakistan',
                'slug' => 'how-to-verify-property-documents-in-pakistan',
                'excerpt' => 'Avoid fraud with this checklist for verifying ownership, transfer and society records.',
                'content' => "<h2>Title Deed Verification</h2>\n<p>Visit the local registrar office to verify the title deed and check for any encumbrances.</p>\n<h2>NOC from Society</h2>\n<p>Obtain a No Objection Certificate from the housing society confirming the seller's ownership.</p>",
                'blog_category_id' => $buyingGuide,
                'read_time' => 8,
                'status' => 'published',
                'featured' => false,
                'published_at' => '2026-05-01',
            ],
            [
                'title' => 'Rental Yield Guide for Pakistani Cities',
                'slug' => 'rental-yield-guide-for-pakistani-cities',
                'excerpt' => 'Compare rental returns across Karachi, Lahore and Islamabad before you buy to let.',
                'content' => "<h2>Lahore Rental Market</h2>\n<p>Lahore offers rental yields of 3-5% annually, with DHA and Gulberg being the most popular areas.</p>\n<h2>Karachi Rental Market</h2>\n<p>Karachi's commercial areas offer higher yields, especially in Clifton and Defence.</p>",
                'blog_category_id' => $investmentTips,
                'read_time' => 5,
                'status' => 'published',
                'featured' => false,
                'published_at' => '2026-04-28',
            ],
            [
                'title' => 'Top Upcoming Housing Societies in Islamabad',
                'slug' => 'top-upcoming-housing-societies-in-islamabad',
                'excerpt' => 'A look at the approved societies drawing the most buyer interest this year.',
                'content' => "<h2>Capital Smart City</h2>\n<p>Pakistan's first smart city project located on the Lahore-Islamabad motorway interchange.</p>\n<h2>Park View City Islamabad</h2>\n<p>A rapidly developing society with excellent views of the Margalla Hills.</p>",
                'blog_category_id' => $propertyNews,
                'read_time' => 6,
                'status' => 'published',
                'featured' => false,
                'published_at' => '2026-04-25',
            ],
        ];

        $realEstate = BlogTag::where('slug', 'real-estate')->value('id');
        $investment = BlogTag::where('slug', 'investment')->value('id');
        $lahoreTag = BlogTag::where('slug', 'lahore')->value('id');

        foreach ($blogs as $blogData) {
            $blog = Blog::firstOrCreate(
                ['slug' => $blogData['slug']],
                array_merge($blogData, [
                    'author_id' => $author->id,
                    'image' => '/images/blog-1.jpg',
                ])
            );

            if ($blog->wasRecentlyCreated && $realEstate && $investment) {
                $tags = [$realEstate];
                if (str_contains($blog->slug, 'invest') || str_contains($blog->slug, 'market')) {
                    $tags[] = $investment;
                }
                if (str_contains($blog->slug, 'lahore') && $lahoreTag) {
                    $tags[] = $lahoreTag;
                }
                $blog->tags()->sync($tags);
            }
        }
    }

    private function seedTestimonials(): void
    {
        $testimonials = [
            [
                'name' => 'Mubashir Ali',
                'city' => 'Lahore',
                'avatar' => 'https://i.pravatar.cc/150?img=23',
                'rating' => 5,
                'purchase' => 'Bought a 1 Kanal House in DHA Phase 6',
                'quote' => 'Sarzmeen made buying our first house stress-free. Every listing was exactly as described, and the agent stayed with us till the final transfer.',
                'status' => 'published',
                'featured' => true,
            ],
            [
                'name' => 'Chand Butt',
                'city' => 'Lahore',
                'avatar' => 'https://i.pravatar.cc/150?img=41',
                'rating' => 5,
                'purchase' => 'Invested in Bahria Orchard Apartments',
                'quote' => 'I compared five different portals before deciding — Sarzmeen had the most accurate pricing and the verified badge actually meant something.',
                'status' => 'published',
                'featured' => true,
            ],
            [
                'name' => 'Hussnain Hussain',
                'city' => 'Islamabad',
                'avatar' => 'https://i.pravatar.cc/150?img=8',
                'rating' => 5,
                'purchase' => 'Rented an office in Blue Area',
                'quote' => 'Truly professional agents with a very cooperative team. Found a commercial space within a week.',
                'status' => 'published',
                'featured' => false,
            ],
            [
                'name' => 'Usman Ghazi',
                'city' => 'Karachi',
                'avatar' => 'https://i.pravatar.cc/150?img=56',
                'rating' => 4,
                'purchase' => 'Sold a plot in Bahria Town Karachi',
                'quote' => 'Listed my plot on a Monday, had three serious buyers by Thursday. The team followed up on every inquiry.',
                'status' => 'published',
                'featured' => false,
            ],
            [
                'name' => 'Ayesha Malik',
                'city' => 'Lahore',
                'avatar' => 'https://i.pravatar.cc/150?img=29',
                'rating' => 5,
                'purchase' => 'Bought a 5 Marla Plot in DHA Phase 9',
                'quote' => 'Transparent pricing, no hidden agent fees, and the documentation support was excellent for a first-time buyer.',
                'status' => 'published',
                'featured' => false,
            ],
            [
                'name' => 'Zain Abbas',
                'city' => 'Multan',
                'avatar' => 'https://i.pravatar.cc/150?img=62',
                'rating' => 5,
                'purchase' => 'Bought a 3 Marla House',
                'quote' => 'The property matched every photo and detail from the listing. Genuinely the most reliable real estate experience.',
                'status' => 'published',
                'featured' => false,
            ],
        ];

        foreach ($testimonials as $data) {
            Testimonial::firstOrCreate(
                ['name' => $data['name'], 'city' => $data['city']],
                $data
            );
        }
    }

    private function seedFaqs(): void
    {
        $faqs = [
            [
                'question' => 'How can I list my property on Sarzameen.com?',
                'answer' => 'Create an account, go to "Add Property" and fill in your listing details. Our team reviews and publishes it within 24 hours.',
                'category' => 'Selling',
                'order' => 1,
            ],
            [
                'question' => 'How long does it take to sell a property?',
                'answer' => 'It varies by location and price, but verified listings on Sarzameen.com typically get inquiries within the first week.',
                'category' => 'Selling',
                'order' => 2,
            ],
            [
                'question' => 'Is there any fee for listing a property?',
                'answer' => 'Basic listings are free. Featured placements have a small fee — details are shown before you confirm.',
                'category' => 'Selling',
                'order' => 3,
            ],
            [
                'question' => 'Do you provide property verification?',
                'answer' => 'Yes, our team verifies ownership documents and listing details before a property is marked as Verified.',
                'category' => 'Buying',
                'order' => 4,
            ],
            [
                'question' => 'How can I contact customer support?',
                'answer' => 'Use the contact form, call or WhatsApp us directly, or email info@sarzameen.com — we typically reply within a few hours.',
                'category' => 'Support',
                'order' => 5,
            ],
            [
                'question' => 'In which cities is Sarzameen.com available?',
                'answer' => 'We currently cover Lahore, Islamabad, Karachi, Rawalpindi, Faisalabad and Multan, with more cities being added regularly.',
                'category' => 'General',
                'order' => 6,
            ],
        ];

        foreach ($faqs as $data) {
            Faq::firstOrCreate(
                ['question' => $data['question']],
                array_merge($data, ['status' => 'published'])
            );
        }
    }

    private function seedInquiries(): void
    {
        $inquiries = [
            [
                'name' => 'Ali Raza',
                'email' => 'ali.raza@email.com',
                'phone' => '+92 300 1234567',
                'message' => 'I am interested in this property, please share more details.',
                'source' => 'website',
                'status' => 'new',
            ],
            [
                'name' => 'Sarah Khan',
                'email' => 'sarah.khan@email.com',
                'phone' => '+92 321 9876543',
                'message' => 'Can you arrange a site visit for this plot?',
                'source' => 'website',
                'status' => 'contacted',
            ],
            [
                'name' => 'Usman Ahmed',
                'email' => 'usman.ahmed@email.com',
                'phone' => '+92 333 4445566',
                'message' => 'What is the negotiable price range for this property?',
                'source' => 'website',
                'status' => 'pending',
            ],
            [
                'name' => 'Fatima Noor',
                'email' => 'fatima.noor@email.com',
                'phone' => '+92 302 7778899',
                'message' => 'Is this property available for rent as well?',
                'source' => 'website',
                'status' => 'new',
            ],
            [
                'name' => 'Bilal Hussain',
                'email' => 'bilal.hussain@email.com',
                'phone' => '+92 345 1122334',
                'message' => 'I would like to know more about the payment plan.',
                'source' => 'website',
                'status' => 'closed',
            ],
        ];

        foreach ($inquiries as $data) {
            Inquiry::firstOrCreate(
                ['email' => $data['email']],
                $data
            );
        }
    }
}
