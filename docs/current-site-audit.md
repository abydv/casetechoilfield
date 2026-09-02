# Current Site Audit — casetechoilfield.com

Source of truth for all migrated content. Captured by crawling the live WordPress
site on 2026-09-02. This document must be treated as authoritative for company
facts, product data, and URLs — nothing here is invented. Where the live site
did not expose a piece of information (e.g. no meta description), it is
explicitly marked "Not present on live site" rather than filled in.

## 1. Platform

- Current CMS: WordPress (lazy-loaded images via `data-lazyloaded`/`data-src`,
  WooCommerce not detected — products are plain pages/CPT, not a WooCommerce
  catalog).
- Sitemap: `https://casetechoilfield.com/wp-sitemap.xml` → single sub-sitemap
  `wp-sitemap-posts-page-1.xml` (all content is WP "pages", no posts/blog CPT
  in the sitemap).
- `robots.txt`:
  ```
  User-agent: *
  Disallow: /wp-admin/
  Allow: /wp-admin/admin-ajax.php

  Sitemap: https://casetechoilfield.com/wp-sitemap.xml
  ```

## 2. Company Information (verbatim / factual — do not alter)

| Field | Value |
|---|---|
| Company name | CASETECH Oilfield Services |
| Founded | 2023 |
| Tagline | "Leading supplier of hard to find equipment for oil drilling companies" |
| Phone | +91-9155501756 |
| Email | casetechoilfield@gmail.com |
| Address | Sector 3, IMT Manesar Industrial Area |
| Copyright notice | "Copyright ©2023 Casetechoilfield. All Rights Reserved" |
| Social / chat | WhatsApp chat widget ("Powered by Joinchat"), greeting: "Hello 👋 Can we help you?" |

No other social media links, certifications, client logos, testimonials,
statistics, or team member names are present anywhere on the live site. These
sections **must not be fabricated** in the new site — either omit them or mark
them as CMS-empty placeholders the admin can fill in later.

No street-level address beyond "Sector 3, IMT Manesar Industrial Area" is
published (no PIN code, no suite/plot number, no embedded Google Map found).

## 3. Full URL Inventory (from wp-sitemap-posts-page-1.xml)

| Old URL | Status | New URL (proposed) | Notes |
|---|---|---|---|
| `/` | Active | `/` | Homepage |
| `/about-us/` | Active | `/about-us` | |
| `/contact-us/` | Active | `/contact-us` | |
| `/products/` | Active | `/products` | Product listing |
| `/bow-spring-centralizers/` | Active | `/products/bow-spring-centralizers` | Product |
| `/cement-baskets/` | Active | `/products/cement-baskets` | Product |
| `/solid-rigid-centralizers/` | Active | `/products/solid-rigid-centralizers` | Product |
| `/stop-collars-2/` | Active | `/products/stop-collars` | Product; old slug has a stray `-2` — clean up in new URL, 301 old |
| `/cable-support-coupling/` | Active | `/products/cable-support-coupling` | Product |
| `/cementing-plug/` | Active | `/products/cementing-plug` | Product |
| `/float-equipment/` | Active | `/products/float-equipment` | Product |
| `/stab-in-shoe-and-collars/` | Active | `/products/stab-in-shoe-and-collars` | Product |
| `/sample-page/` | Orphaned (not in nav) | — | WP default sample page, do not migrate |
| `/coming-soon/` | Orphaned (not in nav) | — | Legacy, do not migrate |
| `/29-2/` | Orphaned (not in nav) | — | Untitled/auto-slugged page, do not migrate |
| `/29-2-2/` | Orphaned (not in nav) | — | Untitled/auto-slugged page, do not migrate |

All 4 orphaned URLs are not linked from navigation, footer, or any crawled
page. They will be added to the redirect manager's 404 log if they receive
traffic post-launch (rule: monitor before deciding whether they need a
redirect), but are not migrated as content.

301 redirect map to create at launch (old → new):

```
/stop-collars-2/          -> /products/stop-collars
/bow-spring-centralizers/ -> /products/bow-spring-centralizers
/cement-baskets/          -> /products/cement-baskets
/solid-rigid-centralizers/-> /products/solid-rigid-centralizers
/cable-support-coupling/  -> /products/cable-support-coupling
/cementing-plug/          -> /products/cementing-plug
/float-equipment/         -> /products/float-equipment
/stab-in-shoe-and-collars/-> /products/stab-in-shoe-and-collars
/about-us/                -> /about-us
/contact-us/              -> /contact-us
/products/                -> /products
```

## 4. Navigation

**Main menu** (Home → About Us → Products [dropdown] → Contact Us, plus a
"Get In Touch" CTA button):

- Home
- About Us
- Products (dropdown, 8 items — see §3)
- Contact Us
- CTA button: "Get In Touch" → `/contact-us/`

**Footer:**
- Product quick-links (5 of the 8 shown): Casing Centralizers, Stop Collars,
  Float Equipment, Stage Cementing Collars, Cementing Plugs — note "Casing
  Centralizers" and "Stage Cementing Collars" in the footer do not map 1:1 to
  distinct product slugs on the site; treat as informal groupings of the real
  8 products (Casing Centralizers ≈ Bow Spring + Solid Rigid Centralizers;
  Stage Cementing Collars is not a distinct page — closest match is Stop
  Collars / Cement Baskets). Flag to admin post-launch rather than guessing.
- Privacy Policy (link present on page chrome, target URL not resolvable —
  page does not exist live; create as empty CMS page for admin to fill in)
- Terms & Conditions (same — link present, no live target)
- Copyright line (see §2)

## 5. Homepage Content (verbatim)

**Hero section**
- Tagline: "Leading supplier of hard to find equipment for oil drilling companies"
- CTA: "View Our Work"

**Six core-values feature grid**
| Title | Copy |
|---|---|
| Efficiency | "Streamline operations with our high-performance oil field tools for optimal productivity." |
| Versatility | "Our tools are designed to be versatile, adapting and excelling in various oil field applications effortlessly." |
| Durability | "Rugged and reliable tools built to withstand the toughest conditions in oil fields." |
| Precision | "Engineered with meticulous precision, our tools deliver unmatched accuracy and performance." |
| Safety | "Always ensuring worker safety with our industry-leading oil field tools and equipment." |
| Innovation | "Cutting-edge technology and engineering solutions for next-generation oil field tools." |

**About section**
- Heading: "A little bit more - About US"
- Body: "CASETECH OILFIELD SERVICES founded in 2023,with the core mission to
  provide exceptional service to the Oil & Gas Industry, We are a leading
  manufacturer and supplier of Oilfield Primary Cementing Equipment
  like- Casing Centralizers, Float equipment, Cementing Plugs, Casing Reamer
  Shoe & Guide shoes, and other Casing Drilling Accessories. Each team member
  brings with them an experience of industry knowledge and practical
  hands-on experience, which provides the confidence that CASETECH can
  deliver. As the heart of the operation, our team collaborates and
  communicates across all to ensure that the job is done with quality and on
  time."
- Subheading: "Who We Are?"
- Body: "We are a company that specializes in providing high-quality and
  innovative oil field tools. We are committed to meeting the specific needs
  of our customers by using the latest technology and conducting thorough
  research and testing throughout our manufacturing process. Our team of
  experts is dedicated to delivering exceptional customer service and
  ensuring that our tools are reliable, durable, and meet industry
  standards. We aim to provide cost-effective solutions while maintaining
  the highest standards of quality and efficiency."
- CTA: "Know More" → About Us

**Products section**
- Heading: "Our Wide Range of PRODUCTS"
- Grid of the same 8 products as the nav (see §3), each with a thumbnail
  image.

**Contact/CTA band**
- Heading: "Get in Touch"
- Two feature callouts: "24/7 hours Customer Support", "100% Quality Product"
- "Call us for information"

**Contact form** (on homepage and on /contact-us/)
- Fields: Name, Email Address, Country, Message
- Submit button
- No CAPTCHA detected on the live form (Contact Form 7 or similar, unverified
  provider). New system must add CAPTCHA (Turnstile/reCAPTCHA) per spec.

## 6. About Us Page (verbatim)

- Title: "About Us | CASETECH Oilfield Services"
- H1: "About US"
- Opening statement: "We maintain unwavering dedication to upholding the
  utmost levels of quality, safety, and ethical conduct in our business
  operations."
- "Who We Are": Founded 2023, "a prominent player in the Oil & Gas Industry",
  manufactures/supplies casing centralizers, float equipment, cementing
  plugs, drilling accessories.
- Mission: "CASETECH's mission is to lead the global market as a provider of
  innovative and high-quality oilfield tools."
- Vision: "Our vision is to be recognized as the global leader in the
  oilfield tool industry."
- "What We Do": "the leading manufacturer of an extensive range of casing
  drilling and cementing accessories", focus on quality, innovation,
  environmental responsibility.
- Repeats: 24/7 Customer Support, 100% Quality Product.

No team photos, no certification badges, no client logos found on this page.

## 7. Contact Us Page (verbatim)

- Address: Sector 3 IMT Manesar Industrial area
- Phone: +91-9155501756
- Email: casetechoilfield@gmail.com
- "24/7 hours Customer Support"
- Form fields: Name, Email Address, Country, Message
- No embedded map iframe detected in the fetch; no business hours table
  beyond "24/7" messaging.

## 8. Product Catalog (verbatim, all 8 products)

Each of these becomes one `products` row with `product_specifications` /
variant sub-entries at launch. Sizes and standards below are the literal
technical facts to migrate — do not alter units or ranges.

### 8.1 Bow Spring Centralizers (`/bow-spring-centralizers/`)
1. Hinged Non-Welded Bow Spring Centralizer — sizes 4-1/2" to 20"; advantages:
   ease of installation, reduced drag, improved cementing, cost effective;
   standard API 10D latest edition.
2. Hinged Welded Bow Spring Centralizer — sizes 3-1/2" to 30"; advantages:
   enhanced stability, increased standoff, durability, improved flow;
   standard API 10D.
3. Hinged Semi-Rigid Non Welded Bow Spring Centralizer — sizes 4-1/2" to 20";
   applications: deviated, horizontal, highly tortuous wells; restoring force
   exceeds API 10D.
4. Hinged Semi-Rigid Welded Bow Spring Centralizer — sizes 4-1/2" to 20";
   improved centralization, enhanced stability, durability.
5. Hinged Non Welded Positive Rigid Centralizer — sizes 4-1/2" to 20";
   standard API 10TR5.
6. Hinged Welded Positive Rigid Centralizer — sizes 4-1/2" to 20"; standard
   API 10TR5.
7. New Generation Bow Spring Centralizer — sizes 4-1/2" to 20"; standard API
   10D latest edition.
8. Slip On Welded Bow Spring Centralizer — sizes 4-1/2" to 20"; standard API
   10D latest edition.

General: custom sizes available on request.

### 8.2 Cement Baskets (`/cement-baskets/`)
1. Slip On Welded Cement Baskets — for casing/liners above porous or weak
   formations; convex-shaped bows welded to end collars; rotatable and
   reciprocable; welded/non-welded convex options; sizes 4-1/2" to 20".
2. Canvas Cement Baskets — high-strength flexible steel staves + heavy-duty
   canvas liners; reduces hydrostatic column above loss zones; riveted
   canvas liners on steel staves; installed between two stop collars; not
   for reciprocation, allows limited pipe movement; sizes 4-1/2" to 20".

Applications: protection from hydrostatic pressure in weak formations,
support of cement columns while setting, accommodation of larger-than-nominal
hole sizes. Custom sizes on request.

### 8.3 Solid Rigid Centralizers (`/solid-rigid-centralizers/`)
1. Slip On Welded Positive Spiralizer — highly deviated/horizontal wells and
   liner hangers; boat-shaped spiral fins, reduced drag; 4-1/2" to 20".
2. Slip On Heavy Duty Welded Positive Spiralizer — extra heavy loads,
   deviated/horizontal wells; hydrodynamic spiral fins; 4-1/2" to 20".
3. Slip On Heavy Duty Straight Spiralizer — highly deviated/horizontal wells,
   ideal with Liner Hangers; 4-1/2" to 20".
4. Slip On Stand Off Band — positive casing standoff in cased/open holes;
   angled fins for turbulent flow; 4-1/2" to 20".
5. Thermoplastic Centralizer — high strength-to-weight ratio, chemical
   resistance, lower cost, lightweight, corrosion-resistant; 4-1/2" to 20".
6. Aluminum Spiral Vane Solid Rigid Centralizer — vortex motion for improved
   fluid velocity, max horizontal standoff; 4-1/2" to 20".

General: complies with 10 TR5 standards; iron phosphate coating with
polyester powder finish; custom sizes on request.

### 8.4 Stop Collars (`/stop-collars-2/`)
1. Hinged Spiral Nail Stop Collar — two spiral-locking pins driven in to lock
   the collar, latches onto casing without slipping, max annular clearance;
   4-1/2" to 20"; API RP 10D2.
2. Hinged Bolted Stop Collar — draw-bolt forces the collar to grip casing;
   two-piece hinged, single bolt; 4-1/2" to 20"; API RP 10D2.
3. Hinged Set Screw Stop Collar — two parts hinged at 180°, set screws for
   grip, effective for low annular clearance; 4-1/2" to 20"; API RP 10D2.
4. Slip on Set Screw Stop Collar — single-piece, one row of set screws, high
   axial loads; configurations: unbeveled, single-side beveled, both-side
   beveled, heavy-duty with zig-zag screw; 4-1/2" to 20"; API RP 10D2.

General: iron phosphate coating, polyester powder coating, API RP 10D2
compliant, custom sizes on request.

### 8.5 Cable Support Coupling (`/cable-support-coupling/`)
1. Non Ferrous Centralizer With Cable Support — centralizes casing while
   protecting downhole casing cables from crushing between production
   tubing couplings and casing ID (prevents costly unscheduled workovers /
   recompletions).
2. Cross Coupling With Cable Support — prevents casing cables bending,
   crushing, exposure to hostile environments; technology originated in the
   North Sea, now deployed globally, protecting downhole cables in thousands
   of wells onshore/offshore.

No sizes/standards/images/PDFs published for this product on the live site.

### 8.6 Cementing Plug (`/cementing-plug/`)
1. Conventional Cementing Plug — graded rubber (NBR & HNBR) fused onto
   composite or aluminum core; up to 250°F (aluminum core); completely PDC
   drillable; tested to API 10TR6; sizes 3-1/2" to 20"; top plug (single
   system) or bottom plug (dual system); works with synthetic or mud fluids.
2. Anti Rotating Cementing Plug — reinforced locking teeth built into the
   plug; high-quality graded rubber with plastic core, no metal parts;
   eliminates plug rotation during drill-out; completely PDC drillable;
   tested to API 10TR6; sizes 3-1/2" to 20"; top & bottom plug options.

Custom sizes on request.

### 8.7 Float Equipment (`/float-equipment/`)
1. Float Shoe Single/Double Valve — seamless casing-grade steel, positive
   sealing in vertical/horizontal/deviated wells; plunger valve from
   high-polymer plastic + natural rubber with phenolic coating; tested/rated
   to API spec; max circulation rates; sizes 3-1/2" to 30"; custom sizes,
   API/premium connections (BTC, LTC, STC), conventional/anti-rotating
   profiles, upjet/downjet/both configurations.
2. Float Collar Single/Double Valve — prevents cement slurry flowback when
   pumping stops; seamless casing-grade steel, non-metallic plunger valve;
   material traceability from mill certificates; CNC machined; API RP 10F
   compliant; PDC drillable; sizes 3-1/2" to 30"; same customization options
   as the Float Shoe.

### 8.8 Stab-In Shoe and Collars (`/stab-in-shoe-and-collars/`)
1. Stab-in Float Shoe w/o Latch in Profile — stab-in profile for stab-in
   cementing (drill pipe stabs directly into the float shoe); inner-string
   cementing; single or double valve; sizes 9-5/8" to 36"; API/premium
   connections (BTC, LTC, STC).
2. Stab-in Float Collar w/o Latch in Profile — designed for cementing large
   diameter casing through casing or drill pipe; improves displacement
   accuracy, reduces cement volume and net rig time; sizes 9-5/8" to 36";
   API/premium connections.
3. Bullet Nose / Eccentric Nose Float Shoe — multiple nose types
   (Conventional, Non-Rotating, Auto Fill Up, Stab-in, Differential Fill
   Up); materials: cement, polyamide plastic, aluminium.

## 9. Media Assets (real URLs to re-download and migrate)

All under `https://casetechoilfield.com/wp-content/uploads/...`:

| File | Used as |
|---|---|
| `2023/07/casetechoilfield-1.png` (682×131, also `-480x92`) | Site logo |
| `2023/07/tools.jpg` (639×494, also `-480x371`) | About/hero image |
| `2023/07/CT01.png` (184×342, also `-161x300`) | Bow Spring Centralizers thumb |
| `2023/07/CTFS.png` (127×293) | Float Equipment thumb |
| `2023/07/CT-21.png` (71×253) | Cable Support Coupling thumb |
| `2023/07/CTCP1.png` (172×187) | Cementing Plug thumb |
| `2023/07/CT15.png` (627×353, also `-480x270`) | Solid Rigid Centralizers thumb |
| `2023/07/CT10.png` (196×202) | Cement Basket thumb |
| `2023/07/CTSISL.png` (68×246) | Stab-In Shoe and Collars thumb |
| `2023/07/CT17.png` (242×180) | Casing Stop Collars thumb |
| `2023/03/casetechoilfield-2-1.png` (389×243, also `-300x187`) | Footer image |

No PDFs, datasheets, or brochures were found linked on any crawled page.
Product detail pages have no dedicated gallery images beyond the single
listing thumbnail — the new CMS should allow the admin to add richer
galleries per product without requiring that content to exist on day one.

## 10. SEO Metadata Observed

- Homepage `<title>`: "CASETECH Oilfield Services |" (trailing pipe with
  nothing after it — looks like an unconfigured SEO plugin title template).
- Each subpage title follows the pattern `"{Page Name} | CASETECH Oilfield
  Services"`.
- No `<meta name="description">`, no Open Graph tags, and no structured data
  were found on any crawled page.
- No canonical tags detected.

**Conclusion for migration:** the current site has essentially no SEO
metadata beyond default WordPress titles. The new CMS must let the admin set
proper titles/descriptions/OG data per page, but there is no existing SEO
copy to "preserve" beyond the title pattern above, which is a reasonable
starting default (`{Content Title} | CASETECH Oilfield Services`).

## 11. Forms

- One form type observed: a simple enquiry/contact form (Name, Email,
  Country, Message) appearing on both the homepage and `/contact-us/`.
- No visible CAPTCHA/spam protection.
- No file upload field, no product-specific "Request a Quote" form was found
  on individual product pages (despite 8 distinct products) — this is a gap
  the new CMS's per-product enquiry feature (spec §15) directly fixes; it is
  new functionality, not a migration of existing behavior.

## 12. Design / Visual Identity Notes

- No brand color values could be extracted (WebFetch renders content as
  text; a full CSS/branding audit should be done visually against
  screenshots before finalizing the theme customizer defaults in the new
  site). Treat "preserve brand colors" as: capture screenshots and sample
  the primary color from the live header/CTA buttons during frontend
  implementation, rather than guessing hex codes here.
- Layout is a conventional single-column WordPress theme: sticky header,
  full-width hero, boxed content sections, WhatsApp floating widget, simple
  footer.

## 13. Gaps vs. the CMS Spec (things NOT present on the live site)

These are legitimately absent — the new CMS should support them, but there is
no source content to migrate for:

- Services module (no distinct "services" content on the live site — the
  "service" language is descriptive copy inside About Us, not a Services
  catalog)
- Projects / case studies
- Industries
- Clients / client logos
- Testimonials
- Team members
- Certifications
- Blog / news
- FAQs
- Downloads (datasheets/brochures/PDFs)
- Sliders (homepage hero is static, not a slider)
- Social media links (only WhatsApp chat)
- Privacy Policy / Terms & Conditions body copy (links exist, pages don't)

The CMS must ship with these modules empty/ready-to-fill rather than
pre-populated with placeholder text.

## 14. Migration Checklist

- [ ] Download and re-host all 11 media files listed in §9
- [ ] Create 8 `products` records with variant data from §8 as
      `product_specifications`
- [ ] Create `about-us`, `contact-us`, homepage content from §5–§6
- [ ] Recreate the enquiry form (§11) with Name/Email/Country/Message fields
      + CAPTCHA
- [ ] Create empty Privacy Policy / Terms & Conditions pages for the admin
      to populate
- [ ] Load the 301 redirect map from §3
- [ ] Configure default SEO title template `{Title} | CASETECH Oilfield
      Services` site-wide, with per-page override
- [ ] Set global company info (phone, email, address) in Settings → General
      exactly as captured in §2
