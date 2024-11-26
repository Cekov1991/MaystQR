@extends('layout')

@section('content')
<!-- Page Title -->
<div class="page-title" data-aos="fade">
    <div class="heading">
        <div class="container">
            <div class="row d-flex justify-content-center text-center">
                <div class="col-lg-8">
                    <h1>Најнови новости</h1>
                    <p class="mb-0">Бидете во тек со најновите објави и новости</p>
                </div>
            </div>
        </div>
    </div>
    <nav class="breadcrumbs">
        <div class="container">
            <ol>
                <li><a href="{{route('welcome')}}">Насловна</a></li>
                <li class="current">Новости</li>
            </ol>
        </div>
    </nav>
</div><!-- End Page Title -->
    <!-- ======= Blogs Section ======= -->
    <section id="blogs" class="blogs">
        <div class="container">

            <div class="row">
                <!-- Category Sidebar -->
                <div class="col-lg-3">
                    <div class="sidebar">
                        <div class="sidebar-item categories">
                            <h3 class="sidebar-title">Категории</h3>
                            <ul class="mt-3">
                                <li>
                                    <a href="{{ route('blogs.index') }}" class="{{ !request('category') ? 'active' : '' }}">
                                        Сите категории
                                    </a>
                                </li>
                                @foreach($categories as $category)
                                    <li>
                                        <a href="{{ route('blogs.index', ['category' => $category->id]) }}"
                                           class="{{ request('category') == $category->id ? 'active' : '' }}">
                                            {{ $category->name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Blog Posts Grid -->
                <div class="col-lg-9">
                    <div class="row gy-4 posts-list">
                        @foreach($blogs as $blog)
                            <div class="col-lg-4">
                                <div class="post-item position-relative h-100">
                                    <div class="post-img position-relative overflow-hidden">
                                        @if($blog->image)
                                            <img src="{{ Storage::url($blog->image) }}"
                                                 class="img-fluid"
                                                 alt="{{ $blog->title }}">
                                        @else
                                            <img src="https://picsum.photos/800/600"
                                                class="img-fluid"
                                                alt="{{ $blog->title }}">
                                        @endif
                                        <span class="post-date">{{ $blog->published_at->format('M d, Y') }}</span>
                                    </div>

                                    <div class="post-content d-flex flex-column">
                                        <h3 class="post-title">{{ $blog->title }}</h3>

                                        <div class="meta d-flex align-items-center">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-folder2"></i>
                                                <span class="ps-2">{{ $blog->category->name }}</span>
                                            </div>
                                        </div>

                                        <div class="post-description">
                                            {{ Str::limit(strip_tags($blog->description), 100) }}
                                        </div>

                                        <hr>

                                        <a href="{{ route('blogs.show', $blog->slug) }}" class="readmore stretched-link">
                                            <span>Read More</span>
                                            <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="pagination justify-content-center mt-4">
                        {{ $blogs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
