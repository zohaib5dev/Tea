@foreach($posts as $post)
    @include('partials.post-card', ['post' => $post])
@endforeach
