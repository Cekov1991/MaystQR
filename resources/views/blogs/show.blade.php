@extends('layout')

@section('content')
    <!-- ======= Blog Details Section ======= -->
    <section id="blog" class="blog">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-8">
                    <article class="blog-details">
                        @if($blog->image)
                            <div class="post-img">
                                <img src="{{ Storage::url($blog->image) }}"
                                     alt="{{ $blog->title }}"
                                     class="img-fluid">
                            </div>
                            @else
                            <div class="post-img">
                                <img src="https://picsum.photos/800/600"
                                     alt="{{ $blog->title }}"
                                     class="img-fluid">
                            </div>
                        @endif

                        <h2 class="title mt-5">{{ $blog->title }}</h2>

                        <div class="meta-top mt-3">

                                <div class="d-flex align-items-center">
                                    <i class="bi bi-clock"></i>
                                    <time datetime="{{ $blog->published_at->format('Y-m-d') }}">
                                        {{ $blog->published_at->format('M d, Y') }}
                                    </time>
                                </div>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-folder2"></i>
                                    <a href="{{ route('blogs.index', ['category' => $blog->category_id]) }}">
                                        {{ $blog->category->name }}
                                    </a>
                                </div>

                        </div>

                        <div class="content mt-5">
                            {!! $blog->description !!}
                        </div>
                    </article>
                </div>

                <div class="col-lg-4">
                    <div class="sidebar">
                        {{-- <!-- Recent Posts Widget -->
                        @if($relatedBlogs->isNotEmpty())
                            <div class="sidebar-item recent-posts">
                                <h3 class="sidebar-title">Related Posts</h3>
                                <div class="mt-3">
                                    @foreach($relatedBlogs as $relatedBlog)
                                        <div class="post-item mt-3">
                                            @if($relatedBlog->image)
                                                <img src="{{ Storage::url($relatedBlog->image) }}"
                                                     alt="{{ $relatedBlog->title }}"
                                                     class="flex-shrink-0">
                                            @else
                                                <img src="https://picsum.photos/800/600"
                                                     alt="{{ $relatedBlog->title }}"
                                                     class="flex-shrink-0">
                                            @endif

                                            <div>
                                                <h4>
                                                    <a href="{{ route('blogs.show', $relatedBlog->slug) }}">
                                                        {{ $relatedBlog->title }}
                                                    </a>
                                                </h4>
                                                <time datetime="{{ $relatedBlog->published_at->format('Y-m-d') }}">
                                                    {{ $relatedBlog->published_at->format('M d, Y') }}
                                                </time>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif --}}
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
