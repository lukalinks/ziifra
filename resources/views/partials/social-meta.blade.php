@php
    use App\Support\SocialMeta;

    $pageTitle = trim(View::yieldContent('title'));
    $pageDescription = trim(View::yieldContent('meta_description'));
    $metaImage = trim(View::yieldContent('meta_image'));

    $social = SocialMeta::fromPage(
        $pageTitle !== '' ? $pageTitle : null,
        $pageDescription !== '' ? $pageDescription : null,
    );

    if ($metaImage !== '') {
        $social = new SocialMeta(
            title: $social->title,
            description: $social->description,
            imageUrl: $metaImage,
            canonicalUrl: $social->canonicalUrl,
            siteName: $social->siteName,
            type: $social->type,
            imageAlt: $social->imageAlt,
        );
    }

    $ogImageWidth = (int) config('ziifra.social.image_width', 1200);
    $ogImageHeight = (int) config('ziifra.social.image_height', 630);
@endphp

<meta name="description" content="{{ $social->description }}">
<link rel="canonical" href="{{ $social->canonicalUrl }}">

<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ SocialMeta::defaultImageUrl() }}">

<meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">
<meta property="og:type" content="{{ $social->type }}">
<meta property="og:site_name" content="{{ $social->siteName }}">
<meta property="og:title" content="{{ $social->title }}">
<meta property="og:description" content="{{ $social->description }}">
<meta property="og:url" content="{{ $social->canonicalUrl }}">
<meta property="og:image" content="{{ $social->imageUrl }}">
<meta property="og:image:secure_url" content="{{ $social->imageUrl }}">
<meta property="og:image:alt" content="{{ $social->imageAlt }}">
@if ($ogImageWidth > 0 && $ogImageHeight > 0)
    <meta property="og:image:width" content="{{ $ogImageWidth }}">
    <meta property="og:image:height" content="{{ $ogImageHeight }}">
@endif

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $social->title }}">
<meta name="twitter:description" content="{{ $social->description }}">
<meta name="twitter:image" content="{{ $social->imageUrl }}">
<meta name="twitter:image:alt" content="{{ $social->imageAlt }}">
