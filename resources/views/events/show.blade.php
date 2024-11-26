@extends('layout')

@section('content')
<!-- Event Details Section -->
<section id="event-details" class="event-details section">
    <div class="container" data-aos="fade-up">

        <div class="row">
            <div class="col-lg-8">
                <article class="event-details">
                    @if($event->image)
                        <div class="event-img">
                            <img src="{{ Storage::url($event->image) }}" alt="{{ $event->title }}" class="img-fluid">
                        </div>
                    @else
                        <div class="event-img">
                            <img src="https://picsum.photos/800/600" alt="{{ $event->title }}" class="img-fluid">
                        </div>
                    @endif

                    <h2 class="mt-5 title">{{ $event->title }}</h2>

                    <div class="meta-top">

                            <div class="mt-2 d-flex align-items-between">
                                <i class="bi bi-calendar-event"></i>
                                <span>Start: {{ $event->start_time->format('l, F jS \a\t g:i a') }}</span>
                            </div>
                            <div class="mt-2 d-flex align-items-between">
                                <i class="bi bi-calendar-event"></i>
                                <span>End: {{ $event->end_time->format('l, F jS \a\t g:i a') }}</span>
                            </div>


                    </div>

                    <div class="content mt-5">
                        {!! $event->description !!}
                    </div>
                </article>
            </div>

            <div class="col-lg-4">
                <div class="sidebar mt-5">
                    <!-- Related Events Widget -->
                    {{-- @if($upcomingEvents->isNotEmpty())
                        <div class="sidebar-item recent-posts">
                            <h3 class="sidebar-title">Идни настани</h3>
                            <div class="mt-3">
                                @foreach($upcomingEvents as $upcomingEvent)
                                    <div class="post-item mt-3">
                                        <div>
                                            <h4>
                                                <a href="{{ route('events.show', $upcomingEvent->slug) }}">
                                                    {{ $upcomingEvent->title }}
                                                </a>
                                            </h4>
                                            <time datetime="{{ $upcomingEvent->start_time->format('Y-m-d H:i') }}">
                                                {{ $upcomingEvent->start_time->format('M d, Y - H:i') }}
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
</section><!-- /Event Details Section -->
@endsection
