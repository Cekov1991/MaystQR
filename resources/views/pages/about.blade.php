@extends('layout')

@section('content')
<div class="page-title" data-aos="fade">
    <div class="heading">
        <div class="container">
            <div class="row d-flex justify-content-center text-center">
                <div class="col-lg-8">
                    <h1>{{$page->title}}</h1>
                </div>
            </div>
        </div>
    </div>
    <nav class="breadcrumbs">
        <div class="container">
            <ol>
                <li><a href="{{route('welcome')}}">Насловна</a></li>
                <li class="current">{{$page->title}}</li>
            </ol>
        </div>
    </nav>
</div>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body">
                   {!!$page->description!!}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
