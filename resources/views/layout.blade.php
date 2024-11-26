<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>QR Code Generator</title>
    <meta name="description" content="Здружението на офталмолози со гордост ги обединува македонските офталмолози од 1954 година, градејќи традиција на професионализам и современа офталмологија.">
    <meta name="keywords" content=" Почетна, За нас, Офталмолози, Историја, Настани, Контакт, Конгрес, Македонско списание за офталмологија, Галерија, Регистрација, Котизација, Програма, Пријава на апстракти, Контакт, Спонзори, X Конгрес на офталмолозите (СЕЕОС), Организација, Поканети предавачи, За Скопје, Детална програма, ">

    <!-- Favicons -->
    <link href="https://ik.imagekit.io/fjxlxprragso/zom/favicon_kb6s0NKO5.png?updatedAt=1731929284988" rel="icon">
    <link href="https://ik.imagekit.io/fjxlxprragso/zom/favicon_kb6s0NKO5.png?updatedAt=1731929284988" rel="apple-touch-icon">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="{{asset('front-end/assets/vendor/bootstrap/css/bootstrap.min.css')}}" rel="stylesheet">
    <link href="{{asset('front-end/assets/vendor/bootstrap-icons/bootstrap-icons.css')}}" rel="stylesheet">
    <link href="{{asset('front-end/assets/vendor/aos/aos.css')}}" rel="stylesheet">
    <link href="{{asset('front-end/assets/vendor/glightbox/css/glightbox.min.css')}}" rel="stylesheet">
    <link href="{{asset('front-end/assets/vendor/swiper/swiper-bundle.min.css')}}" rel="stylesheet">

    <!-- Main CSS File -->
    <link href="{{asset('front-end/assets/css/main.css')}}" rel="stylesheet">
    @stack('styles')
</head>

<body>


    @yield('content')

    <!-- ======= Footer ======= -->

    <!-- End Footer -->

    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Preloader -->
    <div id="preloader"></div>

    <!-- Vendor JS Files -->
    <script src="{{asset('front-end/assets/vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
    <script src="{{asset('front-end/assets/vendor/php-email-form/validate.js')}}"></script>
    <script src="{{asset('front-end/assets/vendor/aos/aos.js')}}"></script>
    <script src="{{asset('front-end/assets/vendor/glightbox/js/glightbox.min.js')}}"></script>
    <script src="{{asset('front-end/assets/vendor/purecounter/purecounter_vanilla.js')}}"></script>
    <script src="{{asset('front-end/assets/vendor/swiper/swiper-bundle.min.js')}}"></script>

    <!-- Main JS File -->
    <script src="{{asset('front-end/assets/js/main.js')}}"></script>
    @stack('scripts')

</body>

</html>
