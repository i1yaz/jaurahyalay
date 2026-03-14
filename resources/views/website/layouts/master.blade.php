<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,  maximum-scale=1">
    <meta name="author" content="Owner">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
        integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="apple-touch-icon" sizes="180x180" href="{{asset('website/img/apple-touch-icon.png')}}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{asset('website/img/favicon-32x32.png')}}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{asset('website/img/favicon-16x16.png')}}">
    <link rel="manifest" href="{{asset('website/img/site.webmanifest')}}">
    {{-- <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu&display=swap" rel="stylesheet"> --}}

    <meta http-equiv="refresh" content="60">
    @vite('resources/css/app.css')
    <title>{{ $title }}</title>
    @if (isset($tournament))
        @php
            $poster = str_replace(pathinfo($tournament->poster, PATHINFO_EXTENSION), 'jpg', $tournament->poster);
        @endphp
        <meta name="description" content="{{ $title }}" />
        <meta property="og:title" content="{{ $title }}" />
        <meta property="og:url" content="{{ url()->current() }}" />
        <meta property="og:description" content="{{ $title }}" />
        <meta property="og:image" content="{{ asset('uploads/' . $poster) }}" />
        <meta property="og:type" content="website" />
    @endif
    @stack('css')
    <style>
        body {
            font-family: 'Noto Nastaliq Urdu', serif;
        }
    </style>
    <x-facebook-pixel />
    <x-meta-capi-tracking />
</head>

<body class="hold-transition sidebar-mini">
    @php
        $segmentClub = Request::segment(2);
        $route = Route::current()->getName();
    @endphp

    <div class="container-fluid" id="header">
        @include('website.include.navbar')

        @php
            $images = [];

            if (isset($tournament) && $tournament->poster) {
                $images[] = asset('uploads/' . $tournament->poster);
            }

            if (empty($images) && isset($sliders) && $sliders->count()) {
                foreach ($sliders as $slider) {
                    if (!empty($slider->slider)) {
                        $images[] = asset('website/sliders/' . $slider->slider);
                    }
                }
            }
            //  else {
            //     foreach ($sliders as $slider) {
            //         if (!empty($slider->slider)) {
            //             $images[] = asset('website/sliders/' . $slider->slider);
            //         }
            //     }
            // }
        @endphp

        @if (count($images))
            <div id="carouselControls" class="carousel slide" data-ride="carousel" data-interval="3000">
                <div class="carousel-inner">
                    @foreach ($images as $index => $image)
                        <div class="carousel-item @if ($index === 0) active @endif">
                            <img class="d-block w-100 lozad" src="{{ $image }}" data-src="{{ $image }}"
                                alt="slide {{ $index + 1 }}">
                        </div>
                    @endforeach
                </div>
                @if (count($images) > 1)
                    <a class="carousel-control-prev" href="#carouselControls" role="button" data-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="sr-only">Previous</span>
                    </a>
                    <a class="carousel-control-next" href="#carouselControls" role="button" data-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="sr-only">Next</span>
                    </a>
                @endif
            </div>
        @endif


        @include('website.include.news')
    </div>
    @yield('content')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.3/jquery.slim.min.js"
        integrity="sha512-jxwTCbLJmXPnV277CvAjAcWAjURzpephk0f0nO2lwsvcoDMqBdy1rh1jEwWWTabX1+Grdmj9GFAgtN22zrV0KQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    @include('website.include.footer')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/lozad/dist/lozad.min.js"></script>
    @vite('resources/js/app.js')
    @stack('js')
</body>

</html>
