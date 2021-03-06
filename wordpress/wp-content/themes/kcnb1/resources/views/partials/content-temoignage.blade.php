<div class="bg-gray mt-8 pb-1">
  <div class="container article-container">
    <?php if (function_exists('rank_math_the_breadcrumbs')) rank_math_the_breadcrumbs(); ?>
    {{ the_post_thumbnail('full', ['class' => 'img-fluid']) }}
    <div class="offset-md-1 col-md-6">
      <div class="icon-text mt-5"><i class="far fa-comment fa-2x"></i> L'histoire de :</div>
      <h1 class="mt-2">{!! App::title() !!}</h1>
      <div class="mt-5">@php the_content() @endphp</div>
      <div class="text-center mb-3 mt-3">
        <span>Partagez cette page : </span>
        <?php echo do_shortcode("[addtoany]"); ?>
      </div>
    </div>
  </div>
</div>

{!! wp_link_pages(['echo' => 0, 'before' => '<nav class="page-nav">
  <p>' . __('Pages:', 'sage'), 'after' => '</p>
</nav>']) !!}
