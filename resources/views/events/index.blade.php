@extends('layout')

@section('content')
    <!-- Events Section -->
    <section id="events" class="events section">

        <div class="container" data-aos="fade-up">

            <div class="row">
                @foreach($events as $event)
                <div class="col-md-6 d-flex align-items-stretch">
                    <div class="card">
                        <div class="card-img">
                            @if($event->image)
                                <img src="{{ Storage::url($event->image) }}" alt="{{ $event->title }}" class="img-fluid">
                            @else
                                <img src="https://picsum.photos/800/600" alt="Default Image" class="img-fluid">
                            @endif
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="{{ route('events.show', $event->slug) }}">{{ $event->title }}</a>
                            </h5>
                            <p class="fst-italic text-center">
                                {{ $event->start_time->format('l, F jS \a\t g:i a') }}
                            </p>
                            <p class="card-text">
                                {{ Str::limit(strip_tags($event->description), 150) }}
                            </p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4">
                {{ $events->links() }}
            </div>

        </div>

    </section><!-- /Events Section -->
@endsection
