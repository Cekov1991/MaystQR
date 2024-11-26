@extends('layout')

@section('content')
<div class="page-title" data-aos="fade">
    <div class="heading">
        <div class="container">
            <div class="row d-flex justify-content-center text-center">
                <div class="col-lg-8">
                    <h1>Македонско списание за офталмологија</h1>
                </div>
            </div>
        </div>
    </div>
    <nav class="breadcrumbs">
        <div class="container">
            <ol>
                <li><a href="{{route('welcome')}}">Насловна</a></li>
                <li class="current">Македонско списание за офталмологија</li>
            </ol>
        </div>
    </nav>
</div>
<div class="container my-5">
    @foreach($publications as $publication)
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <a href="{{ route('publications.download', $publication->id) }}"  target="_blank">
                                <h5 class="card-title">{{ $publication->caption }}</h5>
                            </a>
                            <p class="card-text"><small class="text-muted">{{ $publication->description }}</small></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
