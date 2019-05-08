<div class="bg-gray mt-8">
  <div class="container temoignagne-container">
    {{ the_post_thumbnail() }}
    <div class="icon-text mt-5"><i class="far fa-comment fa-2x"></i> L'histoire de :</div>
    <h1 class="mt-2">{!! App::title() !!}</h1>
    <div class="magazine mt-5">@php the_content() @endphp</div>
  </div>
</div>

{!! wp_link_pages(['echo' => 0, 'before' => '<nav class="page-nav"><p>' . __('Pages:', 'sage'), 'after' => '</p></nav>']) !!}
