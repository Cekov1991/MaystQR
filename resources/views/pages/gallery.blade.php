@extends('layout')

@section('content')
<div class="page-title" data-aos="fade">
    <div class="heading">
        <div class="container">
            <div class="row d-flex justify-content-center text-center">
                <div class="col-lg-8">
                    <h1>Галерија</h1>
                </div>
            </div>
        </div>
    </div>
    <nav class="breadcrumbs">
        <div class="container">
            <ol>
                <li><a href="{{route('welcome')}}">Насловна</a></li>
                <li class="current">Галерија</li>
            </ol>
        </div>
    </nav>
</div>
<div class="container py-5">
    @foreach($images as $folder => $folderImages)
        <div class="card mb-4">
            <div class="card-header page-title">
                <h4 class="m-0">{{ ucfirst($folder) }}</h4>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach ($folderImages as $image)
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="card h-100">
                                <img src="{{ asset('storage/' . $image->image_path) }}"
                                    class="gallery-image"
                                    alt="{{ $image->caption ?? 'Gallery Image' }}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#galleryModal"
                                    data-index="{{ $folder }}"
                                    style="cursor: pointer; object-fit: cover; height: 200px; width: 100%;">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>

<!-- Modal -->
<div class="modal fade" id="galleryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body p-0 position-relative">
                <button type="button" class="btn-close position-absolute top-0 end-0 p-3" data-bs-dismiss="modal" aria-label="Close"></button>
                <img src="" id="modalImage" class="img-fluid w-100" alt="Gallery Image">

                <button class="btn btn-dark position-absolute top-50 start-0 translate-middle-y" id="prevImage">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button class="btn btn-dark position-absolute top-50 end-0 translate-middle-y" id="nextImage">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
@endpush

@push('scripts')
<script>
let currentFolder = '';
let currentImageIndex = 0;
const images = @json($images);
const folders = Object.keys(images);

document.querySelectorAll('.gallery-image').forEach(image => {
    image.addEventListener('click', function() {
        currentFolder = this.dataset.index;
        currentImageIndex = Array.from(this.parentElement.parentElement.children).indexOf(this.parentElement);
        updateModalImage();
    });
});

document.getElementById('prevImage').addEventListener('click', function() {
    currentImageIndex--;
    if (currentImageIndex < 0) {
        currentImageIndex = images[currentFolder].length - 1;
    }
    updateModalImage();
});

document.getElementById('nextImage').addEventListener('click', function() {
    currentImageIndex++;
    if (currentImageIndex >= images[currentFolder].length) {
        currentImageIndex = 0;
    }
    updateModalImage();
});

function updateModalImage() {
    const modalImage = document.getElementById('modalImage');
    const currentImage = images[currentFolder][currentImageIndex];
    modalImage.src = '/storage/' + currentImage.image_path;
    modalImage.alt = currentImage.caption || 'Gallery Image';
}

// Optional: Enable keyboard navigation
document.addEventListener('keydown', function(e) {
    if (!document.getElementById('galleryModal').classList.contains('show')) return;

    if (e.key === 'ArrowLeft') document.getElementById('prevImage').click();
    if (e.key === 'ArrowRight') document.getElementById('nextImage').click();
    if (e.key === 'Escape') document.querySelector('.btn-close').click();
});
</script>
@endpush
@endsection
