<div class="bg-gray mt-8 pb-1">
  <div class="container article-container">
    <?php if (function_exists('rank_math_the_breadcrumbs')) rank_math_the_breadcrumbs(); ?>
    {{ the_post_thumbnail('full', ['class' => 'img-fluid']) }}
    <div class="offset-md-1 col-md-6 pt-5">
      @if(in_category('Évènement'))
      <div class="icon-text"><i class="far fa-calendar-alt fa-2x"></i> Évènement, publié le <time class="updated"
          datetime="{{ get_post_time('c', true) }}">{{ get_the_date() }}</time></div>
      @else
      <div class="icon-text"><i class="far fa-newspaper fa-2x"></i> Article, publié le <time class="updated"
          datetime="{{ get_post_time('c', true) }}">{{ get_the_date() }}</time></div>
      @endif
      <h1 class="mt-2">{!! App::title() !!}</h1>
      @if(in_category('Évènement'))
      <div class="icon-text text-blue"><i class="far fa-sticky-note fa-lg"></i>Date de l'événement :
        {{ the_field('date') }}</div>
      <div class="icon-text text-blue"><i class="fas fa-map-marker-alt fa-lg"></i>Lieu :
        {{ get_field('lieu')['address'] }}</div>
      @endif
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
